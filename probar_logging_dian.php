<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ConfiguracionDian;
use App\Models\User;
use Illuminate\Support\Facades\Log;

echo "=== PRUEBA DE LOGGING MÓDULO DIAN ===\n\n";

try {
    // 1. Verificar configuración
    echo "🔍 1. VERIFICANDO CONFIGURACIÓN DIAN...\n";
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
    
    echo "  ✅ Configuración encontrada: {$configuracion->email_dian}\n";
    
    // 2. Probar logging básico
    echo "\n📝 2. PROBANDO LOGGING BÁSICO...\n";
    
    Log::info('DIAN Test: Iniciando prueba de logging', [
        'timestamp' => now()->toDateTimeString(),
        'usuario_id' => $usuario->id,
        'empresa_id' => $usuario->empresa->id
    ]);
    
    Log::warning('DIAN Test: Mensaje de advertencia de prueba', [
        'tipo' => 'test',
        'nivel' => 'warning'
    ]);
    
    Log::error('DIAN Test: Mensaje de error de prueba', [
        'tipo' => 'test',
        'nivel' => 'error',
        'detalles' => 'Este es un error simulado para probar el logging'
    ]);
    
    echo "  ✅ Logs básicos enviados\n";
    
    // 3. Simular logging de configuración
    echo "\n⚙️ 3. SIMULANDO LOGGING DE CONFIGURACIÓN...\n";
    
    Log::info('DIAN Configuración: Acceso a configuración', [
        'usuario_id' => $usuario->id,
        'usuario_email' => $usuario->email
    ]);
    
    Log::info('DIAN Configuración: Configuración existente encontrada', [
        'empresa_id' => $usuario->empresa->id,
        'configuracion_id' => $configuracion->id,
        'email_configurado' => $configuracion->email_dian,
        'activo' => $configuracion->activo
    ]);
    
    echo "  ✅ Logs de configuración enviados\n";
    
    // 4. Simular logging de conexión IMAP
    echo "\n🔌 4. SIMULANDO LOGGING DE CONEXIÓN IMAP...\n";
    
    $servidor = "{{$configuracion->servidor_imap}:{$configuracion->puerto_imap}";
    if ($configuracion->ssl_enabled) {
        $servidor .= "/imap/ssl";
    }
    $servidor .= "}INBOX";
    
    Log::info('DIAN IMAP: Intentando conexión', [
        'servidor' => $servidor,
        'email' => $configuracion->email_dian,
        'ssl_enabled' => $configuracion->ssl_enabled
    ]);
    
    // Simular error de conexión
    Log::error("DIAN IMAP: Error de conexión simulado", [
        'error' => 'Login failure: authentication failed',
        'servidor' => $servidor,
        'email' => $configuracion->email_dian
    ]);
    
    echo "  ✅ Logs de conexión IMAP enviados\n";
    
    // 5. Simular logging de procesamiento de emails
    echo "\n📧 5. SIMULANDO LOGGING DE PROCESAMIENTO...\n";
    
    Log::info('DIAN EmailProcessor: Iniciando procesamiento de emails', [
        'empresa_id' => $configuracion->empresa_id,
        'email_dian' => $configuracion->email_dian,
        'servidor_imap' => $configuracion->servidor_imap
    ]);
    
    Log::info('DIAN EmailProcessor: Emails obtenidos', [
        'total_emails' => 3
    ]);
    
    // Simular procesamiento de emails individuales
    for ($i = 1; $i <= 3; $i++) {
        Log::info('DIAN EmailProcessor: Procesando email', [
            'email_id' => $i,
            'asunto' => "Factura Electrónica #{$i} - Proveedor Test"
        ]);
        
        if ($i == 2) {
            Log::warning('DIAN EmailProcessor: Error procesando email', [
                'email_id' => $i,
                'error' => 'No se encontraron archivos adjuntos'
            ]);
        } else {
            Log::info('DIAN EmailProcessor: Email procesado exitosamente', [
                'email_id' => $i,
                'facturas_count' => 1
            ]);
        }
    }
    
    echo "  ✅ Logs de procesamiento enviados\n";
    
    // 6. Verificar archivo de log
    echo "\n📄 6. VERIFICANDO ARCHIVO DE LOG...\n";
    
    $logPath = storage_path('logs/laravel.log');
    
    if (file_exists($logPath)) {
        $logSize = filesize($logPath);
        $logSizeFormatted = number_format($logSize / 1024, 2) . ' KB';
        
        echo "  ✅ Archivo de log encontrado: {$logPath}\n";
        echo "  📊 Tamaño del archivo: {$logSizeFormatted}\n";
        
        // Leer las últimas líneas del log
        $logContent = file_get_contents($logPath);
        $logLines = explode("\n", $logContent);
        $lastLines = array_slice($logLines, -10);
        
        echo "\n📋 ÚLTIMAS 10 LÍNEAS DEL LOG:\n";
        foreach ($lastLines as $line) {
            if (!empty(trim($line))) {
                echo "  " . substr($line, 0, 100) . "...\n";
            }
        }
        
    } else {
        echo "  ❌ Archivo de log no encontrado\n";
    }
    
    // 7. Instrucciones para monitoreo
    echo "\n🎯 7. INSTRUCCIONES PARA MONITOREO EN TIEMPO REAL:\n";
    echo "  📝 Para ver logs en tiempo real:\n";
    echo "     tail -f storage/logs/laravel.log\n";
    echo "\n  🔍 Para filtrar solo logs DIAN:\n";
    echo "     tail -f storage/logs/laravel.log | grep DIAN\n";
    echo "\n  📊 Para ver logs por nivel:\n";
    echo "     tail -f storage/logs/laravel.log | grep 'local.INFO.*DIAN'\n";
    echo "     tail -f storage/logs/laravel.log | grep 'local.WARNING.*DIAN'\n";
    echo "     tail -f storage/logs/laravel.log | grep 'local.ERROR.*DIAN'\n";
    
    echo "\n🎊 LOGGING CONFIGURADO EXITOSAMENTE\n";
    echo "Ahora puedes monitorear toda la actividad del módulo DIAN en los logs.\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    Log::error('DIAN Test: Error en prueba de logging', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

echo "\n✅ Prueba de logging completada.\n";
