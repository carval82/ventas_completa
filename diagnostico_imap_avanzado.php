<?php
echo "<h1>🔍 DIAGNÓSTICO AVANZADO DE IMAP</h1>";

echo "<h2>📊 INFORMACIÓN DEL SISTEMA</h2>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Propiedad</th><th>Valor</th></tr>";
echo "<tr><td>PHP Version</td><td>" . PHP_VERSION . "</td></tr>";
echo "<tr><td>SAPI</td><td>" . php_sapi_name() . "</td></tr>";
echo "<tr><td>php.ini cargado</td><td>" . (php_ini_loaded_file() ?: 'Ninguno') . "</td></tr>";
echo "<tr><td>Directorio de extensiones</td><td>" . ini_get('extension_dir') . "</td></tr>";
echo "</table>";

echo "<h2>🔌 ESTADO DE IMAP</h2>";

if (extension_loaded('imap')) {
    echo "<p style='color: green; font-size: 18px;'>✅ <strong>IMAP está cargado</strong></p>";
    
    // Probar funciones IMAP
    $funcionesImap = ['imap_open', 'imap_search', 'imap_fetchstructure', 'imap_fetchbody', 'imap_close'];
    echo "<h3>Funciones IMAP:</h3>";
    foreach ($funcionesImap as $funcion) {
        if (function_exists($funcion)) {
            echo "<span style='color: green;'>✅ {$funcion}</span><br>";
        } else {
            echo "<span style='color: red;'>❌ {$funcion}</span><br>";
        }
    }
    
    // Probar conexión real
    echo "<h3>🧪 Prueba de conexión:</h3>";
    $servidor = "{imap.gmail.com:993/imap/ssl}INBOX";
    $email = "pcapacho24@gmail.com";
    $password = "adkq prqh vhii njnz";
    
    $connection = @imap_open($servidor, $email, $password);
    
    if ($connection) {
        echo "<p style='color: green; font-size: 16px;'>🎉 <strong>¡CONEXIÓN EXITOSA!</strong></p>";
        $info = imap_mailboxmsginfo($connection);
        echo "<p>📊 Total mensajes: {$info->Nmsgs}</p>";
        echo "<p>📬 No leídos: {$info->Unread}</p>";
        imap_close($connection);
        
        echo "<h2 style='color: green;'>🎊 ¡EL MÓDULO DIAN DEBERÍA FUNCIONAR!</h2>";
        echo "<p><a href='/dian/configuracion' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🧪 Ir al Módulo DIAN</a></p>";
        
    } else {
        $error = imap_last_error();
        echo "<p style='color: red;'>❌ Error de conexión: {$error}</p>";
    }
    
} else {
    echo "<p style='color: red; font-size: 18px;'>❌ <strong>IMAP NO está cargado</strong></p>";
    
    echo "<h3>🔍 Diagnóstico detallado:</h3>";
    
    // Verificar archivo php.ini
    $phpIni = php_ini_loaded_file();
    if ($phpIni && file_exists($phpIni)) {
        echo "<p>📄 Leyendo php.ini: {$phpIni}</p>";
        
        $contenido = file_get_contents($phpIni);
        
        // Buscar línea de IMAP
        if (preg_match('/^extension=imap\s*$/m', $contenido)) {
            echo "<p style='color: green;'>✅ extension=imap encontrado en php.ini</p>";
        } elseif (preg_match('/^;extension=imap\s*$/m', $contenido)) {
            echo "<p style='color: red;'>❌ extension=imap está comentado en php.ini</p>";
            echo "<p><strong>🔧 Solución:</strong> Descomentar la línea</p>";
        } else {
            echo "<p style='color: red;'>❌ extension=imap no encontrado en php.ini</p>";
            echo "<p><strong>🔧 Solución:</strong> Agregar extension=imap</p>";
        }
    }
    
    // Verificar DLL
    $extDir = ini_get('extension_dir');
    $imapDll = $extDir . DIRECTORY_SEPARATOR . 'php_imap.dll';
    
    echo "<p>🔍 Buscando DLL: {$imapDll}</p>";
    if (file_exists($imapDll)) {
        echo "<p style='color: green;'>✅ php_imap.dll encontrada</p>";
    } else {
        echo "<p style='color: red;'>❌ php_imap.dll NO encontrada</p>";
        echo "<p><strong>🔧 Solución:</strong> Descargar o reinstalar XAMPP</p>";
    }
    
    // Verificar otros archivos php.ini
    echo "<h3>🔍 Buscando otros archivos php.ini:</h3>";
    $posiblesRutas = [
        'C:\\xampp\\php\\php.ini',
        'C:\\xampp\\apache\\bin\\php.ini',
        'C:\\xampp\\apache\\conf\\php.ini',
        'C:\\Windows\\php.ini'
    ];
    
    foreach ($posiblesRutas as $ruta) {
        if (file_exists($ruta)) {
            echo "<p>📄 Encontrado: {$ruta}</p>";
            
            $contenido = file_get_contents($ruta);
            if (preg_match('/^extension=imap\s*$/m', $contenido)) {
                echo "<p style='color: green;'>  ✅ IMAP habilitado en este archivo</p>";
            } elseif (preg_match('/^;extension=imap\s*$/m', $contenido)) {
                echo "<p style='color: orange;'>  ⚠️ IMAP comentado en este archivo</p>";
            } else {
                echo "<p style='color: red;'>  ❌ IMAP no encontrado en este archivo</p>";
            }
        }
    }
}

