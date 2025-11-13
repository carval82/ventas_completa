<?php
/**
 * Script para ver el desglose de impuestos de una venta
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Venta;

$ventaId = $argv[1] ?? null;

if (!$ventaId) {
    // Obtener última venta
    $venta = Venta::with('detalles.producto')->latest()->first();
} else {
    $venta = Venta::with('detalles.producto')->find($ventaId);
}

if (!$venta) {
    echo "❌ No se encontró la venta\n";
    exit(1);
}

echo "╔═══════════════════════════════════════════╗\n";
echo "║  DESGLOSE DE VENTA #{$venta->id}\n";
echo "╚═══════════════════════════════════════════╝\n\n";

echo "📋 INFORMACIÓN GENERAL:\n";
echo "   Total venta: \$" . number_format($venta->total, 2) . "\n";
echo "   Descuento: \$" . number_format($venta->descuento ?? 0, 2) . "\n";
echo "   Impuesto (campo venta): \$" . number_format($venta->impuesto ?? 0, 2) . "\n\n";

echo "📦 DETALLES DE PRODUCTOS:\n";
echo str_repeat("─", 80) . "\n";
printf("%-5s %-30s %8s %10s %10s %10s %10s\n", 
    "#", "Producto", "Cant", "P.Unit", "Subtotal", "IVA%", "Valor IVA");
echo str_repeat("─", 80) . "\n";

$totalSubtotal = 0;
$totalIVA = 0;

foreach ($venta->detalles as $index => $detalle) {
    $nombreProducto = $detalle->producto ? $detalle->producto->nombre : 'N/A';
    $nombreProducto = substr($nombreProducto, 0, 28);
    
    $precioUnit = $detalle->precio_unitario ?? 0;
    $subtotal = $detalle->subtotal ?? 0;
    $valorIVA = $detalle->valor_iva ?? 0;
    $porcentajeIVA = $detalle->porcentaje_iva ?? 0;
    
    $totalSubtotal += $subtotal;
    $totalIVA += $valorIVA;
    
    printf("%-5s %-30s %8.2f %10.2f %10.2f %9.2f%% %10.2f\n",
        ($index + 1),
        $nombreProducto,
        $detalle->cantidad,
        $precioUnit,
        $subtotal,
        $porcentajeIVA,
        $valorIVA
    );
}

echo str_repeat("─", 80) . "\n";
printf("%47s %10.2f %10s %10.2f\n", 
    "TOTALES:",
    $totalSubtotal,
    "",
    $totalIVA
);
echo str_repeat("─", 80) . "\n\n";

echo "💰 CÁLCULO FINAL:\n";
echo "   Base gravable (sin IVA): \$" . number_format($totalSubtotal, 2) . "\n";
echo "   Total IVA:               \$" . number_format($totalIVA, 2) . "\n";
echo "   ───────────────────────────────────\n";
echo "   Total con IVA:           \$" . number_format($totalSubtotal + $totalIVA, 2) . "\n";

if ($venta->descuento > 0) {
    echo "   Descuento:              -\$" . number_format($venta->descuento, 2) . "\n";
    echo "   ───────────────────────────────────\n";
    echo "   Total a pagar:           \$" . number_format($venta->total, 2) . "\n";
}

echo "\n";

// Verificar si coinciden los totales
$totalCalculado = $totalSubtotal + $totalIVA - ($venta->descuento ?? 0);
$diferencia = abs($totalCalculado - $venta->total);

if ($diferencia < 0.02) {
    echo "✅ Los totales coinciden correctamente\n";
} else {
    echo "⚠️  Hay una diferencia de: \$" . number_format($diferencia, 2) . "\n";
    echo "   Total calculado: \$" . number_format($totalCalculado, 2) . "\n";
    echo "   Total en BD:     \$" . number_format($venta->total, 2) . "\n";
}

echo "\n💡 USO: php ver_desglose_venta.php [ID_VENTA]\n";
echo "   Si no especificas ID, se muestra la última venta.\n\n";
