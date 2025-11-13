<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tablas = [
    'empresas',
    'productos', 
    'clientes',
    'ventas',
    'detalle_ventas',
    'proveedores',
    'categorias',
    'marcas',
    'movimientos_contables',
    'comprobantes',
    'plan_cuentas',
    'configuracion_contable'
];

echo "Conteo de registros por tabla:\n";
echo "==============================\n\n";

$total = 0;
$detalles = [];

foreach ($tablas as $tabla) {
    if (Schema::hasTable($tabla)) {
        $count = DB::table($tabla)->count();
        $detalles[$tabla] = $count;
        $total += $count;
        echo sprintf("%-30s %5d\n", $tabla . ':', $count);
    }
}

echo "\n==============================\n";
echo sprintf("%-30s %5d\n", "TOTAL:", $total);
echo "==============================\n";
