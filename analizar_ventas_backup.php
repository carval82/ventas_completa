<?php
$backupZip = 'storage/app/backups/2025-09-30_19-06-56_backup.zip';

// Extraer el SQL del ZIP
$zip = new ZipArchive();
if ($zip->open($backupZip) === TRUE) {
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);
        if (pathinfo($filename, PATHINFO_EXTENSION) === 'sql') {
            $sqlContent = $zip->getFromIndex($i);
            break;
        }
    }
    $zip->close();
}

if (!isset($sqlContent)) {
    die("No se encontró archivo SQL en el backup\n");
}

echo "Analizando INSERT de tabla VENTAS:\n";
echo "====================================\n\n";

// Buscar INSERT de ventas
$lines = explode("\n", $sqlContent);
$inVentasInsert = false;
$ventasInserts = [];
$currentInsert = '';
$insertCount = 0;

foreach ($lines as $lineNum => $line) {
    $line = trim($line);
    
    // Detectar inicio de INSERT INTO ventas
    if (preg_match('/^INSERT INTO `ventas`/i', $line)) {
        if ($currentInsert) {
            // Guardar el INSERT anterior
            $ventasInserts[] = $currentInsert;
        }
        
        $insertCount++;
        $currentInsert = $line;
        $inVentasInsert = true;
        
        echo "INSERT #$insertCount encontrado en línea " . ($lineNum + 1) . "\n";
        echo "  Primeros 150 caracteres: " . substr($line, 0, 150) . "...\n";
        
        // Verificar si tiene columnas especificadas
        if (preg_match('/INSERT INTO `ventas`\s*(\([^)]+\))?/i', $line, $match)) {
            if (isset($match[1]) && !empty($match[1])) {
                echo "  ✓ Tiene columnas especificadas\n";
                // Contar columnas
                $columnas = explode(',', trim($match[1], '()'));
                echo "  Número de columnas: " . count($columnas) . "\n";
            } else {
                echo "  ✗ SIN columnas especificadas (usa todas por defecto)\n";
            }
        }
        
        // Si termina con ; en la misma línea
        if (substr($line, -1) === ';') {
            $registros = substr_count($line, '),(') + 1;
            echo "  Registros en este INSERT: $registros\n";
            echo "  ✓ INSERT completo en una línea\n\n";
            $ventasInserts[] = $currentInsert;
            $currentInsert = '';
            $inVentasInsert = false;
        } else {
            echo "  ⚠ INSERT multilínea (continúa...)\n";
        }
        
    } elseif ($inVentasInsert) {
        // Continuar acumulando el INSERT
        $currentInsert .= ' ' . $line;
        
        // Si termina con ;
        if (substr($line, -1) === ';') {
            $registros = substr_count($currentInsert, '),(') + 1;
            echo "  Registros en este INSERT: $registros\n";
            echo "  ✓ INSERT completado\n\n";
            $ventasInserts[] = $currentInsert;
            $currentInsert = '';
            $inVentasInsert = false;
        }
    }
}

// Guardar último INSERT si quedó pendiente
if ($currentInsert) {
    $ventasInserts[] = $currentInsert;
}

echo "====================================\n";
echo "Total de INSERT INTO ventas: " . count($ventasInserts) . "\n";
echo "====================================\n\n";

// Contar registros totales
$totalRegistros = 0;
foreach ($ventasInserts as $i => $insert) {
    $registros = substr_count($insert, '),(') + 1;
    $totalRegistros += $registros;
    echo "INSERT #" . ($i + 1) . ": $registros registro(s)\n";
}

echo "\n";
echo "Total de registros de ventas: $totalRegistros\n";

if ($totalRegistros != 31) {
    echo "⚠ ADVERTENCIA: Se esperaban 31 registros pero se encontraron $totalRegistros\n";
}
