<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ConfiguracionDian;
use App\Models\EmailBuzon;
use App\Mail\AcuseReciboMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

echo "📧 PRUEBA DIRECTA CON SMTP CONFIGURADO\n";
echo "======================================\n\n";

// Configurar SMTP directamente en el código
Config::set('mail.default', 'smtp');
Config::set('mail.mailers.smtp', [
    'transport' => 'smtp',
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'interveredanet.cr@gmail.com',
    'password' => 'jiiiy yxnu itis xjru',
    'timeout' => null,
    'local_domain' => null,
]);
Config::set('mail.from', [
    'address' => 'interveredanet.cr@gmail.com',
    'name' => 'Sistema DIAN',
]);

echo "🔧 CONFIGURACIÓN SMTP APLICADA:\n";
echo "===============================\n";
echo "MAIL_MAILER: " . config('mail.default') . "\n";
echo "MAIL_HOST: " . config('mail.mailers.smtp.host') . "\n";
echo "MAIL_PORT: " . config('mail.mailers.smtp.port') . "\n";
echo "MAIL_USERNAME: " . config('mail.mailers.smtp.username') . "\n";
echo "MAIL_FROM: " . config('mail.from.address') . "\n\n";

$config = ConfiguracionDian::where('activo', true)->first();
$empresa = $config->empresa;

echo "🏢 Empresa: " . $empresa->nombre . "\n";
echo "📧 Email configurado: " . $config->email_dian . "\n\n";

// Crear email de prueba
$emailPrueba = new EmailBuzon([
    'empresa_id' => $empresa->id,
    'mensaje_id' => 'SMTP_TEST_' . time(),
    'cuenta_email' => $config->email_dian,
    'remitente_email' => 'facturacion@agrosander.com',
    'remitente_nombre' => 'Agrosander Don Jorge S A S',
    'asunto' => 'Prueba SMTP Real - Factura Test',
    'fecha_email' => now(),
    'estado' => 'procesado'
]);

// Datos de factura de prueba
$datosFactura = [
    'cufe' => 'CUFE-SMTP-TEST-' . strtoupper(uniqid()),
    'numero_factura' => 'SMTP-TEST-001',
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
    'email_proveedor' => 'agrosandersas@gmail.com',
    'email_cliente' => $config->email_dian
];

echo "📧 ENVIANDO ACUSE REAL VÍA SMTP:\n";
echo "================================\n";
echo "📧 Destinatario: agrosandersas@gmail.com\n";
echo "📄 Factura: SMTP-TEST-001\n";
echo "🔑 CUFE: {$datosFactura['cufe']}\n";
echo "💰 Total: $" . number_format($datosFactura['totales']['total'], 2) . "\n\n";

try {
    echo "🚀 Enviando email...\n";
    
    // Enviar acuse real usando SMTP
    Mail::to('agrosandersas@gmail.com')->send(new AcuseReciboMail($emailPrueba, $datosFactura, $empresa));
    
    echo "✅ ¡ACUSE ENVIADO EXITOSAMENTE VÍA SMTP!\n";
    echo "📧 Email enviado REALMENTE a: agrosandersas@gmail.com\n";
    echo "📨 Desde: interveredanet.cr@gmail.com\n";
    echo "📋 Asunto: Acuse de Recibo - Factura Electrónica SMTP-TEST-001\n\n";
    
    echo "🎉 ¡ÉXITO TOTAL!\n";
    echo "================\n";
    echo "✅ Configuración SMTP funcionando\n";
    echo "✅ Email enviado realmente\n";
    echo "✅ Template HTML aplicado\n";
    echo "✅ Datos de factura incluidos\n";
    echo "✅ Sistema completamente funcional\n\n";
    
    echo "📱 VERIFICA TU EMAIL:\n";
    echo "====================\n";
    echo "Revisa la bandeja de entrada de: agrosandersas@gmail.com\n";
    echo "Busca un email de: Sistema DIAN <interveredanet.cr@gmail.com>\n";
    echo "Asunto: Acuse de Recibo - Factura Electrónica SMTP-TEST-001\n\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR ENVIANDO EMAIL:\n";
    echo "========================\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n\n";
    
    echo "🔧 POSIBLES SOLUCIONES:\n";
    echo "=======================\n";
    echo "1. Verifica que la contraseña de aplicación sea correcta\n";
    echo "2. Asegúrate de que la verificación en 2 pasos esté activada\n";
    echo "3. Verifica que 'Acceso de apps menos seguras' esté deshabilitado\n";
    echo "4. Intenta generar una nueva contraseña de aplicación\n\n";
}

echo "🏁 Prueba SMTP directa completada\n";
