<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ConfiguracionDian;
use App\Models\FacturaDianProcesada;
use App\Models\User;
use App\Services\Dian\EmailProcessorService;
use Illuminate\Support\Facades\Log;

echo "=== PRUEBA COMPLETA DEL MÓDULO DIAN ===\n\n";

try {
    // 1. Verificar configuración actual
    echo "🔍 1. VERIFICANDO CONFIGURACIÓN ACTUAL...\n";
    $usuario = User::with('empresa')->first();
    $configuracion = ConfiguracionDian::where('empresa_id', $usuario->empresa->id)->first();
    
    if (!$configuracion) {
        echo "  ❌ No hay configuración DIAN\n";
        exit(1);
    }
    
    echo "  ✅ Configuración encontrada\n";
    echo "    📧 Email: {$configuracion->email_dian}\n";
    echo "    🌐 Servidor: {$configuracion->servidor_imap}:{$configuracion->puerto_imap}\n";
    echo "    ⚡ Activo: " . ($configuracion->activo ? 'Sí' : 'No') . "\n";
    
    // 2. Probar conexión detallada
    echo "\n🔌 2. PRUEBA DETALLADA DE CONEXIÓN IMAP...\n";
    
    Log::info('DIAN Prueba Completa: Iniciando prueba detallada de conexión');
    
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
        
        // Obtener información del buzón
        $info = imap_mailboxmsginfo($connection);
        echo "    📊 Total de mensajes: {$info->Nmsgs}\n";
        echo "    📧 Mensajes no leídos: {$info->Unread}\n";
        echo "    📅 Último mensaje: " . date('Y-m-d H:i:s', $info->Date) . "\n";
        
        Log::info('DIAN Prueba Completa: Información del buzón obtenida', [
            'total_mensajes' => $info->Nmsgs,
            'no_leidos' => $info->Unread,
            'ultimo_mensaje' => date('Y-m-d H:i:s', $info->Date)
        ]);
        
        // Buscar emails recientes
        echo "\n  🔍 Buscando emails recientes...\n";
        $fechaHoy = date('d-M-Y');
        $fechaAyer = date('d-M-Y', strtotime('-1 day'));
        
        // Buscar emails de hoy y ayer
        $busquedaHoy = "SINCE \"{$fechaHoy}\"";
        $busquedaAyer = "SINCE \"{$fechaAyer}\"";
        
        $emailsHoy = @imap_search($connection, $busquedaHoy);
        $emailsAyer = @imap_search($connection, $busquedaAyer);
        
        $countHoy = $emailsHoy ? count($emailsHoy) : 0;
        $countAyer = $emailsAyer ? count($emailsAyer) : 0;
        
        echo "    📅 Emails de hoy: {$countHoy}\n";
        echo "    📅 Emails desde ayer: {$countAyer}\n";
        
        // Buscar emails con palabras clave de facturas
        echo "\n  🔍 Buscando emails con palabras clave de facturas...\n";
        $palabrasClave = ['factura', 'invoice', 'cufe', 'dian', 'electronica'];
        $emailsFacturas = [];
        
        foreach ($palabrasClave as $palabra) {
            $busqueda = "SUBJECT \"{$palabra}\"";
            $resultados = @imap_search($connection, $busqueda);
            if ($resultados) {
                $emailsFacturas = array_merge($emailsFacturas, $resultados);
                echo "    🔍 '{$palabra}': " . count($resultados) . " emails\n";
            }
        }
        
        $emailsFacturas = array_unique($emailsFacturas);
        echo "    📄 Total emails con palabras clave: " . count($emailsFacturas) . "\n";
        
        Log::info('DIAN Prueba Completa: Búsqueda de emails completada', [
            'emails_hoy' => $countHoy,
            'emails_ayer' => $countAyer,
            'emails_facturas' => count($emailsFacturas)
        ]);
        
        // Mostrar algunos emails recientes
        if ($emailsAyer && count($emailsAyer) > 0) {
            echo "\n  📋 ÚLTIMOS 5 EMAILS:\n";
            $ultimosEmails = array_slice(array_reverse($emailsAyer), 0, 5);
            
            foreach ($ultimosEmails as $emailId) {
                $header = imap_headerinfo($connection, $emailId);
                $asunto = $header->subject ?? 'Sin asunto';
                $remitente = $header->from[0]->mailbox . '@' . $header->from[0]->host;
                $fecha = date('Y-m-d H:i:s', $header->udate);
                
                echo "    📧 ID: {$emailId}\n";
                echo "       📝 Asunto: {$asunto}\n";
                echo "       👤 De: {$remitente}\n";
                echo "       📅 Fecha: {$fecha}\n";
                echo "       ---\n";
            }
        }
        
        imap_close($connection);
        
    } else {
        $error = imap_last_error();
        echo "  ❌ Error de conexión: {$error}\n";
        
        Log::error('DIAN Prueba Completa: Error de conexión IMAP', [
            'error' => $error,
            'servidor' => $servidor
        ]);
    }
    
    // 3. Probar procesamiento con EmailProcessorService
    echo "\n📬 3. PRUEBA CON EMAILPROCESSORSERVICE...\n";
    
    Log::info('DIAN Prueba Completa: Iniciando prueba con EmailProcessorService');
    
    $emailProcessor = new EmailProcessorService($configuracion);
    $resultados = $emailProcessor->procesarEmails();
    
    echo "  📊 RESULTADOS DEL PROCESAMIENTO:\n";
    echo "    📧 Emails procesados: {$resultados['emails_procesados']}\n";
    echo "    📄 Facturas encontradas: {$resultados['facturas_encontradas']}\n";
    echo "    ❌ Errores: " . count($resultados['errores']) . "\n";
    
    if (!empty($resultados['errores'])) {
        echo "\n  🔍 ERRORES ENCONTRADOS:\n";
        foreach ($resultados['errores'] as $i => $error) {
            echo "    " . ($i + 1) . ". {$error}\n";
        }
    }
    
    if (!empty($resultados['facturas_procesadas'])) {
        echo "\n  🎉 FACTURAS PROCESADAS:\n";
        foreach ($resultados['facturas_procesadas'] as $i => $factura) {
            echo "    " . ($i + 1) . ". ID: {$factura->id} - {$factura->asunto_email}\n";
        }
    }
    
    Log::info('DIAN Prueba Completa: EmailProcessorService completado', $resultados);
    
    // 4. Verificar base de datos
    echo "\n💾 4. VERIFICANDO BASE DE DATOS...\n";
    
    $totalFacturas = FacturaDianProcesada::where('empresa_id', $usuario->empresa->id)->count();
    $facturasHoy = FacturaDianProcesada::where('empresa_id', $usuario->empresa->id)
                                      ->whereDate('created_at', today())
                                      ->count();
    
    echo "  📊 Total facturas en BD: {$totalFacturas}\n";
    echo "  📅 Facturas de hoy: {$facturasHoy}\n";
    
    if ($totalFacturas > 0) {
        $ultimasFacturas = FacturaDianProcesada::where('empresa_id', $usuario->empresa->id)
                                              ->orderBy('created_at', 'desc')
                                              ->limit(3)
                                              ->get();
        
        echo "\n  📋 ÚLTIMAS 3 FACTURAS PROCESADAS:\n";
        foreach ($ultimasFacturas as $i => $factura) {
            echo "    " . ($i + 1) . ". {$factura->asunto_email}\n";
            echo "       📅 {$factura->created_at}\n";
            echo "       📧 {$factura->remitente_email}\n";
            echo "       ⚡ Estado: {$factura->estado}\n";
            echo "       ---\n";
        }
    }
    
    // 5. Estado final y recomendaciones
    echo "\n🎯 5. ESTADO FINAL Y RECOMENDACIONES:\n";
    
    if ($connection) {
        echo "  ✅ Conexión IMAP: FUNCIONANDO\n";
    } else {
        echo "  ❌ Conexión IMAP: CON PROBLEMAS\n";
    }
    
    echo "  ✅ Configuración: COMPLETA\n";
    echo "  ✅ Logging: HABILITADO\n";
    echo "  ✅ Base de datos: FUNCIONANDO\n";
    
    echo "\n📋 PRÓXIMOS PASOS:\n";
    echo "  1. 🌐 Accede al dashboard: http://127.0.0.1:8000/dian\n";
    echo "  2. 📊 Monitorea los logs: tail -f storage/logs/laravel.log | grep DIAN\n";
    echo "  3. 📧 Envía facturas de prueba a: pcapacho24@gmail.com\n";
    echo "  4. ⚡ Usa 'Procesar Emails' desde el dashboard\n";
    echo "  5. 🔄 Configura procesamiento automático si es necesario\n";
    
    Log::info('DIAN Prueba Completa: Prueba completa finalizada exitosamente', [
        'conexion_exitosa' => isset($connection) && $connection,
        'total_facturas_bd' => $totalFacturas,
        'emails_procesados' => $resultados['emails_procesados']
    ]);
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    
    Log::error('DIAN Prueba Completa: Error en prueba completa', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

echo "\n✅ Prueba completa finalizada.\n";
