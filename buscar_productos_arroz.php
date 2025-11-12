<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Producto;
use App\Models\ProductoEquivalencia;

echo "🔍 Buscando productos con 'arroz' en el nombre...\n\n";

$productos = Producto::where('nombre', 'like', '%arroz%')->get(['id', 'nombre', 'unidad_medida']);

if ($productos->count() > 0) {
    foreach ($productos as $producto) {
        $equivalencias = ProductoEquivalencia::where('producto_id', $producto->id)->count();
        echo "ID: {$producto->id} - {$producto->nombre} (Unidad: {$producto->unidad_medida}) - Equivalencias: {$equivalencias}\n";
    }
} else {
    echo "No se encontraron productos con 'arroz' en el nombre.\n";
}

echo "\n🔍 Buscando productos que contengan '25' y 'l'...\n\n";

$productos25l = Producto::where('nombre', 'like', '%25%')
                       ->where('nombre', 'like', '%l%')
                       ->get(['id', 'nombre', 'unidad_medida']);

if ($productos25l->count() > 0) {
    foreach ($productos25l as $producto) {
        $equivalencias = ProductoEquivalencia::where('producto_id', $producto->id)->count();
        echo "ID: {$producto->id} - {$producto->nombre} (Unidad: {$producto->unidad_medida}) - Equivalencias: {$equivalencias}\n";
    }
} else {
    echo "No se encontraron productos con '25' y 'l'.\n";
}

echo "\n📋 Productos con equivalencias existentes:\n\n";

$productosConEquivalencias = ProductoEquivalencia::select('producto_id')
    ->distinct()
    ->get()
    ->map(function($pe) {
        $producto = Producto::find($pe->producto_id);
        return [
            'id' => $pe->producto_id,
            'nombre' => $producto ? $producto->nombre : 'No encontrado',
            'equivalencias' => ProductoEquivalencia::where('producto_id', $pe->producto_id)->count()
        ];
    });

foreach ($productosConEquivalencias as $item) {
    echo "ID: {$item['id']} - {$item['nombre']} - Equivalencias: {$item['equivalencias']}\n";
}
