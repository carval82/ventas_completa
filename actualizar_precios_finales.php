<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Producto;
use Illuminate\Support\Facades\DB;

echo "Actualizando precios finales de productos...\n";

// Actualizar todos los productos para asegurar que precio_final sea correcto
$productos = Producto::all();
$contador = 0;

foreach ($productos as $producto) {
    // Si no tiene IVA, asignar 19% por defecto
    if ($producto->iva === null || $producto->iva == 0) {
        $producto->iva = 19;
    }
    
    // Calcular precio final con IVA incluido
    $producto->precio_final = $producto->precio_venta * (1 + ($producto->iva / 100));
    
    // Calcular valor del IVA
    $producto->valor_iva = $producto->precio_venta * ($producto->iva / 100);
    
    $producto->save();
    $contador++;
}

echo "Se han actualizado $contador productos correctamente.\n";
echo "Todos los productos ahora tienen el precio_final calculado correctamente.\n";
