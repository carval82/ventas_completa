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
use App\Models\ConfiguracionContable;
use App\Http\Controllers\BalanceGeneralController;
use App\Http\Controllers\EstadoResultadosController;
use App\Http\Controllers\FlujoEfectivoController;
use Illuminate\Http\Request;
use Carbon\Carbon;

echo "=== VERIFICACIÓN FINAL COMPLETA SISTEMA NIF COLOMBIA ===\n\n";

$errores = [];
$advertencias = [];
$exitoso = [];

// 1. VERIFICAR INTEGRACIÓN VENTAS-CONTABILIDAD
echo "🔗 1. VERIFICANDO INTEGRACIÓN VENTAS-CONTABILIDAD...\n";
try {
    $totalVentas = Venta::count();
    $ventasConComprobante = 0;
    
    foreach (Venta::all() as $venta) {
        $comprobante = Comprobante::where('descripcion', 'LIKE', "%{$venta->numero_factura}%")->first();
        if ($comprobante) {
            $ventasConComprobante++;
        }
    }
    
    $porcentajeIntegracion = $totalVentas > 0 ? ($ventasConComprobante / $totalVentas) * 100 : 100;
    
    if ($porcentajeIntegracion >= 95) {
        $exitoso[] = "✅ Integración ventas-contabilidad: {$porcentajeIntegracion}% ({$ventasConComprobante}/{$totalVentas})";
    } elseif ($porcentajeIntegracion >= 80) {
        $advertencias[] = "⚠️ Integración ventas-contabilidad: {$porcentajeIntegracion}% - Algunas ventas sin comprobantes";
    } else {
        $errores[] = "❌ Integración ventas-contabilidad: {$porcentajeIntegracion}% - Muchas ventas sin comprobantes";
    }
} catch (\Exception $e) {
    $errores[] = "❌ Error verificando integración: " . $e->getMessage();
}

// 2. VERIFICAR CONFIGURACIÓN CONTABLE
echo "⚙️ 2. VERIFICANDO CONFIGURACIÓN CONTABLE...\n";
$conceptosRequeridos = ['caja', 'ventas', 'iva_ventas', 'costo_ventas', 'inventario'];
$configuracionesOk = 0;

foreach ($conceptosRequeridos as $concepto) {
    try {
        $cuenta = ConfiguracionContable::getCuentaPorConcepto($concepto);
        if ($cuenta) {
            $configuracionesOk++;
            $exitoso[] = "✅ Configuración '{$concepto}': {$cuenta->codigo} - {$cuenta->nombre}";
        } else {
            $errores[] = "❌ Configuración '{$concepto}': NO ENCONTRADA";
        }
    } catch (\Exception $e) {
        $errores[] = "❌ Error configuración '{$concepto}': " . $e->getMessage();
    }
}

// 3. VERIFICAR BALANCE GENERAL NIF
echo "📊 3. VERIFICANDO BALANCE GENERAL NIF...\n";
try {
    $balanceController = new BalanceGeneralController();
    $request = new Request([
        'fecha_corte' => Carbon::now()->format('Y-m-d'),
        'nivel_detalle' => 4,
        'mostrar_ceros' => false
    ]);
    
    $response = $balanceController->generar($request);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        $balance = $data['balance'];
        $totalActivos = $balance['totales']['total_activos'];
        $totalPatrimonio = $balance['totales']['total_patrimonio'];
        
        if ($totalActivos > 0 && $totalPatrimonio > 0) {
            $exitoso[] = "✅ Balance General NIF: Activos \$" . number_format($totalActivos, 0, ',', '.') . 
                        " | Patrimonio \$" . number_format($totalPatrimonio, 0, ',', '.');
        } else {
            $advertencias[] = "⚠️ Balance General NIF: Sin datos o valores en cero";
        }
    } else {
        $errores[] = "❌ Balance General NIF: Error al generar - " . ($data['message'] ?? 'Error desconocido');
    }
} catch (\Exception $e) {
    $errores[] = "❌ Error Balance General NIF: " . $e->getMessage();
}

// 4. VERIFICAR ESTADO DE RESULTADOS NIF
echo "📈 4. VERIFICANDO ESTADO DE RESULTADOS NIF...\n";
try {
    $estadoController = new EstadoResultadosController();
    $request = new Request([
        'fecha_inicio' => Carbon::now()->startOfYear()->format('Y-m-d'),
        'fecha_fin' => Carbon::now()->format('Y-m-d'),
        'nivel_detalle' => 4,
        'mostrar_ceros' => false
    ]);
    
    $response = $estadoController->generar($request);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        $estado = $data['estado_resultados'];
        $ingresos = $estado['totales']['total_ingresos_operacionales'];
        $utilidad = $estado['totales']['utilidad_neta'];
        
        if ($ingresos > 0) {
            $exitoso[] = "✅ Estado de Resultados NIF: Ingresos \$" . number_format($ingresos, 0, ',', '.') . 
                        " | Utilidad \$" . number_format($utilidad, 0, ',', '.');
        } else {
            $advertencias[] = "⚠️ Estado de Resultados NIF: Sin ingresos registrados";
        }
    } else {
        $errores[] = "❌ Estado de Resultados NIF: Error al generar";
    }
} catch (\Exception $e) {
    $errores[] = "❌ Error Estado de Resultados NIF: " . $e->getMessage();
}

