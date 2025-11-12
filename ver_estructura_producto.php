<?php
/**
 * Script para verificar la estructura de la tabla productos
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Producto;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "=== Estructura de la tabla productos ===\n\n";

// Obtener columnas de la tabla
$columnas = Schema::getColumnListing('productos');

echo "Columnas de la tabla productos:\n";
foreach ($columnas as $columna) {
    echo "- {$columna}\n";
}

echo "\n=== Ejemplo de producto ===\n\n";

// Obtener un producto de ejemplo
$producto = Producto::first();

if ($producto) {
    echo "ID: {$producto->id}\n";
    foreach ($columnas as $columna) {
        echo "{$columna}: " . json_encode($producto->$columna) . "\n";
    }
} else {
    echo "No hay productos en la base de datos.\n";
}

echo "\n=== Fin del proceso ===\n";
