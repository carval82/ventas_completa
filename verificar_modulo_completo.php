<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ConfiguracionDian;
use App\Models\FacturaDianProcesada;
use App\Models\User;
use Illuminate\Support\Facades\Log;

echo "=== VERIFICACIÓN COMPLETA DEL MÓDULO DIAN ===\n\n";

try {
    // 1. Verificar configuración básica
    echo "🔍 1. VERIFICANDO CONFIGURACIÓN BÁSICA...\n";
    
    $usuario = User::with('empresa')->first();
    if (!$usuario || !$usuario->empresa) {
        echo "  ❌ Usuario o empresa no encontrados\n";
        exit(1);
    }
    
    $configuracion = ConfiguracionDian::where('empresa_id', $usuario->empresa->id)->first();
    if (!$configuracion) {
        echo "  ❌ No hay configuración DIAN\n";
        exit(1);
    }
    
    echo "  ✅ Usuario: {$usuario->name}\n";
    echo "  ✅ Empresa: {$usuario->empresa->nombre}\n";
    echo "  ✅ Email: {$configuracion->email_dian}\n";
    echo "  ✅ Activo: " . ($configuracion->activo ? 'Sí' : 'No') . "\n";
    
    // 2. Verificar IMAP
    echo "\n🔌 2. VERIFICANDO IMAP...\n";
    
    $imapDisponible = extension_loaded('imap');
    echo "  📟 IMAP disponible: " . ($imapDisponible ? '✅ Sí' : '❌ No') . "\n";
    
    if ($imapDisponible) {
        // Probar conexión IMAP real
        $servidor = "{{$configuracion->servidor_imap}:{$configuracion->puerto_imap}";
        if ($configuracion->ssl_enabled) {
            $servidor .= "/imap/ssl";
        }
        $servidor .= "}INBOX";
        
        $connection = @imap_open(
            $servidor,
            $configuracion->email_dian,
            $configuracion->password_email
        );
        
        if ($connection) {
            $info = imap_mailboxmsginfo($connection);
            echo "  🎉 Conexión IMAP exitosa\n";
            echo "    📊 Total mensajes: {$info->Nmsgs}\n";
            echo "    📬 No leídos: {$info->Unread}\n";
            imap_close($connection);
        } else {
            $error = imap_last_error();
            echo "  ⚠️ Error IMAP: {$error}\n";
        }
    }
    
    // 3. Verificar archivos del módulo
    echo "\n📁 3. VERIFICANDO ARCHIVOS DEL MÓDULO...\n";
    
    $archivosRequeridos = [
        'app/Models/ConfiguracionDian.php',
        'app/Models/FacturaDianProcesada.php',
        'app/Services/Dian/EmailProcessorService.php',
        'app/Services/Dian/GmailApiService.php',
        'app/Http/Controllers/DianFacturasController.php',
        'resources/views/dian/dashboard.blade.php',
        'resources/views/dian/configuracion.blade.php',
        'resources/views/dian/facturas.blade.php',
        'resources/views/dian/partials/factura-detalle-modal.blade.php'
    ];
    
    $archivosOk = 0;
    foreach ($archivosRequeridos as $archivo) {
        if (file_exists(__DIR__ . '/' . $archivo)) {
            echo "  ✅ {$archivo}\n";
            $archivosOk++;
        } else {
            echo "  ❌ {$archivo}\n";
        }
    }
    
    echo "  📊 Archivos: {$archivosOk}/" . count($archivosRequeridos) . "\n";
    
    // 4. Verificar base de datos
    echo "\n💾 4. VERIFICANDO BASE DE DATOS...\n";
    
    $totalFacturas = FacturaDianProcesada::where('empresa_id', $usuario->empresa->id)->count();
    echo "  📊 Total facturas en BD: {$totalFacturas}\n";
    
    if ($totalFacturas > 0) {
        $ultimaFactura = FacturaDianProcesada::where('empresa_id', $usuario->empresa->id)
                                           ->orderBy('created_at', 'desc')
                                           ->first();
        echo "  📄 Última factura: {$ultimaFactura->asunto_email}\n";
        echo "  📅 Fecha: {$ultimaFactura->created_at}\n";
    }
    
    // 5. Verificar rutas
    echo "\n🛣️ 5. VERIFICANDO RUTAS...\n";
    
    $rutasEsperadas = [
        'dian.dashboard',
        'dian.configuracion',
        'dian.facturas',
        'dian.factura.detalle',
        'dian.factura.detalle.ajax',
        'dian.factura.xml',
        'dian.factura.acuse'
    ];
    
    $rutasOk = 0;
    foreach ($rutasEsperadas as $ruta) {
        try {
            $url = route($ruta, $ruta === 'dian.factura.detalle' || 
                              $ruta === 'dian.factura.detalle.ajax' || 
                              $ruta === 'dian.factura.xml' || 
                              $ruta === 'dian.factura.acuse' ? 1 : []);
            echo "  ✅ {$ruta}: {$url}\n";
            $rutasOk++;
        } catch (\Exception $e) {
            echo "  ❌ {$ruta}: Error\n";
        }
    }
    
    echo "  📊 Rutas: {$rutasOk}/" . count($rutasEsperadas) . "\n";
    
    // 6. Verificar logging
    echo "\n📄 6. VERIFICANDO LOGGING...\n";
    
    Log::info('DIAN Verificación: Módulo completamente verificado', [
        'usuario_id' => $usuario->id,
        'empresa_id' => $usuario->empresa->id,
        'archivos_ok' => $archivosOk,
        'rutas_ok' => $rutasOk,
        'total_facturas' => $totalFacturas,
        'imap_disponible' => $imapDisponible
    ]);
    
    $logPath = storage_path('logs/laravel.log');
    if (file_exists($logPath)) {
        $logSize = filesize($logPath);
        echo "  ✅ Log funcionando (" . number_format($logSize / 1024, 2) . " KB)\n";
    }
    
    // 7. Estado final
    echo "\n🎯 7. ESTADO FINAL DEL MÓDULO DIAN...\n";
    
    $score = 0;
    $maxScore = 5;
    
    if ($archivosOk === count($archivosRequeridos)) $score++;
    if ($rutasOk === count($rutasEsperadas)) $score++;
    if ($configuracion && $configuracion->activo) $score++;
    if ($imapDisponible) $score++;
    if (file_exists($logPath)) $score++;
    
    $porcentaje = ($score / $maxScore) * 100;
    
    echo "  📊 Puntuación: {$score}/{$maxScore} ({$porcentaje}%)\n";
    
    if ($porcentaje >= 80) {
        echo "  🎉 ESTADO: COMPLETAMENTE FUNCIONAL\n";
        echo "  ✅ El módulo DIAN está listo para usar\n";
    } elseif ($porcentaje >= 60) {
        echo "  ⚡ ESTADO: FUNCIONAL CON LIMITACIONES\n";
        echo "  ⚠️ Algunas funciones pueden no estar disponibles\n";
    } else {
        echo "  ❌ ESTADO: REQUIERE ATENCIÓN\n";
        echo "  🔧 Se necesitan correcciones antes de usar\n";
    }
    
    // 8. Enlaces de acceso
    echo "\n🔗 8. ENLACES DE ACCESO...\n";
    echo "  🏠 Dashboard: http://127.0.0.1:8000/dian\n";
    echo "  ⚙️ Configuración: http://127.0.0.1:8000/dian/configuracion\n";
    echo "  📄 Facturas: http://127.0.0.1:8000/dian/facturas\n";
    
    // 9. Instrucciones finales
    echo "\n📋 9. PRÓXIMOS PASOS...\n";
    
    if ($porcentaje >= 80) {
        echo "  🎊 ¡El módulo está listo!\n";
        echo "  1. Ve al dashboard DIAN\n";
        echo "  2. Verifica tu configuración\n";
        echo "  3. Prueba la conexión IMAP\n";
        echo "  4. Procesa algunos emails\n";
        echo "  5. ¡Disfruta del procesamiento automático!\n";
    } else {
        echo "  🔧 Pasos para completar:\n";
        if ($archivosOk < count($archivosRequeridos)) {
            echo "    - Verificar archivos faltantes\n";
        }
        if ($rutasOk < count($rutasEsperadas)) {
            echo "    - Verificar configuración de rutas\n";
        }
        if (!$imapDisponible) {
            echo "    - Habilitar extensión IMAP en PHP\n";
        }
        if (!$configuracion->activo) {
            echo "    - Activar configuración DIAN\n";
        }
    }
    
    echo "\n📊 MONITOREO:\n";
    echo "  📝 Logs generales: tail -f storage/logs/laravel.log\n";
    echo "  🔍 Solo DIAN: tail -f storage/logs/laravel.log | grep DIAN\n";
    
} catch (\Exception $e) {
    echo "❌ Error en verificación: " . $e->getMessage() . "\n";
    Log::error('DIAN Verificación: Error en verificación completa', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

echo "\n✅ Verificación completa finalizada.\n";
