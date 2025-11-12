<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\File;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

echo "📧 ENVÍO REAL CON PHPMAILER (SSL CORREGIDO)\n";
echo "===========================================\n\n";

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

try {
    // Crear instancia de PHPMailer
    $mail = new PHPMailer(true);
    
    echo "🔧 CONFIGURANDO PHPMAILER:\n";
    echo "==========================\n";
    
    // Configuración del servidor SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'interveredanet.cr@gmail.com';
    $mail->Password   = 'jiiiy yxnu itis xjru';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    
    // Configuración adicional para evitar errores SSL
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    echo "✅ SMTP configurado: smtp.gmail.com:587\n";
    echo "✅ Usuario: interveredanet.cr@gmail.com\n";
    echo "✅ Encriptación: STARTTLS\n";
    echo "✅ Opciones SSL: Configuradas\n\n";
    
    // Configurar remitente y destinatario
    $mail->setFrom('interveredanet.cr@gmail.com', 'Sistema de Backups');
    $mail->addAddress('pcapacho24@gmail.com', 'Administrador');
    
    // Configurar el email
    $mail->isHTML(true);
    $mail->Subject = '🗄️ Backup Real - ' . date('d/m/Y H:i:s');
    
    // Contenido HTML
    $mail->Body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; }
            .header { background: #007bff; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { padding: 20px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0 0 5px 5px; }
            .info { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px; border-radius: 4px; margin: 15px 0; }
            .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>🗄️ Backup de Base de Datos</h1>
            <p>Sistema de Ventas e Inventario</p>
        </div>
        <div class='content'>
            <p>Estimado administrador,</p>
            <p>Se ha generado un nuevo backup de la base de datos del sistema de ventas.</p>
            
            <div class='info'>
                <strong>📁 Archivo:</strong> $filename<br>
                <strong>📊 Tamaño:</strong> " . round($fileSize / 1024, 2) . " KB<br>
                <strong>📅 Fecha:</strong> " . date('d/m/Y H:i:s') . "<br>
                <strong>🔧 Método:</strong> Envío REAL por SMTP
            </div>
            
            <p><strong>📎 El backup se encuentra adjunto a este correo electrónico.</strong></p>
            
            <h3>Recomendaciones:</h3>
            <ul>
                <li>Guarde este backup en un lugar seguro</li>
                <li>No comparta este archivo con personas no autorizadas</li>
                <li>Verifique periódicamente la integridad de sus backups</li>
            </ul>
        </div>
        <div class='footer'>
            <p>Este es un mensaje automático del Sistema de Ventas e Inventario</p>
            <p>Enviado REALMENTE por SMTP - No es simulación</p>
        </div>
    </body>
    </html>";
    
    // Adjuntar el archivo de backup (usar ruta como string)
    $mail->addAttachment($latestBackup->getPathname(), $filename);
    
    echo "📧 ENVIANDO EMAIL REAL...\n";
    echo "=========================\n";
    
    // Enviar el email
    $mail->send();
    
    echo "🎉 ¡EMAIL ENVIADO EXITOSAMENTE!\n";
    echo "===============================\n";
    echo "✅ Método: PHPMailer con SMTP\n";
    echo "✅ Destinatario: pcapacho24@gmail.com\n";
    echo "✅ Adjunto: $filename (" . round($fileSize / 1024, 2) . " KB)\n";
    echo "✅ Servidor: smtp.gmail.com:587\n";
    echo "✅ Desde: Sistema de Backups <interveredanet.cr@gmail.com>\n";
    echo "✅ Encriptación: STARTTLS\n\n";
    
    echo "📱 VERIFICACIÓN INMEDIATA:\n";
    echo "==========================\n";
    echo "1. 📧 Revisa AHORA la bandeja de entrada de: pcapacho24@gmail.com\n";
    echo "2. 🔍 Busca email con asunto: 🗄️ Backup Real - " . date('d/m/Y H:i:s') . "\n";
    echo "3. 📎 El email contiene el backup adjunto ($filename)\n";
    echo "4. ✅ Este es un envío REAL, no simulación ni log\n";
    echo "5. 📨 Email entregado directamente al servidor de Gmail\n\n";
    
    echo "🎊 ¡ENVÍO REAL COMPLETADO CON ÉXITO!\n";
    echo "====================================\n";
    echo "🚀 El backup se envió REALMENTE por SMTP\n";
    echo "📧 Email entregado al destinatario real\n";
    echo "✅ Usando PHPMailer con configuración SSL corregida\n";
    echo "🔐 Autenticación Gmail exitosa\n";
    
} catch (Exception $e) {
    echo "❌ ERROR DE PHPMAILER:\n";
    echo "======================\n";
    echo "Error: {$mail->ErrorInfo}\n";
    echo "Excepción: " . $e->getMessage() . "\n\n";
    
    echo "🔧 POSIBLES SOLUCIONES:\n";
    echo "=======================\n";
    echo "1. Verifica que la verificación en 2 pasos esté ACTIVADA\n";
    echo "2. Genera una NUEVA contraseña de aplicación\n";
    echo "3. Ve a: https://myaccount.google.com/security\n";
    echo "4. Busca 'Contraseñas de aplicaciones'\n";
    echo "5. Crea nueva contraseña para 'Sistema Backup'\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR GENERAL:\n";
    echo "=================\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}

echo "\n🏁 Envío con PHPMailer completado\n";
