<?php
// Script para diagnosticar problemas con la tabla empresas en el backup

$backupZip = 'storage/app/backups/2025-09-30_19-06-56_backup.zip';
$sqlFile = null;

// Extraer el SQL del ZIP
$zip = new ZipArchive();
if ($zip->open($backupZip) === TRUE) {
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);
        if (pathinfo($filename, PATHINFO_EXTENSION) === 'sql') {
            $sqlContent = $zip->getFromIndex($i);
            $sqlFile = $filename;
            break;
        }
    }
    $zip->close();
}

if (!$sqlContent) {
    die("No se encontró archivo SQL en el backup\n");
}

echo "Archivo SQL encontrado: $sqlFile\n\n";

// Buscar la tabla empresas
$lines = explode("\n", $sqlContent);
$inEmpresasTable = false;
$createTableFound = false;
$insertCount = 0;
$columnsInInsert = [];

foreach ($lines as $lineNum => $line) {
    $line = trim($line);
    
    // Buscar CREATE TABLE empresas
    if (preg_match('/CREATE TABLE.*`empresas`/i', $line)) {
        echo "✓ CREATE TABLE empresas encontrado en línea " . ($lineNum + 1) . "\n";
        $createTableFound = true;
        $inEmpresasTable = true;
        continue;
    }
    
    // Si estamos en la tabla empresas, mostrar algunas líneas
    if ($inEmpresasTable && $createTableFound) {
        if (preg_match('/^\s*`([^`]+)`/', $line, $match)) {
            echo "  - Columna: {$match[1]}\n";
        }
        
        if (strpos($line, ') ENGINE=') !== false) {
            $inEmpresasTable = false;
            echo "\n";
        }
    }
    
    // Buscar INSERT INTO empresas
    if (preg_match('/^INSERT INTO `empresas`\s*(\([^)]+\))?/i', $line, $match)) {
        $insertCount++;
        echo "✓ INSERT INTO empresas encontrado en línea " . ($lineNum + 1) . "\n";
        
        // Extraer las columnas del INSERT
        if (isset($match[1])) {
            $columnsStr = trim($match[1], '()');
            $columnsInInsert = array_map(function($col) {
                return trim($col, '` ');
            }, explode(',', $columnsStr));
            
            echo "  Columnas en INSERT: " . count($columnsInInsert) . "\n";
            echo "  Primeras columnas: " . implode(', ', array_slice($columnsInInsert, 0, 5)) . "...\n";
            
            // Verificar si tiene las columnas de Alegra
            $alegraColumns = ['alegra_email', 'alegra_token', 'id_resolucion_alegra', 'id_cliente_generico_alegra'];
            foreach ($alegraColumns as $alegraCol) {
                if (in_array($alegraCol, $columnsInInsert)) {
                    echo "  ✓ Columna {$alegraCol} presente\n";
                } else {
                    echo "  ✗ Columna {$alegraCol} FALTANTE\n";
                }
            }
        } else {
            echo "  ⚠ INSERT sin especificar columnas (usa todas las columnas por defecto)\n";
        }
        
        // Mostrar parte del INSERT
        echo "  Primeros 200 caracteres:\n";
        echo "  " . substr($line, 0, 200) . "...\n\n";
    }
}

echo "\n";
echo "Resumen:\n";
echo "========\n";
echo "CREATE TABLE empresas: " . ($createTableFound ? "✓ Encontrado" : "✗ NO encontrado") . "\n";
echo "INSERT INTO empresas: $insertCount encontrados\n";
echo "\n";

if ($insertCount == 0) {
    echo "⚠ PROBLEMA: No se encontraron INSERT para la tabla empresas\n";
    echo "Esto significa que el backup no tiene datos de empresas.\n";
} else {
    echo "✓ El backup SÍ contiene datos de empresas\n";
}
