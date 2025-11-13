<?php
/**
 * Script para verificar el IVA de los productos
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "╔═══════════════════════════════════════════╗\n";
echo "║  VERIFICACIÓN DE IVA EN PRODUCTOS        ║\n";
echo "╚═══════════════════════════════════════════╝\n\n";

// Verificar estructura de tabla productos
echo "📋 Estructura de tabla 'productos':\n";
$columns = DB::select("SHOW COLUMNS FROM productos");
$tieneIVA = false;

foreach ($columns as $column) {
    if (in_array($column->Field, ['iva', 'impuesto', 'porcentaje_iva'])) {
        echo "   ✅ Columna '{$column->Field}': {$column->Type}\n";
        $tieneIVA = true;
    }
}

if (!$tieneIVA) {
    echo "   ⚠️  NO se encontró columna de IVA en productos\n";
    echo "   📝 Columnas disponibles: ";
    echo implode(', ', array_map(fn($c) => $c->Field, $columns)) . "\n";
}

echo "\n";

// Verificar productos con IVA
echo "📦 Productos con IVA configurado:\n";
$productos = DB::table('productos')
    ->select('id', 'nombre', 'precio_venta', 'precio_final', 'valor_iva')
    ->limit(10)
    ->get();

if ($productos->isEmpty()) {
    echo "   ⚠️  No hay productos en la BD\n";
} else {
    $conIVA = 0;
    $sinIVA = 0;
    
    foreach ($productos as $prod) {
        $ivaValor = $prod->valor_iva ?? 0;
        $precio = $prod->precio_final ?? $prod->precio_venta ?? 0;
        
        if ($ivaValor > 0) {
            echo "   ✅ [{$prod->id}] {$prod->nombre}\n";
            echo "      Precio: \${$precio} | IVA: {$ivaValor}%\n";
            $conIVA++;
        } else {
            $sinIVA++;
        }
    }
    
    echo "\n   Total: {$conIVA} productos CON IVA | {$sinIVA} productos SIN IVA\n";
}

echo "\n";

// Verificar una venta reciente
echo "🧾 Verificar venta reciente:\n";
$venta = DB::table('ventas')
    ->orderBy('id', 'desc')
    ->first();

if ($venta) {
    echo "   Venta ID: {$venta->id}\n";
    echo "   Total: \${$venta->total}\n";
    echo "   Impuesto: \$" . ($venta->impuesto ?? '0') . "\n";
    
    // Ver detalles
    $detalles = DB::table('detalle_ventas')
        ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
        ->where('detalle_ventas.venta_id', $venta->id)
        ->select('productos.nombre', 'productos.valor_iva', 'detalle_ventas.*')
        ->get();
    
    if ($detalles->isNotEmpty()) {
        echo "\n   📋 Detalles:\n";
        foreach ($detalles as $det) {
            $ivaProducto = $det->valor_iva ?? 0;
            echo "      • {$det->nombre}\n";
            echo "        Cantidad: {$det->cantidad} | Precio: \${$det->precio}\n";
            echo "        Subtotal: \${$det->subtotal} | IVA producto: {$ivaProducto}%\n";
        }
    }
} else {
    echo "   ⚠️  No hay ventas en la BD\n";
}

echo "\n";

// Recomendaciones
echo "╔═══════════════════════════════════════════╗\n";
echo "║  📝 RECOMENDACIONES                       ║\n";
echo "╚═══════════════════════════════════════════╝\n\n";

if (!$tieneIVA) {
    echo "⚠️  PROBLEMA: La tabla 'productos' no tiene columna de IVA\n";
    echo "   Necesitas agregar el campo 'iva' a la tabla productos\n";
    echo "   Ejecuta: php artisan make:migration add_iva_to_productos_table\n\n";
} elseif ($conIVA === 0) {
    echo "⚠️  Los productos NO tienen IVA configurado\n";
    echo "   Debes editar los productos y asignar el % de IVA (ej: 19)\n\n";
} else {
    echo "✅ La estructura está correcta\n";
    echo "   El desglose de IVA se calculará dinámicamente desde los productos\n\n";
}

echo "💡 El nuevo sistema calcula el IVA así:\n";
echo "   1. Lee el % de IVA de cada producto\n";
echo "   2. Calcula base gravable: precio / (1 + IVA/100)\n";
echo "   3. Calcula IVA: precio - base gravable\n";
echo "   4. Suma todos los IVAs para el total\n\n";
