<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Venta;
use App\Models\Comprobante;
use App\Models\MovimientoContable;
use App\Models\PlanCuenta;

echo "=== VERIFICACIÓN INTEGRACIÓN VENTAS-CONTABILIDAD NIF ===\n\n";

// 1. Verificar ventas existentes
$ventas = Venta::with(['detalles', 'cliente'])->get();
echo "📊 VENTAS REGISTRADAS: " . $ventas->count() . "\n";

if ($ventas->count() > 0) {
    echo "\n=== DETALLE DE VENTAS ===\n";
    foreach ($ventas as $venta) {
        echo "Venta #{$venta->numero_factura} - Fecha: {$venta->fecha_venta}\n";
        echo "  Cliente: " . ($venta->cliente->nombre ?? 'N/A') . "\n";
        echo "  Subtotal: $" . number_format($venta->subtotal, 0, ',', '.') . "\n";
        echo "  IVA: $" . number_format($venta->iva, 0, ',', '.') . "\n";
        echo "  Total: $" . number_format($venta->total, 0, ',', '.') . "\n";
        echo "  Método: {$venta->metodo_pago}\n";
        
        // Verificar si tiene comprobante contable
        $comprobantes = Comprobante::where('descripcion', 'LIKE', "%{$venta->numero_factura}%")->get();
        if ($comprobantes->count() > 0) {
            echo "  ✅ TIENE COMPROBANTE CONTABLE\n";
            foreach ($comprobantes as $comp) {
                echo "    - Comprobante: {$comp->prefijo}{$comp->numero}\n";
                echo "    - Estado: {$comp->estado}\n";
                echo "    - Total: $" . number_format($comp->total_debito, 0, ',', '.') . "\n";
            }
        } else {
            echo "  ❌ SIN COMPROBANTE CONTABLE\n";
        }
        echo "\n";
    }
}

// 2. Verificar comprobantes de ventas
echo "\n=== COMPROBANTES DE VENTAS ===\n";
$comprobantesVentas = Comprobante::where('tipo', 'Ingreso')
                                ->where('prefijo', 'V')
                                ->get();
echo "📋 COMPROBANTES DE VENTAS: " . $comprobantesVentas->count() . "\n";

// 3. Verificar movimientos contables de ventas
echo "\n=== MOVIMIENTOS CONTABLES DE VENTAS ===\n";
$movimientosVentas = MovimientoContable::whereHas('comprobante', function($query) {
    $query->where('tipo', 'Ingreso')->where('prefijo', 'V');
})->with(['comprobante', 'cuenta'])->get();

echo "💰 MOVIMIENTOS DE VENTAS: " . $movimientosVentas->count() . "\n";

if ($movimientosVentas->count() > 0) {
    echo "\n=== DETALLE MOVIMIENTOS ===\n";
    foreach ($movimientosVentas as $mov) {
        echo "Comprobante: {$mov->comprobante->prefijo}{$mov->comprobante->numero}\n";
        echo "  Cuenta: {$mov->cuenta->codigo} - {$mov->cuenta->nombre}\n";
        echo "  Débito: $" . number_format($mov->debito, 0, ',', '.') . "\n";
        echo "  Crédito: $" . number_format($mov->credito, 0, ',', '.') . "\n";
        echo "  Fecha: {$mov->fecha}\n\n";
    }
}

// 4. Verificar cuentas de ventas con saldos
echo "\n=== CUENTAS DE VENTAS CON SALDOS ===\n";
$cuentasVentas = PlanCuenta::where('codigo', 'LIKE', '4%')
                          ->where('estado', true)
                          ->get();

foreach ($cuentasVentas as $cuenta) {
    $saldo = $cuenta->getSaldo();
    if ($saldo != 0) {
        echo "Cuenta: {$cuenta->codigo} - {$cuenta->nombre}\n";
        echo "  Saldo: $" . number_format($saldo, 0, ',', '.') . "\n";
        
        // Verificar movimientos de esta cuenta
        $movimientos = $cuenta->movimientos()->count();
        echo "  Movimientos: {$movimientos}\n\n";
    }
}

// 5. Verificar configuración contable
echo "\n=== CONFIGURACIÓN CONTABLE ===\n";
try {
    $configContable = App\Models\ConfiguracionContable::first();
    if ($configContable) {
        echo "✅ CONFIGURACIÓN CONTABLE EXISTE\n";
        
        // Verificar cuentas configuradas
        $cuentas = [
            'caja' => 'getCuentaPorConcepto',
            'ventas' => 'getCuentaPorConcepto', 
            'iva_ventas' => 'getCuentaPorConcepto'
        ];
        
        foreach ($cuentas as $concepto => $metodo) {
            try {
                $cuenta = App\Models\ConfiguracionContable::getCuentaPorConcepto($concepto);
                if ($cuenta) {
                    echo "  ✅ {$concepto}: {$cuenta->codigo} - {$cuenta->nombre}\n";
                } else {
                    echo "  ❌ {$concepto}: NO CONFIGURADA\n";
                }
            } catch (Exception $e) {
                echo "  ❌ {$concepto}: ERROR - {$e->getMessage()}\n";
            }
        }
    } else {
        echo "❌ NO HAY CONFIGURACIÓN CONTABLE\n";
    }
} catch (Exception $e) {
    echo "❌ ERROR AL VERIFICAR CONFIGURACIÓN: {$e->getMessage()}\n";
}

// 6. Resumen de integración
echo "\n=== RESUMEN DE INTEGRACIÓN ===\n";
$ventasConComprobante = 0;
foreach ($ventas as $venta) {
    $comprobantes = Comprobante::where('descripcion', 'LIKE', "%{$venta->numero_factura}%")->count();
    if ($comprobantes > 0) {
        $ventasConComprobante++;
    }
}

$porcentajeIntegracion = $ventas->count() > 0 ? ($ventasConComprobante / $ventas->count()) * 100 : 0;

echo "📊 ESTADÍSTICAS:\n";
echo "  - Total Ventas: {$ventas->count()}\n";
echo "  - Ventas con Comprobante: {$ventasConComprobante}\n";
echo "  - Integración: " . number_format($porcentajeIntegracion, 1) . "%\n";
echo "  - Comprobantes Generados: {$comprobantesVentas->count()}\n";
echo "  - Movimientos Contables: {$movimientosVentas->count()}\n";

if ($porcentajeIntegracion == 100) {
    echo "\n🎉 ¡INTEGRACIÓN COMPLETA AL 100%!\n";
} elseif ($porcentajeIntegracion > 0) {
    echo "\n⚠️  INTEGRACIÓN PARCIAL - Algunas ventas sin comprobantes\n";
} else {
    echo "\n❌ SIN INTEGRACIÓN - Ventas no generan comprobantes contables\n";
}
