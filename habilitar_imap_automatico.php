<?php
echo "<h1>🔧 HABILITAR IMAP AUTOMÁTICAMENTE</h1>";

try {
    echo "<h2>🔍 PASO 1: Identificando archivo php.ini correcto</h2>";
    
    // Obtener el php.ini que realmente usa Apache
    $phpIniWeb = php_ini_loaded_file();
    echo "<p>📄 php.ini detectado: <strong>{$phpIniWeb}</strong></p>";
    
    if (!$phpIniWeb || !file_exists($phpIniWeb)) {
        echo "<p style='color: red;'>❌ No se pudo detectar php.ini</p>";
        
        // Buscar manualmente
        $posiblesRutas = [
            'C:\\xampp\\php\\php.ini',
            'C:\\xampp\\apache\\bin\\php.ini'
        ];
        
        echo "<p>🔍 Buscando manualmente...</p>";
        foreach ($posiblesRutas as $ruta) {
            if (file_exists($ruta)) {
                $phpIniWeb = $ruta;
                echo "<p style='color: green;'>✅ Encontrado: {$ruta}</p>";
                break;
            }
        }
        
        if (!$phpIniWeb) {
            echo "<p style='color: red;'>❌ No se encontró php.ini. Proceso abortado.</p>";
            exit;
        }
    }
    
    echo "<h2>📖 PASO 2: Leyendo contenido actual</h2>";
    
    if (!is_readable($phpIniWeb)) {
        echo "<p style='color: red;'>❌ No se puede leer {$phpIniWeb}</p>";
        echo "<p><strong>Solución:</strong> Ejecutar como administrador o cambiar permisos</p>";
        exit;
    }
    
    $contenido = file_get_contents($phpIniWeb);
    if ($contenido === false) {
        echo "<p style='color: red;'>❌ Error leyendo el archivo</p>";
        exit;
    }
    
    echo "<p>✅ Archivo leído correctamente (" . number_format(strlen($contenido)) . " caracteres)</p>";
    
    echo "<h2>🔍 PASO 3: Analizando configuración IMAP</h2>";
    
    $lineas = explode("\n", $contenido);
    $modificado = false;
    $lineaEncontrada = false;
    $numeroLinea = 0;
    
    foreach ($lineas as $i => &$linea) {
        $numeroLinea = $i + 1;
        
        // Buscar línea comentada de IMAP
        if (preg_match('/^\s*;extension=imap\s*$/i', $linea)) {
            echo "<p style='color: orange;'>⚠️ Encontrada línea comentada en línea {$numeroLinea}: <code>{$linea}</code></p>";
            $linea = 'extension=imap';
            echo "<p style='color: green;'>🔧 Cambiada a: <code>{$linea}</code></p>";
            $modificado = true;
            $lineaEncontrada = true;
            break;
        }
        // Verificar si ya está habilitado
        elseif (preg_match('/^\s*extension=imap\s*$/i', $linea)) {
            echo "<p style='color: green;'>✅ IMAP ya está habilitado en línea {$numeroLinea}: <code>{$linea}</code></p>";
            $lineaEncontrada = true;
            break;
        }
    }
    
    if (!$lineaEncontrada) {
        echo "<p style='color: blue;'>ℹ️ No se encontró línea de IMAP, agregándola...</p>";
        
        // Buscar una buena ubicación para agregar
        $ubicacionInsercion = -1;
        foreach ($lineas as $i => $linea) {
            if (strpos($linea, 'extension=') !== false) {
                $ubicacionInsercion = $i + 1;
            }
        }
        
        if ($ubicacionInsercion > 0) {
            array_splice($lineas, $ubicacionInsercion, 0, ['extension=imap']);
            echo "<p style='color: green;'>✅ Línea agregada después de otras extensiones</p>";
        } else {
            $lineas[] = '';
            $lineas[] = '; IMAP extension agregada automáticamente';
            $lineas[] = 'extension=imap';
            echo "<p style='color: green;'>✅ Línea agregada al final del archivo</p>";
        }
        $modificado = true;
    }
    
    echo "<h2>💾 PASO 4: Guardando cambios</h2>";
    
    if ($modificado) {
        // Crear backup
        $backupPath = $phpIniWeb . '.backup.' . date('Y-m-d_H-i-s');
        if (copy($phpIniWeb, $backupPath)) {
            echo "<p style='color: green;'>✅ Backup creado: {$backupPath}</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ No se pudo crear backup (continuando...)</p>";
        }
        
        // Guardar archivo modificado
        $nuevoContenido = implode("\n", $lineas);
        
        if (file_put_contents($phpIniWeb, $nuevoContenido) !== false) {
            echo "<p style='color: green; font-size: 16px;'>✅ <strong>Archivo php.ini actualizado exitosamente</strong></p>";
        } else {
            echo "<p style='color: red;'>❌ Error guardando php.ini</p>";
            echo "<p><strong>Posibles causas:</strong></p>";
            echo "<ul>";
            echo "<li>Permisos insuficientes</li>";
            echo "<li>Archivo en uso</li>";
            echo "<li>Antivirus bloqueando</li>";
            echo "</ul>";
            exit;
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ No se requieren cambios en php.ini</p>";
    }
    
    echo "<h2>🔄 PASO 5: Reiniciando Apache</h2>";
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>⚠️ ACCIÓN REQUERIDA:</strong></p>";
    echo "<ol>";
    echo "<li>Ve a <strong>XAMPP Control Panel</strong></li>";
    echo "<li>Click <strong>'Stop'</strong> en Apache</li>";
    echo "<li>Espera 5 segundos</li>";
    echo "<li>Click <strong>'Start'</strong> en Apache</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h2>🧪 PASO 6: Verificación</h2>";
    echo "<p>Después de reiniciar Apache:</p>";
    echo "<p><a href='diagnostico_imap_avanzado.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔍 Verificar IMAP</a></p>";
    
    if ($modificado) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3 style='color: #155724;'>🎉 ¡Modificación completada!</h3>";
        echo "<p>Se ha habilitado IMAP en: <strong>{$phpIniWeb}</strong></p>";
        echo "<p><strong>Siguiente paso:</strong> Reiniciar Apache y verificar</p>";
        echo "</div>";
    }
    
    echo "<h2>🎯 Una vez que IMAP funcione:</h2>";
    echo "<ul>";
    echo "<li><a href='/dian'>🏠 Dashboard DIAN</a></li>";
    echo "<li><a href='/dian/configuracion'>⚙️ Configuración DIAN</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
