<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Producto;

echo "🔍 ANALIZANDO PRODUCTOS PARA IDENTIFICAR SERVICIOS\n";
echo "================================================\n\n";

$productos = Producto::take(20)->get();

echo "📊 Productos encontrados: " . $productos->count() . "\n\n";

$posiblesServicios = [];
$productosNormales = [];

foreach ($productos as $producto) {
    echo "ID: {$producto->id}\n";
    echo "Código: {$producto->codigo}\n";
    echo "Nombre: {$producto->nombre}\n";
    echo "Stock: {$producto->stock}\n";
    echo "Unidad: {$producto->unidad_medida}\n";
    echo "Precio: $" . number_format($producto->precio_venta) . "\n";
    
    // Criterios para identificar servicios:
    // 1. Palabras clave en el nombre
    $nombreLower = strtolower($producto->nombre);
    $esServicio = false;
    
    if (strpos($nombreLower, 'servicio') !== false ||
        strpos($nombreLower, 'instalacion') !== false ||
        strpos($nombreLower, 'instalación') !== false ||
        strpos($nombreLower, 'mantenimiento') !== false ||
        strpos($nombreLower, 'reparacion') !== false ||
        strpos($nombreLower, 'reparación') !== false ||
        strpos($nombreLower, 'soporte') !== false ||
        strpos($nombreLower, 'configuracion') !== false ||
        strpos($nombreLower, 'configuración') !== false ||
        strpos($nombreLower, 'mano de obra') !== false ||
        strpos($nombreLower, 'licencia') !== false ||
        strpos($nombreLower, 'internet') !== false ||
        strpos($nombreLower, 'kaspersky') !== false ||
        strpos($nombreLower, 'office') !== false ||
        strpos($nombreLower, 'windows') !== false ||
        strpos($nombreLower, 'implementacion') !== false ||
        strpos($nombreLower, 'implementación') !== false) {
        $esServicio = true;
        $posiblesServicios[] = $producto;
        echo "🔧 POSIBLE SERVICIO\n";
    } else {
        $productosNormales[] = $producto;
        echo "📦 PRODUCTO FÍSICO\n";
    }
    
    echo "---\n";
}

echo "\n📊 RESUMEN:\n";
echo "==========\n";
echo "🔧 Posibles servicios: " . count($posiblesServicios) . "\n";
echo "📦 Productos físicos: " . count($productosNormales) . "\n\n";

if (count($posiblesServicios) > 0) {
    echo "🔧 SERVICIOS IDENTIFICADOS:\n";
    foreach ($posiblesServicios as $servicio) {
        echo "   - {$servicio->codigo}: {$servicio->nombre}\n";
    }
    echo "\n";
}

echo "💡 ESTRATEGIA PROPUESTA:\n";
echo "========================\n";
echo "1. Identificar servicios por palabras clave en el nombre\n";
echo "2. Permitir edición de precio solo para servicios\n";
echo "3. Mantener precio fijo para productos físicos\n";
echo "4. Enviar precios editados a Alegra en la facturación\n";

?>
