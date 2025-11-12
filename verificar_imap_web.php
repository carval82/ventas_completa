<?php
// Script para verificar IMAP desde web
echo "<h2>Verificación de Extensión IMAP</h2>";

echo "<h3>1. Verificación de Extensión</h3>";
if (extension_loaded('imap')) {
    echo "✅ Extensión IMAP está cargada<br>";
} else {
    echo "❌ Extensión IMAP NO está cargada<br>";
}

echo "<h3>2. Funciones IMAP Disponibles</h3>";
$funcionesImap = ['imap_open', 'imap_search', 'imap_fetchstructure', 'imap_fetchbody', 'imap_close'];
foreach ($funcionesImap as $funcion) {
    if (function_exists($funcion)) {
        echo "✅ {$funcion} disponible<br>";
    } else {
        echo "❌ {$funcion} NO disponible<br>";
    }
}

echo "<h3>3. Información de PHP</h3>";
echo "Versión PHP: " . PHP_VERSION . "<br>";
echo "SAPI: " . php_sapi_name() . "<br>";

echo "<h3>4. Extensiones Cargadas</h3>";
$extensiones = get_loaded_extensions();
sort($extensiones);
foreach ($extensiones as $ext) {
    if (strpos(strtolower($ext), 'imap') !== false) {
        echo "✅ {$ext}<br>";
    }
}

echo "<h3>5. Configuración PHP</h3>";
echo "extension_dir: " . ini_get('extension_dir') . "<br>";

echo "<h3>6. Prueba de Conexión IMAP</h3>";
if (function_exists('imap_open')) {
    echo "Intentando conexión de prueba...<br>";
    
    $servidor = "{imap.gmail.com:993/imap/ssl}INBOX";
    $email = "pcapacho24@gmail.com";
    $password = "adkq prqh vhii njnz";
    
    $connection = @imap_open($servidor, $email, $password);
    
    if ($connection) {
        echo "🎉 ¡Conexión IMAP exitosa!<br>";
        $info = imap_mailboxmsginfo($connection);
        echo "Total mensajes: {$info->Nmsgs}<br>";
        echo "No leídos: {$info->Unread}<br>";
        imap_close($connection);
    } else {
        $error = imap_last_error();
        echo "❌ Error de conexión: {$error}<br>";
    }
} else {
    echo "❌ Función imap_open no disponible<br>";
}

echo "<h3>7. Solución</h3>";
if (!extension_loaded('imap')) {
    echo "<p style='color: red;'><strong>PROBLEMA:</strong> La extensión IMAP no está habilitada para el servidor web.</p>";
    echo "<p><strong>SOLUCIÓN:</strong></p>";
    echo "<ol>";
    echo "<li>Abrir el archivo php.ini usado por Apache: <code>C:\\xampp\\php\\php.ini</code></li>";
    echo "<li>Buscar la línea: <code>;extension=imap</code></li>";
    echo "<li>Cambiar a: <code>extension=imap</code> (quitar el punto y coma)</li>";
    echo "<li>Reiniciar Apache en XAMPP</li>";
    echo "<li>Verificar que aparezca en phpinfo()</li>";
    echo "</ol>";
} else {
    echo "<p style='color: green;'><strong>✅ IMAP está correctamente configurado</strong></p>";
}
?>