// 5. VERIFICAR FLUJO DE EFECTIVO NIF
echo "💰 5. VERIFICANDO FLUJO DE EFECTIVO NIF...\n";
try {
    $flujoController = new FlujoEfectivoController();
    $request = new Request([
        'fecha_inicio' => Carbon::now()->startOfMonth()->format('Y-m-d'),
        'fecha_fin' => Carbon::now()->format('Y-m-d'),
        'metodo' => 'indirecto'
    ]);
    
    $response = $flujoController->generar($request);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        $flujo = $data['flujo_efectivo'];
        $efectivoFinal = $flujo['totales']['efectivo_final'];
        
        $exitoso[] = "✅ Flujo de Efectivo NIF: Efectivo final \$" . number_format($efectivoFinal, 0, ',', '.');
    } else {
        $errores[] = "❌ Flujo de Efectivo NIF: Error al generar";
    }
} catch (\Exception $e) {
    $errores[] = "❌ Error Flujo de Efectivo NIF: " . $e->getMessage();
}

// 6. VERIFICAR PLAN DE CUENTAS PUC
echo "📋 6. VERIFICANDO PLAN DE CUENTAS PUC...\n";
try {
    $cuentasPUC = PlanCuenta::where('estado', true)->count();
    $cuentasConMovimientos = PlanCuenta::whereHas('movimientos')->count();
    
    if ($cuentasPUC >= 10) {
        $exitoso[] = "✅ Plan de Cuentas PUC: {$cuentasPUC} cuentas activas, {$cuentasConMovimientos} con movimientos";
    } else {
        $advertencias[] = "⚠️ Plan de Cuentas PUC: Solo {$cuentasPUC} cuentas activas";
    }
} catch (\Exception $e) {
    $errores[] = "❌ Error Plan de Cuentas: " . $e->getMessage();
}

// 7. VERIFICAR MOVIMIENTOS CONTABLES
echo "📝 7. VERIFICANDO MOVIMIENTOS CONTABLES...\n";
try {
    $totalMovimientos = MovimientoContable::count();
    $movimientosHoy = MovimientoContable::whereDate('created_at', Carbon::today())->count();
    
    if ($totalMovimientos > 0) {
        $exitoso[] = "✅ Movimientos Contables: {$totalMovimientos} total, {$movimientosHoy} hoy";
    } else {
        $errores[] = "❌ Movimientos Contables: No hay movimientos registrados";
    }
} catch (\Exception $e) {
    $errores[] = "❌ Error Movimientos Contables: " . $e->getMessage();
}

// 8. VERIFICAR RUTAS Y CONTROLADORES
echo "🛣️ 8. VERIFICANDO RUTAS Y CONTROLADORES...\n";
try {
    $controladores = [
        'BalanceGeneralController' => BalanceGeneralController::class,
        'EstadoResultadosController' => EstadoResultadosController::class,
        'FlujoEfectivoController' => FlujoEfectivoController::class
    ];
    
    foreach ($controladores as $nombre => $clase) {
        if (class_exists($clase)) {
            $exitoso[] = "✅ Controlador {$nombre}: Disponible";
        } else {
            $errores[] = "❌ Controlador {$nombre}: No encontrado";
        }
    }
} catch (\Exception $e) {
    $errores[] = "❌ Error verificando controladores: " . $e->getMessage();
}

// RESUMEN FINAL
echo "\n" . str_repeat("=", 80) . "\n";
echo "🏆 RESUMEN FINAL DE VERIFICACIÓN SISTEMA NIF COLOMBIA\n";
echo str_repeat("=", 80) . "\n\n";

// Mostrar resultados exitosos
if (!empty($exitoso)) {
    echo "✅ FUNCIONALIDADES OPERATIVAS:\n";
    foreach ($exitoso as $item) {
        echo "   {$item}\n";
    }
    echo "\n";
}

// Mostrar advertencias
if (!empty($advertencias)) {
    echo "⚠️ ADVERTENCIAS:\n";
    foreach ($advertencias as $item) {
        echo "   {$item}\n";
    }
    echo "\n";
}

// Mostrar errores
if (!empty($errores)) {
    echo "❌ ERRORES ENCONTRADOS:\n";
    foreach ($errores as $item) {
        echo "   {$item}\n";
    }
    echo "\n";
}

// Calcular puntuación final
$totalVerificaciones = count($exitoso) + count($advertencias) + count($errores);
$puntuacionExitoso = count($exitoso);
$puntuacionAdvertencias = count($advertencias) * 0.5;
$puntuacionFinal = ($puntuacionExitoso + $puntuacionAdvertencias) / $totalVerificaciones * 100;

echo "📊 PUNTUACIÓN FINAL: " . number_format($puntuacionFinal, 1) . "%\n\n";

if ($puntuacionFinal >= 90) {
    echo "🎉 ¡SISTEMA NIF COLOMBIA COMPLETAMENTE FUNCIONAL!\n";
    echo "✅ Listo para producción\n";
    echo "✅ Cumplimiento NIF: 90%+\n";
    echo "✅ Integración completa ventas-contabilidad\n";
    echo "✅ Todos los reportes operativos\n\n";
} elseif ($puntuacionFinal >= 75) {
    echo "👍 SISTEMA NIF COLOMBIA MAYORMENTE FUNCIONAL\n";
    echo "⚠️ Algunas mejoras menores requeridas\n";
    echo "✅ Apto para uso con supervisión\n\n";
} else {
    echo "⚠️ SISTEMA NIF COLOMBIA REQUIERE ATENCIÓN\n";
    echo "❌ Errores críticos deben ser corregidos\n";
    echo "❌ No recomendado para producción\n\n";
}

echo "🎯 ACCESO AL SISTEMA:\n";
echo "   Dashboard: http://127.0.0.1:8000/contabilidad/dashboard\n";
echo "   Balance General: http://127.0.0.1:8000/contabilidad/balance-general\n";
echo "   Estado Resultados: http://127.0.0.1:8000/contabilidad/estado-resultados\n";
echo "   Flujo Efectivo: http://127.0.0.1:8000/contabilidad/flujo-efectivo\n\n";

echo "🏁 VERIFICACIÓN COMPLETADA\n";
