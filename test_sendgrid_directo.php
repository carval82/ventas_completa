<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

echo "🧪 PRUEBA DIRECTA DE SENDGRID\n";
echo "=============================\n\n";

// API Key de SendGrid
$apiKey = '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';

echo "🔑 API Key: " . substr($apiKey, 0, 15) . "... (Longitud: " . strlen($apiKey) . ")\n\n";

// Configurar Laravel Mail para SendGrid
Config::set('mail.default', 'sendgrid');
Config::set('mail.mailers.sendgrid', [
    'transport' => 'smtp',
    'host' => 'smtp.sendgrid.net',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'apikey',
    'password' => $apiKey,
    'timeout' => null,
    'local_domain' => null,
]);

Config::set('mail.from', [
    'address' => 'interveredanet.cr@gmail.com',
    'name' => 'Sistema DIAN'
]);

echo "📧 CONFIGURACIÓN APLICADA:\n";
echo "==========================\n";
echo "Host: smtp.sendgrid.net\n";
echo "Port: 587\n";
echo "Username: apikey\n";
echo "From: Sistema DIAN <interveredanet.cr@gmail.com>\n\n";

echo "⚠️ IMPORTANTE:\n";
echo "==============\n";
echo "Antes de continuar, asegúrate de que:\n";
echo "1. El email 'interveredanet.cr@gmail.com' esté verificado en SendGrid\n";
echo "2. Ve a: https://app.sendgrid.com/settings/sender_auth\n";
echo "3. Verifica que aparezca como 'Verified'\n\n";

echo "¿Continuar con la prueba? (s/n): ";
$handle = fopen("php://stdin", "r");
$continuar = trim(fgets($handle));
fclose($handle);

if (strtolower($continuar) !== 's' && strtolower($continuar) !== 'si') {
    echo "❌ Prueba cancelada\n";
    echo "💡 Verifica el email en SendGrid y vuelve a ejecutar\n";
    exit(0);
}

echo "\n🚀 ENVIANDO EMAIL DE PRUEBA...\n";
echo "==============================\n";

try {
    Mail::raw('Este es un email de prueba enviado directamente con SendGrid desde Laravel.', function ($message) {
        $message->to('pcapacho24@gmail.com')
                ->subject('🧪 Prueba SendGrid Directa - ' . date('d/m/Y H:i:s'));
    });
    
    echo "✅ Email enviado exitosamente!\n";
    echo "📧 Destinatario: pcapacho24@gmail.com\n";
    echo "📋 Asunto: 🧪 Prueba SendGrid Directa - " . date('d/m/Y H:i:s') . "\n\n";
    
    echo "🎉 SENDGRID FUNCIONANDO CORRECTAMENTE\n";
    echo "====================================\n";
    echo "✅ Autenticación exitosa\n";
    echo "✅ Email enviado\n";
    echo "✅ Configuración válida\n\n";
    
    echo "📱 VERIFICACIÓN:\n";
    echo "================\n";
    echo "1. Revisa la bandeja de entrada de: pcapacho24@gmail.com\n";
    echo "2. Busca el email de prueba\n";
    echo "3. Verifica las estadísticas en SendGrid Dashboard\n\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR ENVIANDO EMAIL:\n";
    echo "========================\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    
    echo "🔍 POSIBLES CAUSAS:\n";
    echo "==================\n";
    echo "1. ❌ Email remitente no verificado en SendGrid\n";
    echo "2. ❌ API Key inválida o expirada\n";
    echo "3. ❌ Límite diario de SendGrid alcanzado\n";
    echo "4. ❌ Problemas de conectividad\n\n";
    
    echo "💡 SOLUCIONES:\n";
    echo "==============\n";
    echo "1. Ve a https://app.sendgrid.com/settings/sender_auth\n";
    echo "2. Verifica el email: interveredanet.cr@gmail.com\n";
    echo "3. Revisa tu API Key en Settings > API Keys\n";
    echo "4. Verifica límites en Dashboard > Activity\n";
}

echo "\n🏁 Prueba completada\n";
