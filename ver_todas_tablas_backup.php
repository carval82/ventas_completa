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

// Buscar todas las tablas con INSERT
$lines = explode("\n", $sqlContent);
$tablasConInsert = [];

foreach ($lines as $line) {
    $line = trim($line);
    
    // Buscar INSERT INTO
    if (preg_match('/^INSERT INTO `([^`]+)`/i', $line, $match)) {
        $tabla = $match[1];
        
        if (!isset($tablasConInsert[$tabla])) {
            $tablasConInsert[$tabla] = 0;
        }
        
        // Contar registros aproximados en esta línea
        $registros = substr_count($line, '),(') + 1;
        $tablasConInsert[$tabla] += $registros;
    }
}

echo "Tablas con datos en el backup:\n";
echo "===============================\n\n";

$totalRegistros = 0;
foreach ($tablasConInsert as $tabla => $registros) {
    echo sprintf("%-40s %6d registros\n", $tabla, $registros);
    $totalRegistros += $registros;
}

echo "\n===============================\n";
echo sprintf("%-40s %6d registros\n", "TOTAL:", $totalRegistros);
echo "===============================\n";
