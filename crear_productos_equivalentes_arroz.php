<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Producto;
use App\Models\ProductoEquivalencia;

echo "🌾 Creando sistema de productos equivalentes para ARROZ...\n\n";

// Paso 1: Configurar producto existente como producto base
echo "📦 Paso 1: Configurando producto base...\n";

$productoBase = Producto::find(43); // "arroz para x 25 l"

if (!$productoBase) {
    echo "❌ Producto base (ID: 43) no encontrado.\n";
    exit;
}

// Actualizar producto base
$productoBase->update([
    'es_producto_base' => true,
    'factor_stock' => 1.000000, // 1 paca = 1 paca
    'nombre' => 'Arroz por Paca (25 lb)',
    'unidad_medida' => 'paca'
]);

echo "✅ Producto base configurado: {$productoBase->nombre}\n";
echo "   Stock actual: {$productoBase->stock} pacas\n\n";

// Paso 2: Crear producto equivalente por LIBRA
echo "📦 Paso 2: Creando producto por LIBRA...\n";

$productoLibra = Producto::create([
    'codigo' => 'ARROZ-LB-001',
    'nombre' => 'Arroz por Libra',
    'descripcion' => 'Arroz vendido por libra - Equivalente a paca de 25 lb',
    'precio_compra' => 1800, // Precio por libra
    'precio_venta' => 2500,  // Precio por libra
    'precio_final' => 2975,  // Con IVA 19%
    'iva' => 19,
    'valor_iva' => 475,
    'porcentaje_ganancia' => 38.89,
    'stock' => 0, // No maneja stock propio
    'stock_minimo' => 0,
    'unidad_medida' => 'lb',
    'estado' => true,
    'producto_base_id' => $productoBase->id,
    'factor_stock' => 0.04, // 1 libra = 0.04 pacas (1÷25)
    'es_producto_base' => false
]);

echo "✅ Producto por libra creado: {$productoLibra->nombre}\n";
echo "   Precio: $" . number_format($productoLibra->precio_venta) . " por libra\n";
echo "   Factor stock: {$productoLibra->factor_stock} (1 lb = 0.04 pacas)\n\n";

// Paso 3: Crear producto equivalente por KILO
echo "📦 Paso 3: Creando producto por KILO...\n";

$productoKilo = Producto::create([
    'codigo' => 'ARROZ-KG-001',
    'nombre' => 'Arroz por Kilo',
    'descripcion' => 'Arroz vendido por kilo - Equivalente a paca de 25 lb',
    'precio_compra' => 4000, // Precio por kilo
    'precio_venta' => 5500,  // Precio por kilo
    'precio_final' => 6545,  // Con IVA 19%
    'iva' => 19,
    'valor_iva' => 1045,
    'porcentaje_ganancia' => 37.50,
    'stock' => 0, // No maneja stock propio
    'stock_minimo' => 0,
    'unidad_medida' => 'kg',
    'estado' => true,
    'producto_base_id' => $productoBase->id,
    'factor_stock' => 0.0882, // 1 kilo = 0.0882 pacas (1÷11.34)
    'es_producto_base' => false
]);

echo "✅ Producto por kilo creado: {$productoKilo->nombre}\n";
echo "   Precio: $" . number_format($productoKilo->precio_venta) . " por kilo\n";
echo "   Factor stock: {$productoKilo->factor_stock} (1 kg = 0.0882 pacas)\n\n";

// Paso 4: Crear equivalencias automáticas entre productos
echo "📋 Paso 4: Creando equivalencias automáticas...\n";

// Limpiar equivalencias existentes
ProductoEquivalencia::whereIn('producto_id', [$productoBase->id, $productoLibra->id, $productoKilo->id])->delete();

$equivalencias = [
    // PACA (base) ↔ LIBRA
    [
        'producto_id' => $productoBase->id,
        'unidad_origen' => 'paca',
        'unidad_destino' => 'lb',
        'factor_conversion' => 25.0000,
        'descripcion' => '1 paca = 25 libras'
    ],
    [
        'producto_id' => $productoLibra->id,
        'unidad_origen' => 'lb',
        'unidad_destino' => 'paca',
        'factor_conversion' => 0.04,
        'descripcion' => '1 libra = 0.04 pacas'
    ],
    
    // PACA (base) ↔ KILO
    [
        'producto_id' => $productoBase->id,
        'unidad_origen' => 'paca',
        'unidad_destino' => 'kg',
        'factor_conversion' => 11.34,
        'descripcion' => '1 paca = 11.34 kilos'
    ],
    [
        'producto_id' => $productoKilo->id,
        'unidad_origen' => 'kg',
        'unidad_destino' => 'paca',
        'factor_conversion' => 0.0882,
        'descripcion' => '1 kilo = 0.0882 pacas'
    ],
    
    // LIBRA ↔ KILO (conversiones cruzadas)
    [
        'producto_id' => $productoLibra->id,
        'unidad_origen' => 'lb',
        'unidad_destino' => 'kg',
        'factor_conversion' => 0.4536,
        'descripcion' => '1 libra = 0.4536 kilos'
    ],
    [
        'producto_id' => $productoKilo->id,
        'unidad_origen' => 'kg',
        'unidad_destino' => 'lb',
        'factor_conversion' => 2.2046,
        'descripcion' => '1 kilo = 2.2046 libras'
    ]
];

foreach ($equivalencias as $equiv) {
    ProductoEquivalencia::create([
        'producto_id' => $equiv['producto_id'],
        'unidad_origen' => $equiv['unidad_origen'],
        'unidad_destino' => $equiv['unidad_destino'],
        'factor_conversion' => $equiv['factor_conversion'],
        'descripcion' => $equiv['descripcion'],
        'activo' => true
    ]);
    
    echo "  ✅ {$equiv['descripcion']}\n";
}

echo "\n🎉 ¡Sistema de productos equivalentes creado exitosamente!\n\n";

echo "📊 RESUMEN:\n";
echo "┌─────────────────────────────────────────────────────────────┐\n";
echo "│ PRODUCTO BASE: Arroz por Paca (25 lb)                      │\n";
echo "│ Stock: {$productoBase->stock} pacas                                      │\n";
echo "│ Precio: $" . number_format($productoBase->precio_venta) . " por paca                               │\n";
echo "├─────────────────────────────────────────────────────────────┤\n";
echo "│ EQUIVALENTE 1: Arroz por Libra                              │\n";
echo "│ Precio: $" . number_format($productoLibra->precio_venta) . " por libra                                │\n";
echo "│ Factor: 1 lb = 0.04 pacas                                   │\n";
echo "├─────────────────────────────────────────────────────────────┤\n";
echo "│ EQUIVALENTE 2: Arroz por Kilo                               │\n";
echo "│ Precio: $" . number_format($productoKilo->precio_venta) . " por kilo                                 │\n";
echo "│ Factor: 1 kg = 0.0882 pacas                                 │\n";
echo "└─────────────────────────────────────────────────────────────┘\n\n";

echo "🧪 INSTRUCCIONES PARA PROBAR:\n";
echo "1. Ir a /ventas/create\n";
echo "2. Buscar cualquiera de los 3 productos de arroz\n";
echo "3. Agregar cantidad y cambiar unidades\n";
echo "4. Verificar que el stock se descuente del producto base\n\n";

echo "💡 EJEMPLOS DE CONVERSIÓN:\n";
echo "• 50 libras → 2 pacas (50 × 0.04 = 2)\n";
echo "• 22.68 kilos → 2 pacas (22.68 × 0.0882 ≈ 2)\n";
echo "• 1 paca → 25 libras → 11.34 kilos\n\n";
