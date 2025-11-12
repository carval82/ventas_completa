<?php

echo "=== HABILITAR EXTENSIÓN IMAP EN XAMPP ===\n\n";

try {
    // 1. Verificar estado actual
    echo "🔍 1. VERIFICANDO ESTADO ACTUAL...\n";
    
    if (extension_loaded('imap')) {
        echo "  ✅ IMAP ya está habilitado\n";
        echo "  🎉 No se requiere ninguna acción\n";
        exit(0);
    } else {
        echo "  ❌ IMAP no está habilitado\n";
        echo "  🔧 Procediendo a habilitarlo...\n";
    }
    
    // 2. Localizar php.ini
    echo "\n📄 2. LOCALIZANDO ARCHIVO PHP.INI...\n";
    
    $phpIniPath = php_ini_loaded_file();
    echo "  📍 Archivo php.ini: {$phpIniPath}\n";
    
    if (!$phpIniPath || !file_exists($phpIniPath)) {
        echo "  ❌ No se pudo localizar php.ini\n";
        echo "  🔍 Rutas comunes en XAMPP:\n";
        echo "     - C:\\xampp\\php\\php.ini\n";
        echo "     - C:\\xampp\\apache\\bin\\php.ini\n";
        
        // Intentar rutas comunes
        $rutasComunes = [
            'C:\\xampp\\php\\php.ini',
            'C:\\xampp\\apache\\bin\\php.ini'
        ];
        
        foreach ($rutasComunes as $ruta) {
            if (file_exists($ruta)) {
                $phpIniPath = $ruta;
                echo "  ✅ Encontrado en: {$ruta}\n";
                break;
            }
        }
        
        if (!$phpIniPath) {
            echo "  ❌ No se pudo encontrar php.ini automáticamente\n";
            echo "  📝 INSTRUCCIONES MANUALES:\n";
            echo "     1. Localiza tu archivo php.ini\n";
            echo "     2. Busca la línea: ;extension=imap\n";
            echo "     3. Cámbiala a: extension=imap\n";
            echo "     4. Reinicia Apache\n";
            exit(1);
        }
    }
    
    // 3. Leer contenido actual
    echo "\n📖 3. LEYENDO CONTENIDO ACTUAL...\n";
    
    if (!is_readable($phpIniPath)) {
        echo "  ❌ No se puede leer el archivo php.ini\n";
        echo "  🔧 Ejecuta este script como administrador\n";
        exit(1);
    }
    
    $contenido = file_get_contents($phpIniPath);
    
    if ($contenido === false) {
        echo "  ❌ Error leyendo php.ini\n";
        exit(1);
    }
    
    echo "  ✅ Archivo leído correctamente\n";
    echo "  📊 Tamaño: " . number_format(strlen($contenido)) . " caracteres\n";
    
    // 4. Buscar y modificar línea IMAP
    echo "\n🔍 4. BUSCANDO CONFIGURACIÓN IMAP...\n";
    
    $lineas = explode("\n", $contenido);
    $modificado = false;
    $lineaEncontrada = false;
    
    foreach ($lineas as $i => &$linea) {
        // Buscar línea comentada de IMAP
        if (preg_match('/^\s*;extension=imap\s*$/i', $linea)) {
            echo "  ✅ Encontrada línea comentada: {$linea}\n";
            $linea = 'extension=imap';
            echo "  🔧 Cambiada a: {$linea}\n";
            $modificado = true;
            $lineaEncontrada = true;
            break;
        }
        // Verificar si ya está habilitado
        elseif (preg_match('/^\s*extension=imap\s*$/i', $linea)) {
            echo "  ✅ IMAP ya está habilitado en la línea: {$linea}\n";
            $lineaEncontrada = true;
            break;
        }
    }
    
    if (!$lineaEncontrada) {
        echo "  ⚠️ No se encontró línea de IMAP, agregándola...\n";
        
        // Buscar sección de extensiones
        $seccionExtensiones = false;
        foreach ($lineas as $i => $linea) {
            if (strpos($linea, '[PHP]') !== false || strpos($linea, 'extension=') !== false) {
                $seccionExtensiones = $i;
                break;
            }
        }
        
        if ($seccionExtensiones !== false) {
            // Insertar después de la primera extensión encontrada
            array_splice($lineas, $seccionExtensiones + 1, 0, 'extension=imap');
            echo "  ✅ Línea agregada en sección de extensiones\n";
            $modificado = true;
        } else {
            // Agregar al final
            $lineas[] = '';
            $lineas[] = '; IMAP extension habilitada automáticamente';
            $lineas[] = 'extension=imap';
            echo "  ✅ Línea agregada al final del archivo\n";
            $modificado = true;
        }
    }
    
    // 5. Guardar cambios si es necesario
    if ($modificado) {
        echo "\n💾 5. GUARDANDO CAMBIOS...\n";
        
        // Crear backup
        $backupPath = $phpIniPath . '.backup.' . date('Y-m-d_H-i-s');
        if (copy($phpIniPath, $backupPath)) {
            echo "  ✅ Backup creado: {$backupPath}\n";
        } else {
            echo "  ⚠️ No se pudo crear backup\n";
        }
        
        // Guardar archivo modificado
        $nuevoContenido = implode("\n", $lineas);
        
        if (file_put_contents($phpIniPath, $nuevoContenido) !== false) {
            echo "  ✅ Archivo php.ini actualizado exitosamente\n";
        } else {
            echo "  ❌ Error guardando php.ini\n";
            echo "  🔧 Verifica permisos o ejecuta como administrador\n";
            exit(1);
        }
    } else {
        echo "\n✅ No se requieren cambios en php.ini\n";
    }
    
    // 6. Instrucciones finales
    echo "\n🎯 6. PASOS FINALES:\n";
    echo "  1. 🔄 Reinicia Apache en XAMPP Control Panel\n";
    echo "  2. 🌐 Ve a: http://127.0.0.1:8000/verificar_imap_web.php\n";
    echo "  3. ✅ Verifica que IMAP aparezca como habilitado\n";
    echo "  4. 🧪 Prueba la conexión en el módulo DIAN\n";
    
    echo "\n📍 VERIFICACIÓN:\n";
    echo "  🔗 Verificar IMAP: http://127.0.0.1:8000/verificar_imap_web.php\n";
    echo "  🏠 Módulo DIAN: http://127.0.0.1:8000/dian\n";
    
    if ($modificado) {
        echo "\n⚠️ IMPORTANTE:\n";
        echo "  🔄 DEBES REINICIAR APACHE para que los cambios tengan efecto\n";
        echo "  📍 XAMPP Control Panel → Apache → Stop → Start\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n✅ Proceso completado.\n";
