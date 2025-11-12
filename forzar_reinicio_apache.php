<?php
echo "<h1>🔄 FORZAR REINICIO DE APACHE PARA IMAP</h1>";

echo "<h2>📋 PASOS PARA REINICIAR APACHE:</h2>";
echo "<ol>";
echo "<li><strong>Abrir XAMPP Control Panel</strong></li>";
echo "<li><strong>Detener Apache:</strong> Click en 'Stop' junto a Apache</li>";
echo "<li><strong>Esperar 5 segundos</strong></li>";
echo "<li><strong>Iniciar Apache:</strong> Click en 'Start' junto a Apache</li>";
echo "<li><strong>Verificar que no hay errores en los logs</strong></li>";
echo "</ol>";

echo "<h2>🔍 VERIFICACIÓN ACTUAL:</h2>";

echo "<h3>1. Estado de IMAP:</h3>";
if (extension_loaded('imap')) {
    echo "<p style='color: green;'>✅ <strong>IMAP está cargado</strong></p>";
    
    echo "<h3>2. Funciones IMAP disponibles:</h3>";
    $funcionesImap = ['imap_open', 'imap_search', 'imap_fetchstructure', 'imap_fetchbody', 'imap_close'];
    foreach ($funcionesImap as $funcion) {
        if (function_exists($funcion)) {
            echo "<p style='color: green;'>✅ {$funcion}</p>";
        } else {
            echo "<p style='color: red;'>❌ {$funcion}</p>";
        }
    }
    
    echo "<h3>3. Prueba de conexión IMAP:</h3>";
    echo "<p>Intentando conectar a Gmail...</p>";
    
    $servidor = "{imap.gmail.com:993/imap/ssl}INBOX";
    $email = "pcapacho24@gmail.com";
    $password = "adkq prqh vhii njnz";
    
    $connection = @imap_open($servidor, $email, $password);
    
    if ($connection) {
        echo "<p style='color: green; font-size: 18px;'>🎉 <strong>¡CONEXIÓN IMAP EXITOSA!</strong></p>";
        
        $info = imap_mailboxmsginfo($connection);
        echo "<p><strong>📊 Información del buzón:</strong></p>";
        echo "<ul>";
        echo "<li>📧 Total mensajes: {$info->Nmsgs}</li>";
        echo "<li>📬 No leídos: {$info->Unread}</li>";
        echo "<li>📅 Último mensaje: " . date('Y-m-d H:i:s', $info->Date) . "</li>";
        echo "</ul>";
        
        imap_close($connection);
        
        echo "<h2 style='color: green;'>🎊 ¡EL MÓDULO DIAN DEBERÍA FUNCIONAR AHORA!</h2>";
        echo "<p><a href='/dian/configuracion' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🧪 Probar Módulo DIAN</a></p>";
        
    } else {
        $error = imap_last_error();
        echo "<p style='color: red;'>❌ <strong>Error de conexión:</strong> {$error}</p>";
        
        if (strpos($error, 'authentication failed') !== false) {
            echo "<p><strong>💡 Solución:</strong> Verificar contraseña de aplicación de Gmail</p>";
        } else {
            echo "<p><strong>💡 Solución:</strong> Verificar configuración de red/firewall</p>";
        }
    }
    
} else {
    echo "<p style='color: red; font-size: 18px;'>❌ <strong>IMAP NO está cargado</strong></p>";
    echo "<p><strong>🔧 Solución:</strong></p>";
    echo "<ol>";
    echo "<li>Verificar que php.ini tiene: <code>extension=imap</code></li>";
    echo "<li>Reiniciar Apache completamente</li>";
    echo "<li>Verificar logs de Apache por errores</li>";
    echo "</ol>";
}

echo "<h2>📊 Información del sistema:</h2>";
echo "<ul>";
echo "<li><strong>PHP Version:</strong> " . PHP_VERSION . "</li>";
echo "<li><strong>SAPI:</strong> " . php_sapi_name() . "</li>";
echo "<li><strong>php.ini:</strong> " . php_ini_loaded_file() . "</li>";
echo "</ul>";

echo "<h2>🔗 Enlaces útiles:</h2>";
echo "<ul>";
echo "<li><a href='/dian'>🏠 Dashboard DIAN</a></li>";
echo "<li><a href='/dian/configuracion'>⚙️ Configuración DIAN</a></li>";
echo "<li><a href='http://localhost/dashboard/phpinfo.php'>📊 PHPInfo completo</a></li>";
echo "</ul>";

echo "<h2>📝 Siguiente paso:</h2>";
if (extension_loaded('imap')) {
    echo "<p style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
    echo "<strong>✅ IMAP funciona correctamente.</strong><br>";
    echo "Ve al módulo DIAN y prueba la conexión desde la interfaz web.";
    echo "</p>";
} else {
    echo "<p style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<strong>⚠️ Reinicia Apache en XAMPP Control Panel</strong><br>";
    echo "Después recarga esta página para verificar.";
    echo "</p>";
}
?>
