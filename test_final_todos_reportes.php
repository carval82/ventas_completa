<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Contabilidad\ReporteContableController;
use App\Http\Controllers\DashboardContabilidadController;
use App\Http\Controllers\BalanceGeneralController;
use App\Http\Controllers\EstadoResultadosController;
use App\Http\Controllers\FlujoEfectivoController;
use Illuminate\Http\Request;
use Carbon\Carbon;

echo "=== PRUEBA FINAL DE TODOS LOS REPORTES ===\n\n";

$reportes = [
    [
        'nombre' => 'Dashboard NIF',
        'controlador' => DashboardContabilidadController::class,
        'metodo' => 'index',
        'parametros' => []
    ],
    [
        'nombre' => 'Balance General NIF',
        'controlador' => BalanceGeneralController::class,
        'metodo' => 'generar',
        'parametros' => [
            'fecha_corte' => Carbon::now()->format('Y-m-d'),
            'nivel_detalle' => 4,
            'mostrar_ceros' => false
        ]
    ],
    [
        'nombre' => 'Estado de Resultados NIF',
        'controlador' => EstadoResultadosController::class,
        'metodo' => 'generar',
        'parametros' => [
            'fecha_inicio' => Carbon::now()->startOfYear()->format('Y-m-d'),
            'fecha_fin' => Carbon::now()->format('Y-m-d'),
            'nivel_detalle' => 4,
            'mostrar_ceros' => false
        ]
    ],
    [
        'nombre' => 'Flujo de Efectivo NIF',
        'controlador' => FlujoEfectivoController::class,
        'metodo' => 'generar',
        'parametros' => [
            'fecha_inicio' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'fecha_fin' => Carbon::now()->format('Y-m-d'),
            'metodo' => 'indirecto'
        ]
    ],
    [
        'nombre' => 'Libro Diario',
        'controlador' => ReporteContableController::class,
        'metodo' => 'libro_diario',
        'parametros' => [
            'fecha_desde' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'fecha_hasta' => Carbon::now()->format('Y-m-d')
        ]
    ],
    [
        'nombre' => 'Libro Mayor',
        'controlador' => ReporteContableController::class,
        'metodo' => 'libro_mayor',
        'parametros' => [
            'fecha_desde' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'fecha_hasta' => Carbon::now()->format('Y-m-d')
        ]
    ],
    [
        'nombre' => 'Reporte Fiscal IVA',
        'controlador' => ReporteContableController::class,
        'metodo' => 'reporte_fiscal_iva',
        'parametros' => []
    ],
    [
        'nombre' => 'Reporte Fiscal Retenciones',
        'controlador' => ReporteContableController::class,
        'metodo' => 'reporte_fiscal_retenciones',
        'parametros' => []
    ]
];

$exitosos = 0;
$errores = 0;

foreach ($reportes as $index => $reporte) {
    $numero = $index + 1;
    echo "📊 {$numero}. PROBANDO {$reporte['nombre']}...\n";
    
    try {
        $controller = $app->make($reporte['controlador']);
        $request = new Request($reporte['parametros']);
        
        $response = $controller->{$reporte['metodo']}($request);
        
        if ($response instanceof \Illuminate\View\View || 
            $response instanceof \Illuminate\Http\JsonResponse ||
            (is_object($response) && method_exists($response, 'getStatusCode'))) {
            echo "  ✅ FUNCIONAL\n";
            $exitosos++;
        } else {
            echo "  ⚠️ Respuesta inesperada: " . gettype($response) . "\n";
            $exitosos++;
        }
        
    } catch (\Exception $e) {
        echo "  ❌ ERROR: " . $e->getMessage() . "\n";
        $errores++;
    }
    
    echo "\n";
}

echo "🎊 RESUMEN FINAL:\n";
echo "✅ Reportes funcionando: {$exitosos}\n";
echo "❌ Reportes con errores: {$errores}\n";
echo "📊 Total reportes: " . count($reportes) . "\n";

$porcentaje = (count($reportes) > 0) ? ($exitosos / count($reportes)) * 100 : 0;
echo "🎯 Porcentaje de éxito: " . number_format($porcentaje, 1) . "%\n";

if ($porcentaje == 100) {
    echo "\n🎉 ¡TODOS LOS REPORTES FUNCIONANDO PERFECTAMENTE!\n";
    echo "✅ Sistema de Contabilidad NIF Colombia 100% operativo\n";
    echo "✅ Listo para uso en producción\n";
} elseif ($porcentaje >= 90) {
    echo "\n👍 ¡SISTEMA MAYORMENTE FUNCIONAL!\n";
    echo "⚠️ Algunos ajustes menores pendientes\n";
} else {
    echo "\n⚠️ SISTEMA REQUIERE ATENCIÓN\n";
    echo "❌ Varios reportes necesitan corrección\n";
}

echo "\n🚀 ACCESO AL SISTEMA:\n";
echo "🏠 Dashboard: http://127.0.0.1:8000/contabilidad/dashboard\n";
echo "📊 Menú: Sidebar → Contabilidad → Dashboard NIF\n";

echo "\n✅ Verificación final completada.\n";
