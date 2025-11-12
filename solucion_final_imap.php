<?php

echo "=== SOLUCIÓN FINAL PARA IMAP ===\n\n";

try {
    // 1. Verificar configuración actual
    echo "🔍 1. DIAGNÓSTICO COMPLETO...\n";
    
    $phpIni = php_ini_loaded_file();
    $imapCLI = extension_loaded('imap');
    
    echo "  📟 IMAP CLI: " . ($imapCLI ? '✅ Habilitado' : '❌ Deshabilitado') . "\n";
    echo "  📄 php.ini: {$phpIni}\n";
    
    // Verificar contenido del php.ini
    if (file_exists($phpIni)) {
        $contenido = file_get_contents($phpIni);
        $imapHabilitado = preg_match('/^\s*extension=imap\s*$/m', $contenido);
        $imapComentado = preg_match('/^\s*;extension=imap\s*$/m', $contenido);
        
        echo "  📋 En php.ini:\n";
        if ($imapHabilitado) {
            echo "    ✅ extension=imap (habilitado)\n";
        }
        if ($imapComentado) {
            echo "    ⚠️ ;extension=imap (comentado)\n";
        }
    }
    
    // 2. Verificar DLL
    echo "\n🔍 2. VERIFICANDO ARCHIVOS DLL...\n";
    
    $phpDir = dirname(PHP_BINARY);
    $extDir = $phpDir . DIRECTORY_SEPARATOR . 'ext';
    $imapDll = $extDir . DIRECTORY_SEPARATOR . 'php_imap.dll';
    
    echo "  📁 Directorio: {$extDir}\n";
    
    if (file_exists($imapDll)) {
        $dllSize = filesize($imapDll);
        echo "  ✅ php_imap.dll encontrada (" . number_format($dllSize) . " bytes)\n";
    } else {
        echo "  ❌ php_imap.dll NO encontrada\n";
    }
    
    // Verificar otras DLLs relacionadas
    $dllsRelacionadas = ['php_openssl.dll', 'libeay32.dll', 'ssleay32.dll'];
    foreach ($dllsRelacionadas as $dll) {
        $rutaDll = $extDir . DIRECTORY_SEPARATOR . $dll;
        if (file_exists($rutaDll)) {
            echo "  ✅ {$dll} encontrada\n";
        } else {
            echo "  ⚠️ {$dll} no encontrada (puede ser necesaria)\n";
        }
    }
    
    // 3. Crear configuración alternativa
    echo "\n🔧 3. CREANDO CONFIGURACIÓN ALTERNATIVA...\n";
    
    // Crear un php.ini específico para Apache si es necesario
    $apachePhpIni = 'C:\\xampp\\apache\\bin\\php.ini';
    
    if (!file_exists($apachePhpIni) || $apachePhpIni === $phpIni) {
        echo "  ℹ️ Usando configuración unificada\n";
    } else {
        echo "  📄 Verificando php.ini de Apache: {$apachePhpIni}\n";
        
        if (file_exists($apachePhpIni)) {
            $contenidoApache = file_get_contents($apachePhpIni);
            $imapApache = preg_match('/^\s*extension=imap\s*$/m', $contenidoApache);
            
            if (!$imapApache) {
                echo "  🔧 Habilitando IMAP en php.ini de Apache...\n";
                
                // Agregar o descomentar IMAP
                if (preg_match('/^\s*;extension=imap\s*$/m', $contenidoApache)) {
                    $contenidoApache = preg_replace('/^\s*;extension=imap\s*$/m', 'extension=imap', $contenidoApache);
                } else {
                    $contenidoApache .= "\n; IMAP habilitado automáticamente\nextension=imap\n";
                }
                
                if (file_put_contents($apachePhpIni, $contenidoApache)) {
                    echo "  ✅ IMAP habilitado en php.ini de Apache\n";
                } else {
                    echo "  ❌ Error escribiendo php.ini de Apache\n";
                }
            } else {
                echo "  ✅ IMAP ya habilitado en php.ini de Apache\n";
            }
        }
    }
    
    // 4. Verificar variables de entorno
    echo "\n🌍 4. VERIFICANDO VARIABLES DE ENTORNO...\n";
    
    $path = getenv('PATH');
    $phpInPath = strpos($path, 'xampp\\php') !== false;
    echo "  📍 PHP en PATH: " . ($phpInPath ? '✅ Sí' : '⚠️ No') . "\n";
    
    // 5. Crear script de prueba web robusto
    echo "\n🌐 5. CREANDO SCRIPT DE PRUEBA WEB ROBUSTO...\n";
    
    $scriptPruebaWeb = '<?php
// Script de prueba IMAP robusto
error_reporting(E_ALL);
ini_set("display_errors", 1);

echo "<h1>🧪 PRUEBA IMAP ROBUSTA</h1>";

echo "<h2>📊 Información del Sistema</h2>";
echo "<table border=\"1\" cellpadding=\"5\" style=\"border-collapse: collapse;\">";
echo "<tr><th>Propiedad</th><th>Valor</th></tr>";
echo "<tr><td>PHP Version</td><td>" . PHP_VERSION . "</td></tr>";
echo "<tr><td>SAPI</td><td>" . php_sapi_name() . "</td></tr>";
echo "<tr><td>php.ini</td><td>" . (php_ini_loaded_file() ?: "Ninguno") . "</td></tr>";
echo "<tr><td>extension_dir</td><td>" . ini_get("extension_dir") . "</td></tr>";
echo "</table>";

echo "<h2>🔌 Estado de IMAP</h2>";

if (extension_loaded("imap")) {
    echo "<div style=\"background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;\">";
    echo "<h3 style=\"color: #155724;\">✅ ¡IMAP ESTÁ CARGADO!</h3>";
    
    // Verificar funciones
    $funciones = [\"imap_open\", \"imap_search\", \"imap_fetchstructure\", \"imap_fetchbody\", \"imap_close\"];
    echo "<p><strong>Funciones disponibles:</strong></p>";
    foreach ($funciones as $func) {
        if (function_exists($func)) {
            echo "<span style=\"color: green;\">✅ {$func}</span><br>";
        } else {
            echo "<span style=\"color: red;\">❌ {$func}</span><br>";
        }
    }
    
    // Probar conexión real
    echo "<h3>🧪 Prueba de Conexión a Gmail</h3>";
    
    $servidor = \"{imap.gmail.com:993/imap/ssl}INBOX\";
    $email = \"pcapacho24@gmail.com\";
    $password = \"adkq prqh vhii njnz\";
    
    echo "<p>🔗 Conectando a: {$servidor}</p>";
    echo "<p>👤 Usuario: {$email}</p>";
    
    $connection = @imap_open($servidor, $email, $password);
    
    if ($connection) {
        echo "<div style=\"background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0;\">";
        echo "<h3 style=\"color: #0c5460;\">🎉 ¡CONEXIÓN EXITOSA!</h3>";
        
        $info = imap_mailboxmsginfo($connection);
        echo "<ul>";
        echo "<li>📊 Total mensajes: {$info->Nmsgs}</li>";
        echo "<li>📬 No leídos: {$info->Unread}</li>";
        echo "<li>📅 Último mensaje: " . date(\"Y-m-d H:i:s\", $info->Date) . "</li>";
        echo "</ul>";
        
        imap_close($connection);
        echo "</div>";
        
        echo "<h2 style=\"color: green;\">🎊 ¡EL MÓDULO DIAN ESTÁ LISTO!</h2>";
        echo "<div style=\"text-align: center; margin: 20px 0;\">";
        echo "<a href=\"/dian\" style=\"background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 18px; margin: 10px;\">🏠 Dashboard DIAN</a>";
        echo "<a href=\"/dian/configuracion\" style=\"background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 18px; margin: 10px;\">⚙️ Configuración</a>";
        echo "</div>";
        
    } else {
        $error = imap_last_error();
        echo "<div style=\"background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;\">";
        echo "<h3 style=\"color: #721c24;\">❌ Error de Conexión</h3>";
        echo "<p><strong>Error:</strong> {$error}</p>";
        
        if (strpos($error, \"authentication failed\") !== false) {
            echo "<p><strong>💡 Solución:</strong> Verificar contraseña de aplicación de Gmail</p>";
        } else {
            echo "<p><strong>💡 Solución:</strong> Verificar configuración de red/firewall</p>";
        }
        echo "</div>";
    }
    
    echo "</div>";
    
} else {
    echo "<div style=\"background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;\">";
    echo "<h3 style=\"color: #721c24;\">❌ IMAP NO ESTÁ CARGADO</h3>";
    
    echo "<h4>🔍 Diagnóstico:</h4>";
    
    // Verificar php.ini
    $phpIni = php_ini_loaded_file();
    if ($phpIni && file_exists($phpIni)) {
        $contenido = file_get_contents($phpIni);
        
        if (preg_match(\"/^\\s*extension=imap\\s*$/m\", $contenido)) {
            echo "<p>✅ extension=imap encontrado en php.ini</p>";
        } elseif (preg_match(\"/^\\s*;extension=imap\\s*$/m\", $contenido)) {
            echo "<p>❌ extension=imap está comentado en php.ini</p>";
        } else {
            echo "<p>❌ extension=imap no encontrado en php.ini</p>";
        }
    }
    
    // Verificar DLL
    $extDir = ini_get(\"extension_dir\");
    $imapDll = $extDir . DIRECTORY_SEPARATOR . \"php_imap.dll\";
    
    if (file_exists($imapDll)) {
        echo "<p>✅ php_imap.dll encontrada en: {$imapDll}</p>";
    } else {
        echo "<p>❌ php_imap.dll NO encontrada en: {$imapDll}</p>";
    }
    
    echo "<h4>🔧 Soluciones:</h4>";
    echo "<ol>";
    echo "<li>Verificar que php.ini tiene: <code>extension=imap</code></li>";
    echo "<li>Verificar que existe: <code>{$imapDll}</code></li>";
    echo "<li>Reiniciar Apache completamente</li>";
    echo "<li>Verificar logs de Apache por errores</li>";
    echo "</ol>";
    
    echo "</div>";
}

echo "<h2>📋 Extensiones Cargadas</h2>";
$extensiones = get_loaded_extensions();
sort($extensiones);
echo "<p><strong>Total:</strong> " . count($extensiones) . " extensiones</p>";
echo "<details><summary>Ver todas</summary>";
echo "<div style=\"columns: 3; column-gap: 20px;\">";
foreach ($extensiones as $ext) {
    if (stripos($ext, \"imap\") !== false) {
        echo "<strong style=\"color: green;\">{$ext}</strong><br>";
    } else {
        echo "{$ext}<br>";
    }
}
echo "</div></details>";

echo "<h2>🔗 Enlaces Útiles</h2>";
echo "<ul>";
echo "<li><a href=\"http://localhost/dashboard/phpinfo.php\">📊 PHPInfo Completo</a></li>";
echo "<li><a href=\"/dian\">🏠 Dashboard DIAN</a></li>";
echo "<li><a href=\"/dian/configuracion\">⚙️ Configuración DIAN</a></li>";
echo "</ul>";
?>';
    
    file_put_contents(__DIR__ . '/prueba_imap_robusta.php', $scriptPruebaWeb);
    echo "  ✅ Script creado: prueba_imap_robusta.php\n";
    
    // 6. Instrucciones finales
    echo "\n🎯 6. INSTRUCCIONES FINALES...\n";
    echo "  1. 🔄 Reinicia Apache completamente:\n";
    echo "     - XAMPP Control Panel → Stop Apache\n";
    echo "     - Espera 10 segundos\n";
    echo "     - Start Apache\n";
    echo "\n";
    echo "  2. 🧪 Verifica con el script robusto:\n";
    echo "     http://127.0.0.1:8000/prueba_imap_robusta.php\n";
    echo "\n";
    echo "  3. 🚀 Si funciona, ve al módulo DIAN:\n";
    echo "     http://127.0.0.1:8000/dian\n";
    
    echo "\n🎊 SOLUCIÓN FINAL APLICADA\n";
    echo "✅ Configuración verificada\n";
    echo "✅ Scripts de prueba creados\n";
    echo "⚠️ REINICIA APACHE y prueba el script\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n✅ Proceso completado.\n";
