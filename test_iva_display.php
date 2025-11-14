<?php
/**
 * Script para probar el cálculo de IVA en la vista
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Venta;
use App\Models\Empresa;

echo "╔═══════════════════════════════════════════╗\n";
echo "║  TEST: CÁLCULO IVA EN FACTURA             ║\n";
echo "╚═══════════════════════════════════════════╝\n\n";

// Obtener empresa
$empresa = Empresa::first();
echo "🏢 EMPRESA:\n";
echo "   Nombre: {$empresa->nombre_comercial}\n";
echo "   Régimen: {$empresa->regimen_tributario}\n";
echo "   Es responsable IVA: " . ($empresa->regimen_tributario === 'responsable_iva' ? 'SÍ' : 'NO') . "\n\n";

// Obtener última venta
$venta = Venta::with('detalles')->latest()->first();

if (!$venta) {
    echo "❌ No hay ventas en la BD\n";
    exit(1);
}

echo "🧾 VENTA #{$venta->id}:\n";
echo "   Total venta: \$" . number_format($venta->total, 2) . "\n";
echo "   Descuento: \$" . number_format($venta->descuento ?? 0, 2) . "\n\n";

// Simular el cálculo de la vista
$subtotalSinIVA = 0;
$totalIVA = 0;
$porcentajesIVA = [];

echo "📦 DETALLES:\n";
foreach ($venta->detalles as $detalle) {
    $subtotalDetalle = $detalle->subtotal ?? 0;
    $valorIVADetalle = $detalle->valor_iva ?? 0;
    $porcentajeDetalle = $detalle->porcentaje_iva ?? 0;
    
    $subtotalSinIVA += $subtotalDetalle;
    $totalIVA += $valorIVADetalle;
    
    if ($porcentajeDetalle > 0 && !in_array($porcentajeDetalle, $porcentajesIVA)) {
        $porcentajesIVA[] = $porcentajeDetalle;
    }
    
    echo "   • Subtotal: \${$subtotalDetalle} | IVA: \${$valorIVADetalle} ({$porcentajeDetalle}%)\n";
}

echo "\n📊 ESTRATEGIA 1 (desde detalles):\n";
echo "   Base gravable: \$" . number_format($subtotalSinIVA, 2) . "\n";
echo "   IVA total: \$" . number_format($totalIVA, 2) . "\n";

// Estrategia 2: Si no hay IVA
$esResponsableIVA = $empresa->regimen_tributario === 'responsable_iva';

if ($totalIVA == 0 && $esResponsableIVA) {
    echo "\n📊 ESTRATEGIA 2 (calculado desde total):\n";
    echo "   ⚠️  No hay IVA en detalles, calculando...\n";
    
    $totalConIVA = $venta->total;
    $descuentos = $venta->descuento ?? 0;
    $subtotalSinDescuento = $totalConIVA + $descuentos;
    
    $baseGravable = $subtotalSinDescuento / 1.19;
    $ivaCalculado = $subtotalSinDescuento - $baseGravable;
    
    if ($descuentos > 0) {
        $factorDescuento = $totalConIVA / $subtotalSinDescuento;
        $baseGravable = $baseGravable * $factorDescuento;
        $ivaCalculado = $ivaCalculado * $factorDescuento;
    }
    
    echo "   Base gravable: \$" . number_format($baseGravable, 2) . "\n";
    echo "   IVA 19%: \$" . number_format($ivaCalculado, 2) . "\n";
    echo "   Total: \$" . number_format($baseGravable + $ivaCalculado, 2) . "\n";
}

echo "\n";

// Verificar qué mostrará la factura
if ($totalIVA > 0) {
    echo "✅ LA FACTURA MOSTRARÁ:\n";
    echo "   Tabla de impuestos con IVA desde detalles\n";
} else if ($esResponsableIVA) {
    echo "✅ LA FACTURA MOSTRARÁ:\n";
    echo "   Tabla de impuestos con IVA calculado (19%)\n";
} else {
    echo "ℹ️  LA FACTURA MOSTRARÁ:\n";
    echo "   'No responsable de IVA'\n";
}

echo "\n";
