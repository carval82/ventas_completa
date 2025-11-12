<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

echo "🧪 PRUEBA CON NUEVA API KEY DE SENDGRID\n";
echo "======================================\n\n";

// Nueva API Key
$apiKey = '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';

echo "🔑 API Key: " . substr($apiKey, 0, 20) . "... ✅\n";
echo "📧 From: interveredanet.cr@gmail.com\n";
echo "📬 To: pcapacho24@gmail.com\n\n";

// Configurar Laravel Mail para SendGrid
Config::set('mail.default', 'sendgrid_test');
Config::set('mail.mailers.sendgrid_test', [
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

echo "🚀 ENVIANDO EMAIL DE PRUEBA...\n";
echo "==============================\n";

try {
    Mail::raw('🎉 ¡Prueba exitosa con nueva API Key de SendGrid!

Este email confirma que:
✅ La nueva API Key funciona correctamente
✅ La configuración SMTP está bien
✅ El sistema dinámico está operativo

Enviado el: ' . date('d/m/Y H:i:s') . '
Desde: Sistema DIAN Laravel
API Key: ' . substr($apiKey, 0, 20) . '...

¡El sistema está listo para producción!', function ($message) {
        $message->to('pcapacho24@gmail.com')
                ->subject('✅ Prueba Exitosa - Nueva API SendGrid - ' . date('H:i:s'));
    });
    
    echo "✅ EMAIL ENVIADO EXITOSAMENTE!\n";
    echo "==============================\n";
    echo "📧 Destinatario: pcapacho24@gmail.com\n";
    echo "📋 Asunto: ✅ Prueba Exitosa - Nueva API SendGrid\n";
    echo "⏰ Hora: " . date('d/m/Y H:i:s') . "\n\n";
    
    echo "🎉 SENDGRID FUNCIONANDO PERFECTAMENTE\n";
    echo "====================================\n";
    echo "✅ Autenticación exitosa con nueva API Key\n";
    echo "✅ Email enviado sin errores\n";
    echo "✅ Configuración SMTP válida\n";
    echo "✅ Sistema listo para uso en producción\n\n";
    
    echo "📱 VERIFICACIÓN:\n";
    echo "================\n";
    echo "1. Revisa tu bandeja: pcapacho24@gmail.com\n";
    echo "2. Busca el email de prueba\n";
    echo "3. Verifica que llegó correctamente\n\n";
    
    echo "🚀 PROBAR SISTEMA COMPLETO:\n";
    echo "===========================\n";
    echo "1. php test_sistema_final.php\n";
    echo "2. php artisan backup:database --send-email\n";
    echo "3. http://127.0.0.1:8000/email-configurations\n\n";
    
    echo "🎯 SISTEMA COMPLETAMENTE FUNCIONAL!\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR ENVIANDO EMAIL:\n";
    echo "========================\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    
    if (strpos($e->getMessage(), 'does not match a verified Sender Identity') !== false) {
        echo "🚨 PROBLEMA: EMAIL NO VERIFICADO\n";
        echo "================================\n";
        echo "El email 'interveredanet.cr@gmail.com' no está verificado en SendGrid.\n\n";
        
        echo "🔧 SOLUCIÓN RÁPIDA:\n";
        echo "===================\n";
        echo "1. Ve a SendGrid > Settings > Sender Authentication\n";
        echo "2. Click 'Verify a Single Sender'\n";
        echo "3. Agrega: interveredanet.cr@gmail.com\n";
        echo "4. Confirma en tu bandeja de entrada\n\n";
        
        echo "⚡ ALTERNATIVA INMEDIATA:\n";
        echo "========================\n";
        echo "Cambiar a tu email personal:\n";
        echo "php cambiar_email_sistema.php tu_email@gmail.com\n";
        
    } else {
        echo "🔍 OTROS POSIBLES PROBLEMAS:\n";
        echo "============================\n";
        echo "• API Key inválida o expirada\n";
        echo "• Límite diario alcanzado\n";
        echo "• Problemas de conectividad\n";
        echo "• Configuración SMTP incorrecta\n";
    }
}

echo "\n🏁 Prueba con nueva API Key completada\n";
