<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ConfiguracionDian;
use App\Models\User;
use App\Services\Dian\EmailProcessorService;
use Illuminate\Support\Facades\Log;

echo "=== CONFIGURACIÓN REAL DEL MÓDULO DIAN ===\n\n";

try {
    // 1. Verificar usuario y empresa
    echo "👤 1. VERIFICANDO USUARIO Y EMPRESA...\n";
    $usuario = User::with('empresa')->first();
    
    if (!$usuario || !$usuario->empresa) {
        echo "  ❌ Usuario o empresa no encontrados\n";
        exit(1);
    }
    
    echo "  ✅ Usuario: {$usuario->name} ({$usuario->email})\n";
    echo "  ✅ Empresa: {$usuario->empresa->nombre}\n";
    
    // 2. Configurar con datos reales
    echo "\n📧 2. CONFIGURANDO CON DATOS REALES...\n";
    
    $emailReal = 'pcapacho24@gmail.com'; // Corregido el @ que faltaba
    $passwordReal = 'adkq prqh vhii njnz';
    
    Log::info('DIAN Configuración Real: Iniciando configuración con datos reales', [
        'usuario_id' => $usuario->id,
        'empresa_id' => $usuario->empresa->id,
        'email_configurado' => $emailReal
    ]);
    
    $configuracion = ConfiguracionDian::updateOrCreate(
        ['empresa_id' => $usuario->empresa->id],
        [
            'email_dian' => $emailReal,
            'password_email' => $passwordReal,
            'servidor_imap' => 'imap.gmail.com',
            'puerto_imap' => 993,
            'ssl_enabled' => true,
            'email_remitente' => $emailReal,
            'nombre_remitente' => $usuario->empresa->nombre ?: 'Mi Empresa',
            'plantilla_acuse' => null, // Usará la plantilla por defecto
            'frecuencia_revision' => 60, // Cada 60 minutos
            'hora_inicio' => '08:00',
            'hora_fin' => '18:00',
            'activo' => true, // Activar para pruebas
            'carpeta_descarga' => 'dian/descargas'
        ]
    );
    
    echo "  ✅ Configuración actualizada exitosamente\n";
    echo "    📧 Email: {$configuracion->email_dian}\n";
    echo "    🌐 Servidor: {$configuracion->servidor_imap}:{$configuracion->puerto_imap}\n";
    echo "    🔒 SSL: " . ($configuracion->ssl_enabled ? 'Sí' : 'No') . "\n";
    echo "    ⚡ Activo: " . ($configuracion->activo ? 'Sí' : 'No') . "\n";
    
    Log::info('DIAN Configuración Real: Configuración guardada', [
        'configuracion_id' => $configuracion->id,
        'email_dian' => $configuracion->email_dian,
        'servidor_imap' => $configuracion->servidor_imap,
        'activo' => $configuracion->activo
    ]);
    
    // 3. Probar conexión IMAP
    echo "\n🔌 3. PROBANDO CONEXIÓN IMAP REAL...\n";
    
    Log::info('DIAN Prueba Real: Iniciando prueba de conexión IMAP');
    
    $emailProcessor = new EmailProcessorService($configuracion);
    
    // Usar reflexión para acceder al método privado conectarIMAP
    $reflection = new ReflectionClass($emailProcessor);
    $conectarMethod = $reflection->getMethod('conectarIMAP');
    $conectarMethod->setAccessible(true);
    
    echo "  🔍 Intentando conectar a Gmail...\n";
    
    if ($conectarMethod->invoke($emailProcessor)) {
        echo "  🎉 ¡CONEXIÓN EXITOSA!\n";
        echo "    ✅ Conectado a Gmail correctamente\n";
        echo "    ✅ Credenciales válidas\n";
        echo "    ✅ Servidor IMAP funcionando\n";
        
        Log::info('DIAN Prueba Real: Conexión IMAP exitosa', [
            'email' => $emailReal,
            'servidor' => 'imap.gmail.com:993'
        ]);
        
        // Cerrar conexión
        $cerrarMethod = $reflection->getMethod('cerrarConexionIMAP');
        $cerrarMethod->setAccessible(true);
        $cerrarMethod->invoke($emailProcessor);
        
    } else {
        echo "  ❌ Error de conexión\n";
        echo "    🔍 Verifica:\n";
        echo "      - Contraseña de aplicación correcta\n";
        echo "      - 2FA activado en Gmail\n";
        echo "      - IMAP habilitado en Gmail\n";
        
        Log::error('DIAN Prueba Real: Error de conexión IMAP', [
            'email' => $emailReal,
            'servidor' => 'imap.gmail.com:993'
        ]);
    }
    
    // 4. Probar procesamiento completo
    echo "\n📬 4. PROBANDO PROCESAMIENTO COMPLETO...\n";
    
    Log::info('DIAN Prueba Real: Iniciando procesamiento completo de emails');
    
    try {
        $resultados = $emailProcessor->procesarEmails();
        
        echo "  📊 Resultados del procesamiento:\n";
        echo "    📧 Emails procesados: {$resultados['emails_procesados']}\n";
        echo "    📄 Facturas encontradas: {$resultados['facturas_encontradas']}\n";
        echo "    ❌ Errores: " . count($resultados['errores']) . "\n";
        
        if (!empty($resultados['errores'])) {
            echo "\n  🔍 Errores encontrados:\n";
            foreach ($resultados['errores'] as $error) {
                echo "    • {$error}\n";
            }
        }
        
        if (!empty($resultados['facturas_procesadas'])) {
            echo "\n  🎉 Facturas procesadas:\n";
            foreach ($resultados['facturas_procesadas'] as $factura) {
                echo "    • ID: {$factura->id} - {$factura->asunto_email}\n";
            }
        }
        
        Log::info('DIAN Prueba Real: Procesamiento completado', [
            'emails_procesados' => $resultados['emails_procesados'],
            'facturas_encontradas' => $resultados['facturas_encontradas'],
            'errores_count' => count($resultados['errores'])
        ]);
        
    } catch (\Exception $e) {
        echo "  ❌ Error en procesamiento: {$e->getMessage()}\n";
        
        Log::error('DIAN Prueba Real: Error en procesamiento', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    
    // 5. Verificar extensión IMAP
    echo "\n🔌 5. VERIFICANDO EXTENSIÓN IMAP...\n";
    if (extension_loaded('imap')) {
        echo "  ✅ Extensión IMAP disponible\n";
        
        // Verificar funciones específicas
        $funcionesImap = ['imap_open', 'imap_search', 'imap_fetchstructure', 'imap_fetchbody', 'imap_close'];
        foreach ($funcionesImap as $funcion) {
            if (function_exists($funcion)) {
                echo "  ✅ Función {$funcion} disponible\n";
            } else {
                echo "  ❌ Función {$funcion} NO disponible\n";
            }
        }
    } else {
        echo "  ❌ Extensión IMAP NO disponible\n";
    }
    
    // 6. Estado final
    echo "\n🎯 6. ESTADO FINAL DEL MÓDULO DIAN:\n";
    echo "  ✅ Configuración completa\n";
    echo "  ✅ Email configurado: {$emailReal}\n";
    echo "  ✅ Módulo activado\n";
    echo "  ✅ Logging habilitado\n";
    
    echo "\n📍 ACCESO AL MÓDULO:\n";
    echo "  🏠 Dashboard: http://127.0.0.1:8000/dian\n";
    echo "  ⚙️ Configuración: http://127.0.0.1:8000/dian/configuracion\n";
    
    echo "\n📊 MONITOREO EN TIEMPO REAL:\n";
    echo "  📝 Ver todos los logs: tail -f storage/logs/laravel.log\n";
    echo "  🔍 Solo logs DIAN: tail -f storage/logs/laravel.log | grep DIAN\n";
    
    Log::info('DIAN Prueba Real: Configuración completada exitosamente', [
        'email_configurado' => $emailReal,
        'modulo_activo' => true,
        'logging_habilitado' => true
    ]);
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    
    Log::error('DIAN Prueba Real: Error en configuración', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

echo "\n✅ Configuración y pruebas completadas.\n";
