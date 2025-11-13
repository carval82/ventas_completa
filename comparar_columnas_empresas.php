<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;

// Columnas en el backup
$columnasBackup = [
    'id',
    'nombre_comercial',
    'razon_social',
    'nit',
    'direccion',
    'telefono',
    'email',
    'sitio_web',
    'logo',
    'created_at',
    'updated_at',
    'regimen_tributario',
    'resolucion_facturacion',
    'prefijo_factura',
    'id_resolucion_alegra',
    'id_cliente_generico_alegra',
    'fecha_resolucion',
    'fecha_vencimiento_resolucion',
    'factura_electronica_habilitada',
    'alegra_email',
    'alegra_token',
    'alegra_multiples_impuestos'
];

// Columnas en la BD actual
$columnasActuales = Schema::getColumnListing('empresas');

echo "Comparación de columnas tabla EMPRESAS:\n";
echo "========================================\n\n";

echo "Columnas en BACKUP: " . count($columnasBackup) . "\n";
echo "Columnas en BD ACTUAL: " . count($columnasActuales) . "\n\n";

// Columnas que están en la BD pero NO en el backup
$columnasFaltantes = array_diff($columnasActuales, $columnasBackup);
if (count($columnasFaltantes) > 0) {
    echo "⚠ Columnas en BD ACTUAL que NO están en el backup:\n";
    foreach ($columnasFaltantes as $col) {
        echo "  - $col (NUEVA)\n";
    }
    echo "\n";
}

// Columnas que están en el backup pero NO en la BD
$columnasExtra = array_diff($columnasBackup, $columnasActuales);
if (count($columnasExtra) > 0) {
    echo "⚠ Columnas en BACKUP que NO están en la BD actual:\n";
    foreach ($columnasExtra as $col) {
        echo "  - $col (ELIMINADA)\n";
    }
    echo "\n";
}

if (count($columnasFaltantes) == 0 && count($columnasExtra) == 0) {
    echo "✓ Las columnas coinciden perfectamente\n\n";
} else {
    echo "✗ HAY DIFERENCIAS - El INSERT sin columnas FALLARÁ\n\n";
    echo "SOLUCIÓN:\n";
    echo "=========\n";
    echo "El código de restauración debe:\n";
    echo "1. Detectar que el INSERT no tiene columnas\n";
    echo "2. Parsear los valores del INSERT\n";
    echo "3. Mapear solo las columnas que existen en ambas\n";
    echo "4. Reconstruir el INSERT con las columnas especificadas\n";
}
