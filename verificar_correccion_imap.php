<?php
echo "<h1>🧪 VERIFICACIÓN IMAP POST-CORRECCIÓN</h1>";

if (extension_loaded("imap")) {
    echo "<p style=\"color: green; font-size: 18px;\">✅ <strong>¡IMAP ESTÁ CARGADO!</strong></p>";
    
    // Probar conexión
    $servidor = "{imap.gmail.com:993/imap/ssl}INBOX";
    $email = "pcapacho24@gmail.com";
    $password = "adkq prqh vhii njnz";
    
    $connection = @imap_open($servidor, $email, $password);
    
    if ($connection) {
        echo "<p style=\"color: green; font-size: 16px;\">🎉 <strong>¡CONEXIÓN A GMAIL EXITOSA!</strong></p>";
        $info = imap_mailboxmsginfo($connection);
        echo "<p>📊 Total mensajes: {$info->Nmsgs}</p>";
        echo "<p>📬 No leídos: {$info->Unread}</p>";
        imap_close($connection);
        
        echo "<h2 style=\"color: green;\">🎊 ¡EL MÓDULO DIAN ESTÁ LISTO!</h2>";
        echo "<p><a href=\"/dian/configuracion\" style=\"background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px;\">🚀 IR AL MÓDULO DIAN</a></p>";
        
    } else {
        $error = imap_last_error();
        echo "<p style=\"color: red;\">❌ Error de conexión: {$error}</p>";
    }
    
} else {
    echo "<p style=\"color: red; font-size: 18px;\">❌ <strong>IMAP AÚN NO ESTÁ CARGADO</strong></p>";
    echo "<p><strong>Solución:</strong> Reinicia Apache en XAMPP Control Panel</p>";
}

echo "<h2>📊 Información del sistema:</h2>";
echo "<ul>";
echo "<li>PHP Version: " . PHP_VERSION . "</li>";
echo "<li>SAPI: " . php_sapi_name() . "</li>";
echo "<li>php.ini: " . php_ini_loaded_file() . "</li>";
echo "</ul>";
?>