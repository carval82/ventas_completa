<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

echo "📧 ENVÍO REAL DE BACKUP POR TINKER\n";
echo "==================================\n\n";

// Configurar SMTP directamente para envío real
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
    'name' => 'Sistema de Backups',
]);

echo "🔧 CONFIGURACIÓN SMTP APLICADA:\n";
echo "===============================\n";
echo "Host: smtp.gmail.com\n";
echo "Port: 587\n";
echo "From: interveredanet.cr@gmail.com\n";
echo "To: pcapacho24@gmail.com\n\n";

// Buscar el último backup
$backupsPath = storage_path('app/backups');
$backupFiles = File::files($backupsPath);

if (empty($backupFiles)) {
    echo "❌ No se encontraron archivos de backup\n";
    echo "Ejecuta primero: php artisan backup:database\n";
    exit(1);
}

// Ordenar por fecha de modificación (más reciente primero)
usort($backupFiles, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

$latestBackup = $backupFiles[0];
$filename = basename($latestBackup);
$fileSize = filesize($latestBackup);

echo "📁 ARCHIVO DE BACKUP SELECCIONADO:\n";
echo "==================================\n";
echo "Archivo: $filename\n";
echo "Tamaño: " . round($fileSize / 1024, 2) . " KB\n";
echo "Ruta: $latestBackup\n";
echo "Fecha: " . date('d/m/Y H:i:s', filemtime($latestBackup)) . "\n\n";

// Verificar que el archivo existe y no está vacío
if (!file_exists($latestBackup) || $fileSize === 0) {
    echo "❌ Archivo de backup inválido\n";
    exit(1);
}

echo "🚀 ENVIANDO EMAIL REAL...\n";
echo "=========================\n";

try {
    // Enviar el email usando el template de backup
    Mail::send('emails.backup', [
        'filename' => $filename,
        'size' => round($fileSize / 1024, 2) . ' KB',
        'date' => date('d/m/Y H:i:s')
    ], function ($message) use ($latestBackup, $filename) {
        $message->to('pcapacho24@gmail.com')
                ->subject('🗄️ Backup de Base de Datos - ' . date('d/m/Y H:i:s'))
                ->attach($latestBackup, [
                    'as' => $filename,
                    'mime' => 'application/octet-stream'
                ]);
    });
    
    echo "🎉 ¡EMAIL ENVIADO EXITOSAMENTE!\n";
    echo "===============================\n";
    echo "✅ Destinatario: pcapacho24@gmail.com\n";
    echo "✅ Asunto: 🗄️ Backup de Base de Datos - " . date('d/m/Y H:i:s') . "\n";
    echo "✅ Adjunto: $filename (" . round($fileSize / 1024, 2) . " KB)\n";
    echo "✅ Servidor SMTP: smtp.gmail.com\n";
    echo "✅ Enviado desde: interveredanet.cr@gmail.com\n\n";
    
    echo "📱 VERIFICACIÓN:\n";
    echo "================\n";
    echo "1. Revisa la bandeja de entrada de: pcapacho24@gmail.com\n";
    echo "2. Busca email de: Sistema de Backups <interveredanet.cr@gmail.com>\n";
    echo "3. El email contiene el backup adjunto\n";
    echo "4. Template HTML profesional aplicado\n\n";
    
    echo "🎊 ¡ENVÍO REAL COMPLETADO!\n";
    echo "==========================\n";
    echo "El backup se envió REALMENTE por SMTP\n";
    echo "No es simulación ni log\n";
    echo "Email real entregado al destinatario\n\n";
    
} catch (\Swift_TransportException $e) {
    echo "❌ ERROR DE TRANSPORTE SMTP:\n";
    echo "============================\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    
    if (strpos($e->getMessage(), 'Username and Password not accepted') !== false) {
        echo "🔐 PROBLEMA DE AUTENTICACIÓN:\n";
        echo "=============================\n";
        echo "1. Verifica que la verificación en 2 pasos esté ACTIVADA\n";
        echo "2. Genera una NUEVA contraseña de aplicación\n";
        echo "3. La contraseña actual puede haber expirado\n\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR GENERAL:\n";
    echo "=================\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Tipo: " . get_class($e) . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n\n";
}

echo "📋 COMPARACIÓN:\n";
echo "===============\n";
echo "• Aplicación (artisan): Usa MAIL_MAILER=log (solo logs)\n";
echo "• Este script: Usa SMTP real (envío verdadero)\n";
echo "• Ambos usan el mismo template y lógica\n";
echo "• Solo cambia la configuración de transporte\n\n";

echo "🏁 Envío real por Tinker completado\n";
