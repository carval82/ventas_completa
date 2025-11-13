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

// Buscar CREATE TABLE ventas
$lines = explode("\n", $sqlContent);
$inVentasCreate = false;
$columnasBackup = [];

foreach ($lines as $line) {
    $line = trim($line);
    
    if (preg_match('/^CREATE TABLE.*`ventas`/i', $line)) {
        echo "✓ CREATE TABLE ventas encontrado\n\n";
        $inVentasCreate = true;
        continue;
    }
    
    if ($inVentasCreate) {
        // Extraer columnas
        if (preg_match('/^\s*`([^`]+)`/', $line, $match)) {
            $columnasBackup[] = $match[1];
        }
        
        // Termina la definición de la tabla
        if (strpos($line, ') ENGINE=') !== false || strpos($line, ');') !== false) {
            break;
        }
    }
}

echo "Columnas en el BACKUP:\n";
echo "======================\n";
foreach ($columnasBackup as $i => $col) {
    echo sprintf("%2d. %s\n", $i + 1, $col);
}

echo "\nTotal columnas en backup: " . count($columnasBackup) . "\n";
echo "\n";

// Columnas en BD actual
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;

$columnasActuales = Schema::getColumnListing('ventas');

echo "Columnas en BD ACTUAL:\n";
echo "======================\n";
foreach ($columnasActuales as $i => $col) {
    echo sprintf("%2d. %s\n", $i + 1, $col);
}

echo "\nTotal columnas en BD actual: " . count($columnasActuales) . "\n";
echo "\n";

// Comparar
$columnasFaltantes = array_diff($columnasActuales, $columnasBackup);
$columnasExtra = array_diff($columnasBackup, $columnasActuales);

if (count($columnasFaltantes) > 0) {
    echo "⚠ Columnas NUEVAS en BD (no están en backup):\n";
    foreach ($columnasFaltantes as $col) {
        echo "  + $col\n";
    }
    echo "\n";
}

if (count($columnasExtra) > 0) {
    echo "⚠ Columnas ELIMINADAS (están en backup pero no en BD):\n";
    foreach ($columnasExtra as $col) {
        echo "  - $col\n";
    }
    echo "\n";
}

if (count($columnasFaltantes) == 0 && count($columnasExtra) == 0) {
    echo "✓ Las columnas coinciden perfectamente\n";
} else {
    echo "✗ HAY DIFERENCIAS\n";
    echo "El INSERT sin columnas especificadas FALLARÁ porque:\n";
    echo "- Backup tiene " . count($columnasBackup) . " valores\n";
    echo "- BD actual espera " . count($columnasActuales) . " valores\n";
}
