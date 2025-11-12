<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Producto;
use App\Models\ProductoEquivalencia;

echo "🌾 Creando equivalencias para 'arroz para x 25 l' (ID: 43)...\n\n";

$producto = Producto::find(43);

if (!$producto) {
    echo "❌ Producto ID 43 no encontrado.\n";
    exit;
}

echo "✅ Producto encontrado: {$producto->nombre}\n";
echo "📦 Unidad actual: {$producto->unidad_medida}\n\n";

// Equivalencias para arroz líquido de 25 litros
$equivalencias = [
    // 1 unidad = 25 litros (según el nombre del producto)
    [
        'unidad_origen' => 'unidad',
        'unidad_destino' => 'l',
        'factor_conversion' => 25.0000,
        'descripcion' => '1 unidad contiene 25 litros'
    ],
    [
        'unidad_origen' => 'l',
        'unidad_destino' => 'unidad',
        'factor_conversion' => 0.0400,
        'descripcion' => '1 litro = 0.04 unidades'
    ],
    
    // 1 unidad = 25000 ml
    [
        'unidad_origen' => 'unidad',
        'unidad_destino' => 'ml',
        'factor_conversion' => 25000.0000,
        'descripcion' => '1 unidad contiene 25000 mililitros'
    ],
    [
        'unidad_origen' => 'ml',
        'unidad_destino' => 'unidad',
        'factor_conversion' => 0.00004,
        'descripcion' => '1 mililitro = 0.00004 unidades'
    ],
    
    // Conversiones cruzadas l <-> ml
    [
        'unidad_origen' => 'l',
        'unidad_destino' => 'ml',
        'factor_conversion' => 1000.0000,
        'descripcion' => '1 litro = 1000 mililitros'
    ],
    [
        'unidad_origen' => 'ml',
        'unidad_destino' => 'l',
        'factor_conversion' => 0.0010,
        'descripcion' => '1 mililitro = 0.001 litros'
    ],
    
    // Si se vende por galones también
    [
        'unidad_origen' => 'unidad',
        'unidad_destino' => 'galon',
        'factor_conversion' => 6.6043,
        'descripcion' => '1 unidad = 6.6043 galones (25L ÷ 3.785L/galón)'
    ],
    [
        'unidad_origen' => 'galon',
        'unidad_destino' => 'unidad',
        'factor_conversion' => 0.1514,
        'descripcion' => '1 galón = 0.1514 unidades'
    ]
];

echo "📋 Creando equivalencias:\n";

foreach ($equivalencias as $equiv) {
    ProductoEquivalencia::create([
        'producto_id' => 43,
        'unidad_origen' => $equiv['unidad_origen'],
        'unidad_destino' => $equiv['unidad_destino'],
        'factor_conversion' => $equiv['factor_conversion'],
        'descripcion' => $equiv['descripcion'],
        'activo' => true
    ]);
    
    echo "  ✅ {$equiv['descripcion']}\n";
}

echo "\n🎉 ¡Equivalencias creadas exitosamente!\n\n";

echo "📊 CONVERSIONES DISPONIBLES:\n";
echo "• 1 unidad → 25 litros\n";
echo "• 1 unidad → 25,000 ml\n";
echo "• 1 unidad → 6.6043 galones\n";
echo "• 50 litros → 2 unidades\n";
echo "• 12.5 litros → 0.5 unidades\n\n";

echo "🧪 INSTRUCCIONES PARA PROBAR:\n";
echo "1. Ir a /ventas/create\n";
echo "2. Buscar producto: 'arroz para x 25 l'\n";
echo "3. Agregar 1 unidad (cantidad por defecto)\n";
echo "4. Cambiar unidad de 'UNIDAD' a 'L' → Debe mostrar 25 litros\n";
echo "5. Cambiar unidad de 'L' a 'ML' → Debe mostrar 25,000 ml\n";
echo "6. Cambiar unidad de 'ML' a 'GALON' → Debe mostrar 6.6043 galones\n\n";
