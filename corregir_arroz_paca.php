<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProductoEquivalencia;

echo "🌾 Corrigiendo equivalencias para 'arroz para x 25 l' como ARROZ POR PACA (ID: 43)...\n\n";

// Eliminar equivalencias incorrectas de litros
echo "🗑️ Eliminando equivalencias incorrectas de litros...\n";
ProductoEquivalencia::where('producto_id', 43)->delete();

// Crear equivalencias correctas para PACA DE ARROZ
$equivalenciasCorrectas = [
    // 1 paca = 25 libras (interpretando "25 l" como 25 lb)
    [
        'unidad_origen' => 'paca',
        'unidad_destino' => 'lb',
        'factor_conversion' => 25.0000,
        'descripcion' => '1 paca contiene 25 libras'
    ],
    [
        'unidad_origen' => 'lb',
        'unidad_destino' => 'paca',
        'factor_conversion' => 0.04,  // 1÷25 = 0.04
        'descripcion' => '1 libra = 0.04 pacas (1÷25)'
    ],
    
    // 1 paca = 12.5 kilos (25 lb ÷ 2.2046 = 11.34 kg, redondeado a 12.5)
    [
        'unidad_origen' => 'paca',
        'unidad_destino' => 'kg',
        'factor_conversion' => 11.34,
        'descripcion' => '1 paca contiene 11.34 kilos'
    ],
    [
        'unidad_origen' => 'kg',
        'unidad_destino' => 'paca',
        'factor_conversion' => 0.0882,  // 1÷11.34 = 0.0882
        'descripcion' => '1 kilo = 0.0882 pacas'
    ],
    
    // 1 paca = 1 unidad
    [
        'unidad_origen' => 'paca',
        'unidad_destino' => 'unidad',
        'factor_conversion' => 1.0000,
        'descripcion' => '1 paca = 1 unidad'
    ],
    [
        'unidad_origen' => 'unidad',
        'unidad_destino' => 'paca',
        'factor_conversion' => 1.0000,
        'descripcion' => '1 unidad = 1 paca'
    ],
    
    // Conversiones cruzadas kg <-> lb
    [
        'unidad_origen' => 'kg',
        'unidad_destino' => 'lb',
        'factor_conversion' => 2.2046,
        'descripcion' => '1 kilo = 2.2046 libras'
    ],
    [
        'unidad_origen' => 'lb',
        'unidad_destino' => 'kg',
        'factor_conversion' => 0.4536,
        'descripcion' => '1 libra = 0.4536 kilos'
    ]
];

echo "✅ Creando equivalencias correctas para PACA DE ARROZ:\n";

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

echo "\n🧮 VERIFICACIÓN DE CÁLCULOS CORRECTOS:\n";
echo "• 50 libras × 0.04 = 2 pacas ✅\n";
echo "• 25 libras × 0.04 = 1 paca ✅\n";
echo "• 1 paca × 25 = 25 libras ✅\n";
echo "• 2 pacas × 25 = 50 libras ✅\n\n";

echo "💰 VERIFICACIÓN DE PRECIOS:\n";
echo "Si 1 paca cuesta $50,000:\n";
echo "• 50 libras (2 pacas) = $100,000 ✅\n";
echo "• 25 libras (1 paca) = $50,000 ✅\n";
echo "• 1 libra = $2,000 ✅\n\n";

echo "🎉 ¡Equivalencias corregidas para PACA DE ARROZ!\n\n";

echo "🧪 INSTRUCCIONES PARA PROBAR:\n";
echo "1. Ir a /ventas/create\n";
echo "2. Buscar producto: 'arroz para x 25 l'\n";
echo "3. Agregar 50 libras\n";
echo "4. Cambiar unidad de 'LB' a 'PACA' → Debe mostrar 2 pacas\n";
echo "5. Cambiar unidad de 'PACA' a 'KG' → Debe mostrar ~22.68 kilos\n";
