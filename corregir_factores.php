<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Producto;

echo "🔧 Corrigiendo factores de conversión...\n\n";

// Producto base (unidad) - debe tener factor 1.0
$productoBase = Producto::find(43);
if ($productoBase) {
    $productoBase->update(['factor_stock' => 1.0]);
    echo "✅ Producto base (ID: 43) - Factor: 1.0\n";
}

// Producto libra - debe tener factor 25.0 (1 unidad = 25 lb)
$productoLibra = Producto::where('unidad_medida', 'lb')
    ->where('producto_base_id', 43)
    ->first();
    
if ($productoLibra) {
    $productoLibra->update(['factor_stock' => 25.0]);
    echo "✅ Producto libra (ID: {$productoLibra->id}) - Factor: 25.0\n";
}

// Producto kilo - debe tener factor 11.34 (1 unidad = 11.34 kg)
$productoKilo = Producto::where('unidad_medida', 'kg')
    ->where('producto_base_id', 43)
    ->first();
    
if ($productoKilo) {
    $productoKilo->update(['factor_stock' => 11.34]);
    echo "✅ Producto kilo (ID: {$productoKilo->id}) - Factor: 11.34\n";
}

echo "\n🧮 FACTORES CORREGIDOS:\n";
echo "• 1 unidad (base) × 1.0 = 1 unidad\n";
echo "• 1 unidad × 25.0 = 25 libras\n";
echo "• 1 unidad × 11.34 = 11.34 kilos\n\n";

echo "🎯 CONVERSIÓN ESPERADA:\n";
echo "• 1 unidad → 25 libras (factor: 25.0)\n";
echo "• Precio: $50,000 ÷ 25 = $2,000 por libra\n\n";

echo "✅ Factores corregidos. Prueba de nuevo!\n";
