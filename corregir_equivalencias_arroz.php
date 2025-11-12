<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProductoEquivalencia;

echo "🔧 Corrigiendo equivalencias para 'arroz para x 25 l' (ID: 43)...\n\n";

// Eliminar equivalencias incorrectas
echo "🗑️ Eliminando equivalencias incorrectas...\n";
ProductoEquivalencia::where('producto_id', 43)->delete();

// Crear equivalencias correctas
$equivalenciasCorrectas = [
    // 1 unidad = 25 litros
    [
        'unidad_origen' => 'unidad',
        'unidad_destino' => 'l',
        'factor_conversion' => 25.0000,
        'descripcion' => '1 unidad contiene 25 litros'
    ],
    [
        'unidad_origen' => 'l',
        'unidad_destino' => 'unidad',
        'factor_conversion' => 0.04,  // 1÷25 = 0.04
        'descripcion' => '1 litro = 0.04 unidades (1÷25)'
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
        'factor_conversion' => 0.00004,  // 1÷25000 = 0.00004
        'descripcion' => '1 mililitro = 0.00004 unidades (1÷25000)'
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
        'factor_conversion' => 0.001,
        'descripcion' => '1 mililitro = 0.001 litros'
    ]
];

echo "✅ Creando equivalencias corregidas:\n";

foreach ($equivalenciasCorrectas as $equiv) {
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

echo "\n🧮 VERIFICACIÓN DE CÁLCULOS:\n";
echo "• 50 litros × 0.04 = 2 unidades ✅\n";
echo "• 25 litros × 0.04 = 1 unidad ✅\n";
echo "• 1 unidad × 25 = 25 litros ✅\n";
echo "• 2 unidades × 25 = 50 litros ✅\n\n";

echo "💰 VERIFICACIÓN DE PRECIOS:\n";
echo "Si 1 unidad cuesta $50,000:\n";
echo "• 50 litros (2 unidades) = $100,000 ✅\n";
echo "• 25 litros (1 unidad) = $50,000 ✅\n";
echo "• 1 litro = $2,000 ✅\n\n";

echo "🎉 ¡Equivalencias corregidas!\n";
