<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\File;

echo "📧 ENVÍO REAL CON MAIL NATIVO DE PHP\n";
echo "====================================\n\n";

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

// Verificar si el archivo es muy grande (límite de 10MB para mail nativo)
if ($fileSize > 10 * 1024 * 1024) {
    echo "⚠️ ARCHIVO MUY GRANDE PARA MAIL NATIVO\n";
    echo "======================================\n";
    echo "Tamaño: " . round($fileSize / 1024 / 1024, 2) . " MB\n";
    echo "Límite recomendado: 10 MB\n";
    echo "Usa PHPMailer o reduce el tamaño del backup\n";
    exit(1);
}

try {
    echo "🔧 CONFIGURANDO ENVÍO NATIVO:\n";
    echo "=============================\n";
    
    $to = 'pcapacho24@gmail.com';
    $subject = '🗄️ Backup Real - ' . date('d/m/Y H:i:s');
    $from = 'interveredanet.cr@gmail.com';
    $fromName = 'Sistema de Backups';
    
    // Generar boundary para multipart
    $boundary = md5(time());
    
    // Headers del email
    $headers = "From: $fromName <$from>\r\n";
    $headers .= "Reply-To: $from\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
    
    // Leer el archivo de backup
    $fileContent = file_get_contents($latestBackup);
    $fileContentEncoded = chunk_split(base64_encode($fileContent));
    
    // Crear el cuerpo del mensaje
    $message = "--$boundary\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    
    // HTML del email
    $htmlContent = "
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
                <strong>🔧 Método:</strong> Mail nativo PHP (ENVÍO REAL)
            </div>
            
            <p><strong>📎 El backup se encuentra adjunto a este correo electrónico.</strong></p>
            
            <h3>⚠️ Importante:</h3>
            <ul>
                <li>Este es un envío REAL usando mail() de PHP</li>
                <li>El archivo está adjunto como base64</li>
                <li>Guarde el backup en un lugar seguro</li>
                <li>No comparta este archivo con personas no autorizadas</li>
            </ul>
        </div>
        <div class='footer'>
            <p>Este es un mensaje automático del Sistema de Ventas e Inventario</p>
            <p>Enviado REALMENTE - No es simulación ni log</p>
        </div>
    </body>
    </html>";
    
    $message .= $htmlContent . "\r\n\r\n";
    
    // Adjuntar el archivo
    $message .= "--$boundary\r\n";
    $message .= "Content-Type: application/octet-stream; name=\"$filename\"\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n";
    $message .= "Content-Disposition: attachment; filename=\"$filename\"\r\n\r\n";
    $message .= $fileContentEncoded . "\r\n";
    $message .= "--$boundary--\r\n";
    
    echo "✅ Headers configurados\n";
    echo "✅ Contenido HTML creado\n";
    echo "✅ Archivo adjunto preparado\n";
    echo "✅ Destinatario: $to\n";
    echo "✅ Asunto: $subject\n\n";
    
    echo "📧 ENVIANDO EMAIL REAL...\n";
    echo "=========================\n";
    
    // Enviar el email
    $sent = mail($to, $subject, $message, $headers);
    
    if ($sent) {
        echo "🎉 ¡EMAIL ENVIADO EXITOSAMENTE!\n";
        echo "===============================\n";
        echo "✅ Método: mail() nativo de PHP\n";
        echo "✅ Destinatario: pcapacho24@gmail.com\n";
        echo "✅ Adjunto: $filename (" . round($fileSize / 1024, 2) . " KB)\n";
        echo "✅ Desde: Sistema de Backups <interveredanet.cr@gmail.com>\n";
        echo "✅ Codificación: Base64\n\n";
        
        echo "📱 VERIFICACIÓN INMEDIATA:\n";
        echo "==========================\n";
        echo "1. 📧 Revisa AHORA la bandeja de entrada de: pcapacho24@gmail.com\n";
        echo "2. 🔍 También revisa la carpeta de SPAM/CORREO NO DESEADO\n";
        echo "3. 📎 Busca email con asunto: 🗄️ Backup Real - " . date('d/m/Y H:i:s') . "\n";
        echo "4. 📁 El email contiene el backup adjunto ($filename)\n";
        echo "5. ✅ Este es un envío REAL usando mail() de PHP\n\n";
        
        echo "🎊 ¡ENVÍO REAL COMPLETADO CON ÉXITO!\n";
        echo "====================================\n";
        echo "🚀 El backup se envió REALMENTE\n";
        echo "📧 Email entregado al servidor de correo\n";
        echo "✅ Usando función mail() nativa de PHP\n";
        echo "📨 Revisa tu bandeja de entrada AHORA\n";
        
    } else {
        echo "❌ ERROR AL ENVIAR EMAIL\n";
        echo "========================\n";
        echo "La función mail() retornó false\n";
        echo "Posibles causas:\n";
        echo "1. Servidor SMTP no configurado en PHP\n";
        echo "2. Restricciones del servidor\n";
        echo "3. Archivo demasiado grande\n";
        echo "4. Headers malformados\n\n";
        
        echo "🔧 CONFIGURACIÓN PHP MAIL:\n";
        echo "==========================\n";
        echo "SMTP: " . ini_get('SMTP') . "\n";
        echo "smtp_port: " . ini_get('smtp_port') . "\n";
        echo "sendmail_from: " . ini_get('sendmail_from') . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR GENERAL:\n";
    echo "=================\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}

echo "\n📋 RESUMEN DE MÉTODOS PROBADOS:\n";
echo "===============================\n";
echo "1. ❌ Laravel Mail con SMTP: Error de autenticación Gmail\n";
echo "2. ❌ Symfony Mailer: Error SSL\n";
echo "3. ❌ PHPMailer: No instalado\n";
echo "4. ✅ Mail nativo PHP: " . ($sent ?? false ? "EXITOSO" : "Falló") . "\n\n";

echo "🎯 RECOMENDACIÓN:\n";
echo "=================\n";
if ($sent ?? false) {
    echo "✅ El envío nativo funcionó - Revisa tu email\n";
} else {
    echo "⚠️ Para envío real confiable:\n";
    echo "1. Instala PHPMailer: composer require phpmailer/phpmailer\n";
    echo "2. O configura SMTP en php.ini\n";
    echo "3. O usa servicios como SendGrid/Mailgun\n";
}

echo "\n🏁 Envío nativo completado\n";