echo "<h2>📋 EXTENSIONES CARGADAS</h2>";
$extensiones = get_loaded_extensions();
sort($extensiones);

echo "<p><strong>Total extensiones cargadas:</strong> " . count($extensiones) . "</p>";
echo "<details><summary>Ver todas las extensiones</summary>";
echo "<div style='columns: 3; column-gap: 20px;'>";
foreach ($extensiones as $ext) {
    if (stripos($ext, 'imap') !== false) {
        echo "<span style='color: green; font-weight: bold;'>{$ext}</span><br>";
    } else {
        echo "{$ext}<br>";
    }
}
echo "</div></details>";

echo "<h2>🔧 SOLUCIONES RECOMENDADAS</h2>";

if (!extension_loaded('imap')) {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;'>";
    echo "<h3>⚠️ IMAP no está cargado</h3>";
    echo "<p><strong>Soluciones en orden de prioridad:</strong></p>";
    echo "<ol>";
    echo "<li><strong>Verificar php.ini correcto:</strong>";
    echo "<ul>";
    echo "<li>Ve a <a href='http://localhost/dashboard/phpinfo.php'>phpinfo()</a></li>";
    echo "<li>Busca 'Loaded Configuration File'</li>";
    echo "<li>Edita ESE archivo php.ini específico</li>";
    echo "</ul></li>";
    echo "<li><strong>Habilitar IMAP:</strong>";
    echo "<ul>";
    echo "<li>Buscar: <code>;extension=imap</code></li>";
    echo "<li>Cambiar a: <code>extension=imap</code></li>";
    echo "</ul></li>";
    echo "<li><strong>Verificar DLL:</strong>";
    echo "<ul>";
    echo "<li>Verificar que existe: <code>C:\\xampp\\php\\ext\\php_imap.dll</code></li>";
    echo "<li>Si no existe, reinstalar XAMPP</li>";
    echo "</ul></li>";
    echo "<li><strong>Reiniciar Apache completamente</strong></li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h3>🚀 Script automático:</h3>";
    echo "<p><a href='#' onclick='habilitarImapAutomatico()' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔧 Habilitar IMAP Automáticamente</a></p>";
}

echo "<h2>🔗 Enlaces útiles</h2>";
echo "<ul>";
echo "<li><a href='http://localhost/dashboard/phpinfo.php'>📊 PHPInfo completo</a></li>";
echo "<li><a href='/dian'>🏠 Dashboard DIAN</a></li>";
echo "<li><a href='/dian/configuracion'>⚙️ Configuración DIAN</a></li>";
echo "</ul>";

?>

<script>
function habilitarImapAutomatico() {
    if (confirm('¿Quieres intentar habilitar IMAP automáticamente?\n\nEsto buscará y modificará el archivo php.ini correcto.')) {
        window.location.href = 'habilitar_imap_automatico.php';
    }
}
</script>
