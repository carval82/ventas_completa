<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ConfiguracionDian;
use App\Models\EmailBuzon;
use App\Services\Dian\BuzonEmailService;
use App\Mail\AcuseReciboMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

echo "📧 PRUEBA DE ENVÍO REAL DE ACUSES\n";
echo "=================================\n\n";

// Verificar configuración de email
echo "🔧 VERIFICANDO CONFIGURACIÓN DE EMAIL:\n";
echo "======================================\n";
echo "MAIL_MAILER: " . config('mail.default') . "\n";
echo "MAIL_HOST: " . config('mail.mailers.smtp.host') . "\n";
echo "MAIL_PORT: " . config('mail.mailers.smtp.port') . "\n";
echo "MAIL_USERNAME: " . config('mail.mailers.smtp.username') . "\n";
echo "MAIL_FROM: " . config('mail.from.address') . "\n\n";

if (config('mail.default') === 'log') {
    echo "⚠️ ADVERTENCIA: El mailer está configurado como 'log'\n";
    echo "Los emails se guardarán en storage/logs/laravel.log pero NO se enviarán realmente\n";
    echo "Para envío real, configura SMTP en .env\n\n";
    
    echo "¿Continuar con modo LOG para testing? (s/n): ";
    $handle = fopen("php://stdin", "r");
    $respuesta = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($respuesta) !== 's' && strtolower($respuesta) !== 'si') {
        echo "❌ Configuración cancelada. Ejecuta: php configurar_email_real.php\n";
        exit;
    }
}

$config = ConfiguracionDian::where('activo', true)->first();
$empresa = $config->empresa;

echo "🏢 Empresa: " . $empresa->nombre . "\n";
echo "📧 Email configurado: " . $config->email_dian . "\n\n";

// Crear email de prueba para envío real
echo "📧 Creando email de prueba para ENVÍO REAL...\n";

$emailPrueba = EmailBuzon::create([
    'empresa_id' => $empresa->id,
    'mensaje_id' => 'ENVIO_REAL_' . time(),
    'cuenta_email' => $config->email_dian,
    'remitente_email' => 'facturacion@agrosander.com',
    'remitente_nombre' => 'Agrosander Don Jorge S A S',
    'asunto' => 'Factura Electrónica REAL - Prueba Envío',
    'contenido_texto' => 'Email de prueba para envío REAL de acuse de recibo...',
    'fecha_email' => now(),
    'fecha_descarga' => now(),
    'archivos_adjuntos' => [
        ['nombre' => 'REAL-TEST-001.xml', 'tamaño' => 35420, 'es_factura' => true],
        ['nombre' => 'REAL-TEST-001.pdf', 'tamaño' => 155630, 'es_factura' => false]
    ],
    'tiene_facturas' => true,
    'procesado' => false,
    'estado' => 'nuevo',
    'metadatos' => [
        'tipo' => 'email_envio_real',
        'test_real' => true
    ]
]);

echo "✅ Email de prueba creado: ID #{$emailPrueba->id}\n\n";

// Opción 1: Envío directo de acuse
echo "🎯 OPCIÓN 1: ENVÍO DIRECTO DE ACUSE\n";
echo "===================================\n";
echo "¿Enviar acuse directamente a un email específico? (s/n): ";
$handle = fopen("php://stdin", "r");
$envioDirecto = trim(fgets($handle));
fclose($handle);

