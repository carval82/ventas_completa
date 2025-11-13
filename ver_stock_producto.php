<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$p = DB::table('productos')->where('id', 142)->first();

echo "Producto ID: 142\n";
echo "Nombre: {$p->nombre}\n";
echo "Stock: ";
var_dump($p->stock);
echo "Tipo de dato: " . gettype($p->stock) . "\n";
echo "Stock convertido a float: " . (float)($p->stock ?? 0) . "\n";
