<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ConfiguracionDian;
use App\Models\User;
use Illuminate\Support\Facades\Log;

echo "=== DIAGNÓSTICO COMPLETO MÓDULO DIAN ===\n\n";

$problemas = [];
$solucionesRecomendadas = [];

try {
    // 1. Verificar base de datos y configuración
    echo "💾 1. VERIFICANDO BASE DE DATOS...\n";
    
    $usuario = User::with('empresa')->first();
    if (!$usuario) {
        $problemas[] = "No hay usuarios en el sistema";
        echo "  ❌ No hay usuarios\n";
    } else {
        echo "  ✅ Usuario: {$usuario->name}\n";
        
        if (!$usuario->empresa) {
            $problemas[] = "Usuario sin empresa asociada";
            echo "  ❌ Sin empresa asociada\n";
        } else {
            echo "  ✅ Empresa: {$usuario->empresa->nombre}\n";
        }
    }
    
    $configuracion = null;
    if ($usuario && $usuario->empresa) {
        $configuracion = ConfiguracionDian::where('empresa_id', $usuario->empresa->id)->first();
        if (!$configuracion) {
            $problemas[] = "No hay configuración DIAN";
            echo "  ❌ Sin configuración DIAN\n";
        } else {
            echo "  ✅ Configuración DIAN encontrada\n";
            echo "    📧 Email: {$configuracion->email_dian}\n";
            echo "    ⚡ Activo: " . ($configuracion->activo ? 'Sí' : 'No') . "\n";
        }
    }
    
    // 2. Verificar extensión IMAP
    echo "\n🔌 2. VERIFICANDO EXTENSIÓN IMAP...\n";
    
    // CLI
    $imapCLI = extension_loaded('imap');
    echo "  📟 CLI: " . ($imapCLI ? '✅ Habilitado' : '❌ Deshabilitado') . "\n";
    
    // Web (simulado)
    $phpIniPath = php_ini_loaded_file();
    echo "  📄 php.ini: {$phpIniPath}\n";
    
    if ($phpIniPath && file_exists($phpIniPath)) {
        $phpIniContent = file_get_contents($phpIniPath);
        $imapHabilitado = preg_match('/^\s*extension=imap\s*$/m', $phpIniContent);
        $imapComentado = preg_match('/^\s*;extension=imap\s*$/m', $phpIniContent);
        
        if ($imapHabilitado) {
            echo "  ✅ IMAP habilitado en php.ini\n";
        } elseif ($imapComentado) {
            echo "  ⚠️ IMAP comentado en php.ini\n";
            $problemas[] = "IMAP está comentado en php.ini";
            $solucionesRecomendadas[] = "Descomentar extension=imap en php.ini";
        } else {
            echo "  ❌ IMAP no encontrado en php.ini\n";
            $problemas[] = "IMAP no está en php.ini";
            $solucionesRecomendadas[] = "Agregar extension=imap a php.ini";
        }
    }
    
    // Verificar DLL
    $phpDir = dirname(PHP_BINARY);
    $imapDll = $phpDir . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR . 'php_imap.dll';
    
    if (file_exists($imapDll)) {
        echo "  ✅ DLL encontrada: {$imapDll}\n";
    } else {
        echo "  ❌ DLL no encontrada: {$imapDll}\n";
        $problemas[] = "DLL de IMAP no encontrada";
        $solucionesRecomendadas[] = "Reinstalar XAMPP o descargar php_imap.dll";
    }
    
    // 3. Probar conexión IMAP (si está disponible)
    echo "\n📧 3. PROBANDO CONEXIÓN IMAP...\n";
    
    if ($imapCLI && $configuracion) {
        $servidor = "{{$configuracion->servidor_imap}:{$configuracion->puerto_imap}";
        if ($configuracion->ssl_enabled) {
            $servidor .= "/imap/ssl";
        }
        $servidor .= "}INBOX";
        
        echo "  🔗 Conectando a: {$servidor}\n";
        echo "  👤 Usuario: {$configuracion->email_dian}\n";
        
        $connection = @imap_open(
            $servidor,
            $configuracion->email_dian,
            $configuracion->password_email
        );
        
        if ($connection) {
            echo "  🎉 ¡CONEXIÓN EXITOSA!\n";
            
            $info = imap_mailboxmsginfo($connection);
            echo "    📊 Total mensajes: {$info->Nmsgs}\n";
            echo "    📧 No leídos: {$info->Unread}\n";
            
            imap_close($connection);
        } else {
            $error = imap_last_error();
            echo "  ❌ Error de conexión: {$error}\n";
            $problemas[] = "Error de conexión IMAP: {$error}";
            
            if (strpos($error, 'authentication failed') !== false) {
                $solucionesRecomendadas[] = "Verificar email y contraseña de aplicación";
            }
        }
    } else {
        echo "  ⚠️ No se puede probar (IMAP no disponible o sin configuración)\n";
    }
    
    // 4. Verificar archivos del módulo
    echo "\n📁 4. VERIFICANDO ARCHIVOS DEL MÓDULO...\n";
    
    $archivosRequeridos = [
        'app/Models/ConfiguracionDian.php',
        'app/Models/FacturaDianProcesada.php',
        'app/Services/Dian/EmailProcessorService.php',
        'app/Services/Dian/FileExtractorService.php',
        'app/Services/Dian/XmlFacturaService.php',
        'app/Services/Dian/AcuseGeneratorService.php',
        'app/Http/Controllers/DianFacturasController.php',
        'resources/views/dian/dashboard.blade.php',
        'resources/views/dian/configuracion.blade.php'
    ];
    
    $archivosExistentes = 0;
    foreach ($archivosRequeridos as $archivo) {
        if (file_exists(__DIR__ . '/' . $archivo)) {
            echo "  ✅ {$archivo}\n";
            $archivosExistentes++;
        } else {
            echo "  ❌ {$archivo}\n";
            $problemas[] = "Archivo faltante: {$archivo}";
        }
    }
    
    echo "  📊 Archivos: {$archivosExistentes}/" . count($archivosRequeridos) . "\n";
    
    // 5. Verificar rutas
    echo "\n🛣️ 5. VERIFICANDO RUTAS...\n";
    
    try {
        $routeList = \Illuminate\Support\Facades\Route::getRoutes();
        $rutasDian = [];
        
        foreach ($routeList as $route) {
            if (strpos($route->getName(), 'dian.') === 0) {
                $rutasDian[] = $route->getName();
            }
        }
        
        echo "  📊 Rutas DIAN encontradas: " . count($rutasDian) . "\n";
        foreach ($rutasDian as $ruta) {
            echo "    ✅ {$ruta}\n";
        }
        
    } catch (\Exception $e) {
        echo "  ⚠️ Error verificando rutas: {$e->getMessage()}\n";
    }
    
    // 6. Verificar logs
    echo "\n📄 6. VERIFICANDO LOGS...\n";
    
    $logPath = storage_path('logs/laravel.log');
    if (file_exists($logPath)) {
        $logSize = filesize($logPath);
        echo "  ✅ Archivo de log: {$logPath}\n";
        echo "  📊 Tamaño: " . number_format($logSize / 1024, 2) . " KB\n";
        
        // Buscar logs DIAN recientes
        $logContent = file_get_contents($logPath);
        $logsDian = substr_count($logContent, 'DIAN');
        echo "  📊 Entradas DIAN: {$logsDian}\n";
        
    } else {
        echo "  ❌ Archivo de log no encontrado\n";
        $problemas[] = "Archivo de log no encontrado";
    }
    
    // 7. Resumen y recomendaciones
    echo "\n🎯 7. RESUMEN Y RECOMENDACIONES...\n";
    
    if (empty($problemas)) {
        echo "  🎉 ¡TODO ESTÁ CONFIGURADO CORRECTAMENTE!\n";
        echo "  ✅ El módulo DIAN debería funcionar perfectamente\n";
        
        echo "\n🚀 PRÓXIMOS PASOS:\n";
        echo "  1. Ve a: http://127.0.0.1:8000/dian\n";
        echo "  2. Configura tu email si no lo has hecho\n";
        echo "  3. Prueba la conexión IMAP\n";
        echo "  4. Activa el procesamiento automático\n";
        
    } else {
        echo "  ⚠️ SE ENCONTRARON " . count($problemas) . " PROBLEMAS:\n\n";
        
        foreach ($problemas as $i => $problema) {
            echo "    " . ($i + 1) . ". ❌ {$problema}\n";
        }
        
        if (!empty($solucionesRecomendadas)) {
            echo "\n  🔧 SOLUCIONES RECOMENDADAS:\n\n";
            foreach ($solucionesRecomendadas as $i => $solucion) {
                echo "    " . ($i + 1) . ". 🔧 {$solucion}\n";
            }
        }
        
        echo "\n  📋 PASOS PRIORITARIOS:\n";
        if (in_array("IMAP está comentado en php.ini", $solucionesRecomendadas)) {
            echo "    1. 🔧 Habilitar IMAP en php.ini\n";
            echo "    2. 🔄 Reiniciar Apache\n";
            echo "    3. 🧪 Probar conexión desde web\n";
        }
    }
    
    // 8. Enlaces útiles
    echo "\n🔗 8. ENLACES ÚTILES:\n";
    echo "  🏠 Dashboard DIAN: http://127.0.0.1:8000/dian\n";
    echo "  ⚙️ Configuración: http://127.0.0.1:8000/dian/configuracion\n";
    echo "  🔍 Verificar IMAP Web: http://127.0.0.1:8000/verificar_imap_web.php\n";
    echo "  📖 Guía completa: SOLUCION_IMAP_XAMPP.md\n";
    
    Log::info('DIAN Diagnóstico: Diagnóstico completo ejecutado', [
        'problemas_encontrados' => count($problemas),
        'archivos_existentes' => $archivosExistentes ?? 0,
        'imap_cli' => $imapCLI,
        'configuracion_existe' => $configuracion ? true : false
    ]);
    
} catch (\Exception $e) {
    echo "❌ Error en diagnóstico: " . $e->getMessage() . "\n";
    Log::error('DIAN Diagnóstico: Error en diagnóstico completo', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

echo "\n✅ Diagnóstico completo finalizado.\n";
