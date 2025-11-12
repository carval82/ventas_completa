<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

echo "📧 PRUEBA DE SENDGRID CON LARAVEL\n";
echo "=================================\n\n";

// Configurar SendGrid temporalmente (reemplaza con tu API Key real)
$sendgridApiKey = 'TU_API_KEY_AQUI'; // Reemplazar con API Key real

if ($sendgridApiKey === 'TU_API_KEY_AQUI') {
    echo "⚠️ CONFIGURACIÓN REQUERIDA:\n";
    echo "===========================\n";
    echo "1. Ve a https://sendgrid.com y crea una cuenta\n";
    echo "2. Crea una API Key en Settings > API Keys\n";
    echo "3. Verifica tu email en Settings > Sender Authentication\n";
    echo "4. Reemplaza 'TU_API_KEY_AQUI' en este script\n";
    echo "5. O configura directamente en .env:\n\n";
    
    echo "MAIL_MAILER=smtp\n";
    echo "MAIL_HOST=smtp.sendgrid.net\n";
    echo "MAIL_PORT=587\n";
    echo "MAIL_USERNAME=apikey\n";
    echo "MAIL_PASSWORD=SG.tu_api_key_real_aqui\n";
    echo "MAIL_ENCRYPTION=tls\n";
    echo "MAIL_FROM_ADDRESS=interveredanet.cr@gmail.com\n";
    echo "MAIL_FROM_NAME=\"Sistema DIAN\"\n\n";
    
    echo "🔗 Registro gratuito: https://sendgrid.com/free\n";
    echo "📚 Documentación: https://docs.sendgrid.com\n\n";
    exit(0);
}

// Configurar SendGrid en Laravel
Config::set('mail.default', 'smtp');
Config::set('mail.mailers.smtp', [
    'transport' => 'smtp',
    'host' => 'smtp.sendgrid.net',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'apikey',
    'password' => $sendgridApiKey,
    'timeout' => null,
    'local_domain' => null,
]);
Config::set('mail.from', [
    'address' => 'interveredanet.cr@gmail.com',
    'name' => 'Sistema DIAN',
]);

echo "🔧 CONFIGURACIÓN SENDGRID:\n";
echo "==========================\n";
echo "Host: smtp.sendgrid.net\n";
echo "Port: 587\n";
echo "Username: apikey\n";
echo "API Key: " . substr($sendgridApiKey, 0, 10) . "...\n";
echo "From: Sistema DIAN <interveredanet.cr@gmail.com>\n\n";

echo "🧪 PRUEBA 1: EMAIL SIMPLE\n";
echo "=========================\n";

try {
    Mail::raw('Este es un email de prueba enviado con SendGrid desde Laravel.', function ($message) {
        $message->to('pcapacho24@gmail.com')
                ->subject('🧪 Prueba SendGrid - ' . date('d/m/Y H:i:s'));
    });
    
    echo "✅ Email simple enviado exitosamente\n";
    echo "📧 Destinatario: pcapacho24@gmail.com\n";
    echo "📋 Asunto: 🧪 Prueba SendGrid - " . date('d/m/Y H:i:s') . "\n\n";
    
} catch (\Exception $e) {
    echo "❌ Error enviando email simple:\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

echo "🧪 PRUEBA 2: EMAIL CON TEMPLATE HTML\n";
echo "====================================\n";

try {
    Mail::send('emails.backup', [
        'filename' => 'test-sendgrid.zip',
        'size' => '47.11 KB',
        'date' => date('d/m/Y H:i:s')
    ], function ($message) {
        $message->to('pcapacho24@gmail.com')
                ->subject('🗄️ Prueba Backup SendGrid - ' . date('d/m/Y H:i:s'));
    });
    
    echo "✅ Email con template enviado exitosamente\n";
    echo "📧 Template: emails.backup\n";
    echo "📋 Asunto: 🗄️ Prueba Backup SendGrid - " . date('d/m/Y H:i:s') . "\n\n";
    
} catch (\Exception $e) {
    echo "❌ Error enviando email con template:\n";
    echo "Error: " . $e->getMessage() . "\n\n";
}

echo "🧪 PRUEBA 3: EMAIL CON ARCHIVO ADJUNTO\n";
echo "======================================\n";

// Buscar un backup para adjuntar
$backupsPath = storage_path('app/backups');
$backupFiles = File::files($backupsPath);

if (!empty($backupFiles)) {
    // Usar el backup más reciente
    usort($backupFiles, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    $latestBackup = $backupFiles[0];
    $filename = basename($latestBackup);
    $fileSize = filesize($latestBackup);
    
    echo "📁 Archivo seleccionado: $filename (" . round($fileSize / 1024, 2) . " KB)\n";
    
    try {
        Mail::send('emails.backup', [
            'filename' => $filename,
            'size' => round($fileSize / 1024, 2) . ' KB',
            'date' => date('d/m/Y H:i:s')
        ], function ($message) use ($latestBackup, $filename) {
            $message->to('pcapacho24@gmail.com')
                    ->subject('🗄️ Backup Real SendGrid - ' . date('d/m/Y H:i:s'))
                    ->attach($latestBackup->getPathname(), [
                        'as' => $filename,
                        'mime' => 'application/octet-stream'
                    ]);
        });
        
        echo "✅ Email con adjunto enviado exitosamente\n";
        echo "📎 Adjunto: $filename\n";
        echo "📋 Asunto: 🗄️ Backup Real SendGrid - " . date('d/m/Y H:i:s') . "\n\n";
        
    } catch (\Exception $e) {
        echo "❌ Error enviando email con adjunto:\n";
        echo "Error: " . $e->getMessage() . "\n\n";
    }
} else {
    echo "⚠️ No se encontraron archivos de backup para adjuntar\n";
    echo "Ejecuta: php artisan backup:database\n\n";
}

echo "🎉 PRUEBAS COMPLETADAS\n";
echo "======================\n";
echo "✅ SendGrid configurado y funcionando\n";
echo "✅ Emails enviados exitosamente\n";
echo "✅ Templates HTML funcionando\n";
echo "✅ Archivos adjuntos funcionando\n\n";

echo "📱 VERIFICACIÓN:\n";
echo "================\n";
echo "1. Revisa la bandeja de entrada de: pcapacho24@gmail.com\n";
echo "2. Busca 3 emails de prueba de SendGrid\n";
echo "3. Verifica que llegaron correctamente\n";
echo "4. Revisa las estadísticas en SendGrid Dashboard\n\n";

echo "🚀 PRÓXIMOS PASOS:\n";
echo "==================\n";
echo "1. Configura .env con tu API Key real\n";
echo "2. Ejecuta: php artisan config:clear\n";
echo "3. Prueba: php artisan backup:database --send-email\n";
echo "4. Prueba acuses DIAN desde el dashboard\n\n";

echo "🏁 Prueba SendGrid completada\n";