if (strtolower($envioDirecto) === 's' || strtolower($envioDirecto) === 'si') {
    echo "Ingresa el email destinatario: ";
    $handle = fopen("php://stdin", "r");
    $emailDestinatario = trim(fgets($handle));
    fclose($handle);
    
    if (filter_var($emailDestinatario, FILTER_VALIDATE_EMAIL)) {
        echo "\n📧 ENVIANDO ACUSE DIRECTO A: $emailDestinatario\n";
        echo "===============================================\n";
        
        try {
            // Datos de prueba para el acuse
            $datosFactura = [
                'cufe' => 'CUFE-REAL-TEST-' . strtoupper(uniqid()),
                'numero_factura' => 'REAL-TEST-001',
                'fecha_factura' => now()->format('Y-m-d'),
                'proveedor' => [
                    'nombre' => 'Agrosander Don Jorge S A S',
                    'nit' => '900591105',
                    'email' => 'agrosandersas@gmail.com'
                ],
                'cliente' => [
                    'nombre' => $empresa->nombre,
                    'nit' => $empresa->nit,
                    'email' => $config->email_dian
                ],
                'totales' => [
                    'subtotal' => 191333,
                    'iva' => 9567,
                    'total' => 200900
                ],
                'email_proveedor' => $emailDestinatario,
                'email_cliente' => $config->email_dian
            ];
            
            // Enviar acuse real
            Mail::to($emailDestinatario)->send(new AcuseReciboMail($emailPrueba, $datosFactura, $empresa));
            
            echo "✅ ACUSE ENVIADO EXITOSAMENTE\n";
            echo "📧 Destinatario: $emailDestinatario\n";
            echo "📄 Factura: REAL-TEST-001\n";
            echo "🔑 CUFE: {$datosFactura['cufe']}\n";
            echo "💰 Total: $" . number_format($datosFactura['totales']['total'], 2) . "\n\n";
            
            if (config('mail.default') === 'log') {
                echo "📝 NOTA: Email guardado en storage/logs/laravel.log\n";
                echo "Para envío real, configura SMTP\n\n";
            } else {
                echo "📨 EMAIL ENVIADO REALMENTE\n";
                echo "Revisa la bandeja de entrada de: $emailDestinatario\n\n";
            }
            
        } catch (\Exception $e) {
            echo "❌ ERROR ENVIANDO ACUSE: " . $e->getMessage() . "\n\n";
        }
    } else {
        echo "❌ Email no válido\n\n";
    }
}

// Opción 2: Procesamiento completo
echo "🎯 OPCIÓN 2: PROCESAMIENTO COMPLETO\n";
echo "===================================\n";
echo "¿Procesar email completo con el sistema? (s/n): ";
$handle = fopen("php://stdin", "r");
$procesamientoCompleto = trim(fgets($handle));
fclose($handle);

if (strtolower($procesamientoCompleto) === 's' || strtolower($procesamientoCompleto) === 'si') {
    echo "\n🚀 PROCESANDO EMAIL COMPLETO:\n";
    echo "=============================\n";
    
    $buzonService = new BuzonEmailService($config);
    $resultado = $buzonService->procesarEmailsDelBuzon();
    
    echo "📊 RESULTADOS:\n";
    echo "==============\n";
    echo "✅ Éxito: " . ($resultado['success'] ? 'SÍ' : 'NO') . "\n";
    echo "📧 Emails procesados: " . ($resultado['emails_procesados'] ?? 0) . "\n";
    echo "💼 Facturas extraídas: " . ($resultado['facturas_extraidas'] ?? 0) . "\n";
    echo "📨 Acuses generados: " . ($resultado['acuses_generados'] ?? 0) . "\n";
    echo "❌ Errores: " . ($resultado['errores'] ?? 0) . "\n\n";
    
    // Verificar el email procesado
    $emailActualizado = EmailBuzon::find($emailPrueba->id);
    
    if (isset($emailActualizado->metadatos['acuse_enviado'])) {
        $acuse = $emailActualizado->metadatos['acuse_enviado'];
        echo "📨 ACUSE ENVIADO:\n";
        echo "=================\n";
        echo "📧 Destinatario: {$acuse['destinatario']}\n";
        echo "📄 Factura: {$acuse['numero_factura']}\n";
        echo "🔑 CUFE: {$acuse['cufe']}\n";
        echo "📅 Fecha: {$acuse['fecha']}\n";
        echo "📤 Método: {$acuse['metodo']}\n\n";
    }
}

echo "📋 INFORMACIÓN ADICIONAL:\n";
echo "=========================\n";
echo "• Para configurar Gmail SMTP: php configurar_email_real.php\n";
echo "• Para ver logs: Get-Content storage/logs/laravel.log -Tail 20\n";
echo "• Dashboard: http://127.0.0.1:8000/dian\n";
echo "• Buzón: http://127.0.0.1:8000/dian/buzon\n\n";

echo "🏁 Prueba de envío real completada\n";
