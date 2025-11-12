<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\File;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

echo "📧 ENVÍO DIRECTO DE BACKUP (SIN LARAVEL MAIL)\n";
echo "=============================================\n\n";

// Buscar el último backup
$backupsPath = storage_path('app/backups');
$backupFiles = File::files($backupsPath);

if (empty($backupFiles)) {
    echo "❌ No se encontraron archivos de backup\n";
    exit(1);
}

// Ordenar por fecha de modificación (más reciente primero)
usort($backupFiles, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

$latestBackup = $backupFiles[0];
$filename = basename($latestBackup);
$fileSize = filesize($latestBackup);

echo "📁 ARCHIVO SELECCIONADO:\n";
echo "========================\n";
echo "Archivo: $filename\n";
echo "Tamaño: " . round($fileSize / 1024, 2) . " KB\n";
echo "Fecha: " . date('d/m/Y H:i:s', filemtime($latestBackup)) . "\n\n";

echo "🔧 CONFIGURANDO TRANSPORTE SMTP DIRECTO:\n";
echo "========================================\n";

try {
    // Crear transporte SMTP directo
    $transport = new EsmtpTransport('smtp.gmail.com', 587, true);
    $transport->setUsername('interveredanet.cr@gmail.com');
    $transport->setPassword('jiiiy yxnu itis xjru');
    
    $mailer = new Mailer($transport);
    
    echo "✅ Transporte SMTP configurado\n";
    echo "Host: smtp.gmail.com:587\n";
    echo "Usuario: interveredanet.cr@gmail.com\n\n";
    
    // Crear el contenido HTML del email
    $htmlContent = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Backup de Base de Datos</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background: #007bff; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f8f9fa; }
            .info { background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>🗄️ Backup de Base de Datos</h1>
            <p>Sistema de Ventas e Inventario</p>
        </div>
        <div class='content'>
            <p>Se ha generado un nuevo backup de la base de datos.</p>
            <div class='info'>
                <strong>📁 Archivo:</strong> $filename<br>
                <strong>📊 Tamaño:</strong> " . round($fileSize / 1024, 2) . " KB<br>
                <strong>📅 Fecha:</strong> " . date('d/m/Y H:i:s') . "<br>
            </div>
            <p><strong>📎 El backup se encuentra adjunto a este email.</strong></p>
            <p>Este es un envío REAL usando SMTP directo (no simulación).</p>
        </div>
    </body>
    </html>";
    
    // Crear el email
    $email = (new Email())
        ->from('interveredanet.cr@gmail.com')
        ->to('pcapacho24@gmail.com')
        ->subject('🗄️ Backup Real - ' . date('d/m/Y H:i:s'))
        ->html($htmlContent);
    
    // Adjuntar el archivo de backup
    $email->addPart(new DataPart(
        file_get_contents($latestBackup),
        $filename,
        'application/octet-stream'
    ));
    
    echo "📧 ENVIANDO EMAIL REAL...\n";
    echo "=========================\n";
    
    // Enviar el email
    $mailer->send($email);
    
    echo "🎉 ¡EMAIL ENVIADO EXITOSAMENTE!\n";
    echo "===============================\n";
    echo "✅ Método: SMTP directo (Symfony Mailer)\n";
    echo "✅ Destinatario: pcapacho24@gmail.com\n";
    echo "✅ Adjunto: $filename (" . round($fileSize / 1024, 2) . " KB)\n";
    echo "✅ Servidor: smtp.gmail.com:587\n";
    echo "✅ Desde: interveredanet.cr@gmail.com\n\n";
    
    echo "📱 VERIFICACIÓN:\n";
    echo "================\n";
    echo "1. Revisa la bandeja de entrada de pcapacho24@gmail.com\n";
    echo "2. Busca email con asunto: 🗄️ Backup Real - " . date('d/m/Y H:i:s') . "\n";
    echo "3. El email contiene el backup adjunto\n";
    echo "4. Este es un envío REAL, no simulación\n\n";
    
    echo "🎊 ¡ENVÍO REAL COMPLETADO CON ÉXITO!\n";
    echo "====================================\n";
    echo "El backup se envió REALMENTE por SMTP\n";
    echo "Email entregado al destinatario real\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR EN ENVÍO:\n";
    echo "==================\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Tipo: " . get_class($e) . "\n\n";
    
    if (strpos($e->getMessage(), 'Authentication') !== false || 
        strpos($e->getMessage(), 'Username and Password') !== false) {
        echo "🔐 PROBLEMA DE AUTENTICACIÓN GMAIL:\n";
        echo "===================================\n";
        echo "1. Verifica verificación en 2 pasos ACTIVADA\n";
        echo "2. Genera NUEVA contraseña de aplicación\n";
        echo "3. La contraseña actual puede haber expirado\n";
        echo "4. Ve a: https://myaccount.google.com/security\n\n";
        
        echo "🔄 ALTERNATIVA - USAR MAILTRAP:\n";
        echo "===============================\n";
        echo "1. Regístrate en https://mailtrap.io\n";
        echo "2. Crea un inbox de testing\n";
        echo "3. Usa las credenciales SMTP de Mailtrap\n";
        echo "4. Los emails se capturarán para testing\n";
    }
}

echo "\n🏁 Envío directo completado\n";
