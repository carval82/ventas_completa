<?php

echo "=== CORRECCIÓN AUTOMÁTICA DE IMAP PARA APACHE ===\n\n";

try {
    // 1. Verificar estado actual
    echo "🔍 1. VERIFICANDO ESTADO ACTUAL...\n";
    
    // Verificar CLI
    $imapCLI = extension_loaded('imap');
    echo "  📟 IMAP CLI: " . ($imapCLI ? '✅ Habilitado' : '❌ Deshabilitado') . "\n";
    
    // 2. Localizar php.ini de Apache
    echo "\n📄 2. LOCALIZANDO PHP.INI DE APACHE...\n";
    
    $phpIniCLI = php_ini_loaded_file();
    echo "  📟 php.ini CLI: {$phpIniCLI}\n";
    
    // Rutas comunes para Apache en XAMPP
    $posiblesRutasApache = [
        'C:\\xampp\\apache\\bin\\php.ini',
        'C:\\xampp\\php\\php.ini',
        'C:\\xampp\\apache\\conf\\php.ini'
    ];
    
    $phpIniApache = null;
    
    foreach ($posiblesRutasApache as $ruta) {
        if (file_exists($ruta)) {
            echo "  📄 Encontrado: {$ruta}\n";
            
            // Verificar si este archivo tiene configuración de Apache
            $contenido = file_get_contents($ruta);
            
            // Si es el mismo archivo que CLI y IMAP ya está habilitado, es probable que sea el correcto
            if ($ruta === $phpIniCLI && $imapCLI) {
                $phpIniApache = $ruta;
                echo "  ✅ Usando archivo CLI (mismo para Apache): {$ruta}\n";
                break;
            }
            
            // Si no es el CLI, verificar si tiene extensiones
            if ($ruta !== $phpIniCLI && strpos($contenido, 'extension=') !== false) {
                $phpIniApache = $ruta;
                echo "  ✅ Detectado como php.ini de Apache: {$ruta}\n";
                break;
            }
        }
    }
    
    if (!$phpIniApache) {
        $phpIniApache = $phpIniCLI; // Usar CLI como fallback
        echo "  ⚠️ Usando CLI como fallback: {$phpIniApache}\n";
    }
    
    // 3. Verificar y corregir configuración
    echo "\n🔧 3. VERIFICANDO Y CORRIGIENDO CONFIGURACIÓN...\n";
    
    if (!is_readable($phpIniApache)) {
        echo "  ❌ No se puede leer {$phpIniApache}\n";
        echo "  🔧 Ejecuta este script como administrador\n";
        exit(1);
    }
    
    $contenido = file_get_contents($phpIniApache);
    $lineas = explode("\n", $contenido);
    $modificado = false;
    $imapEncontrado = false;
    
    foreach ($lineas as $i => &$linea) {
        // Buscar línea comentada de IMAP
        if (preg_match('/^\s*;extension=imap\s*$/i', trim($linea))) {
            echo "  ⚠️ IMAP comentado en línea " . ($i + 1) . ": {$linea}\n";
            $linea = 'extension=imap';
            echo "  🔧 Corregido a: {$linea}\n";
            $modificado = true;
            $imapEncontrado = true;
            break;
        }
        // Verificar si ya está habilitado
        elseif (preg_match('/^\s*extension=imap\s*$/i', trim($linea))) {
            echo "  ✅ IMAP ya habilitado en línea " . ($i + 1) . ": {$linea}\n";
            $imapEncontrado = true;
            break;
        }
    }
    
    // Si no se encontró, agregar
    if (!$imapEncontrado) {
        echo "  ➕ IMAP no encontrado, agregándolo...\n";
        
        // Buscar una buena ubicación
        $ubicacion = -1;
        foreach ($lineas as $i => $linea) {
            if (strpos($linea, 'extension=') !== false) {
                $ubicacion = $i + 1;
            }
        }
        
        if ($ubicacion > 0) {
            array_splice($lineas, $ubicacion, 0, ['extension=imap']);
            echo "  ✅ IMAP agregado después de otras extensiones\n";
        } else {
            $lineas[] = '';
            $lineas[] = '; IMAP habilitado automáticamente';
            $lineas[] = 'extension=imap';
            echo "  ✅ IMAP agregado al final del archivo\n";
        }
        $modificado = true;
    }
    
    // 4. Guardar cambios
    if ($modificado) {
        echo "\n💾 4. GUARDANDO CAMBIOS...\n";
        
        // Crear backup
        $backupPath = $phpIniApache . '.backup.' . date('Y-m-d_H-i-s');
        if (copy($phpIniApache, $backupPath)) {
            echo "  ✅ Backup creado: {$backupPath}\n";
        }
        
        // Guardar
        $nuevoContenido = implode("\n", $lineas);
        if (file_put_contents($phpIniApache, $nuevoContenido) !== false) {
            echo "  ✅ Archivo guardado exitosamente\n";
        } else {
            echo "  ❌ Error guardando archivo\n";
            exit(1);
        }
    } else {
        echo "\n✅ No se requieren cambios en php.ini\n";
    }
    
    // 5. Verificar DLL
    echo "\n🔍 5. VERIFICANDO DLL DE IMAP...\n";
    
    $phpDir = dirname(PHP_BINARY);
    $extDir = $phpDir . DIRECTORY_SEPARATOR . 'ext';
    $imapDll = $extDir . DIRECTORY_SEPARATOR . 'php_imap.dll';
    
    echo "  📁 Directorio extensiones: {$extDir}\n";
    echo "  🔍 Buscando: {$imapDll}\n";
    
    if (file_exists($imapDll)) {
        echo "  ✅ php_imap.dll encontrada\n";
    } else {
        echo "  ❌ php_imap.dll NO encontrada\n";
        echo "  🔧 Intentando descargar...\n";
        
        // Intentar copiar desde otra ubicación común
        $posiblesDlls = [
            'C:\\xampp\\php\\ext\\php_imap.dll',
            'C:\\xampp\\apache\\bin\\ext\\php_imap.dll'
        ];
        
        foreach ($posiblesDlls as $dllPath) {
            if (file_exists($dllPath) && $dllPath !== $imapDll) {
                if (copy($dllPath, $imapDll)) {
                    echo "  ✅ DLL copiada desde: {$dllPath}\n";
                    break;
                } else {
                    echo "  ⚠️ No se pudo copiar desde: {$dllPath}\n";
                }
            }
        }
    }
    
    // 6. Crear script de verificación web
    echo "\n🌐 6. CREANDO SCRIPT DE VERIFICACIÓN WEB...\n";
    
    $scriptVerificacion = '<?php
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
?>';
    
    file_put_contents(__DIR__ . '/verificar_correccion_imap.php', $scriptVerificacion);
    echo "  ✅ Script creado: verificar_correccion_imap.php\n";
    
    // 7. Instrucciones finales
    echo "\n🎯 7. INSTRUCCIONES FINALES...\n";
    
    if ($modificado) {
        echo "  🔄 PASO 1: Reinicia Apache en XAMPP Control Panel\n";
        echo "     - Stop Apache\n";
        echo "     - Espera 5 segundos\n";
        echo "     - Start Apache\n";
        echo "\n";
    }
    
    echo "  🧪 PASO 2: Verifica que funciona:\n";
    echo "     http://127.0.0.1:8000/verificar_correccion_imap.php\n";
    echo "\n";
    echo "  🚀 PASO 3: Si funciona, ve al módulo DIAN:\n";
    echo "     http://127.0.0.1:8000/dian/configuracion\n";
    
    echo "\n🎊 CORRECCIÓN COMPLETADA\n";
    
    if ($modificado) {
        echo "✅ Se modificó: {$phpIniApache}\n";
        echo "⚠️ REINICIA APACHE para aplicar cambios\n";
    } else {
        echo "ℹ️ No se requirieron cambios en php.ini\n";
        echo "🔄 Intenta reiniciar Apache de todas formas\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n✅ Script de corrección finalizado.\n";
