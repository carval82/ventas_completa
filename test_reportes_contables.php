<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Contabilidad\ReporteContableController;
use Illuminate\Http\Request;
use Carbon\Carbon;

echo "=== PRUEBA DE REPORTES CONTABLES ===\n\n";

$controller = $app->make(ReporteContableController::class);

// 1. Probar Libro Diario
echo "📚 1. PROBANDO LIBRO DIARIO...\n";
try {
    $request = new Request([
        'fecha_desde' => Carbon::now()->startOfMonth()->format('Y-m-d'),
        'fecha_hasta' => Carbon::now()->format('Y-m-d')
    ]);
    
    $response = $controller->libro_diario($request);
    echo "  ✅ Libro Diario: Respuesta obtenida correctamente\n";
    
} catch (\Exception $e) {
    echo "  ❌ Error en Libro Diario: " . $e->getMessage() . "\n";
}

// 2. Probar Libro Mayor
echo "\n📖 2. PROBANDO LIBRO MAYOR...\n";
try {
    $request = new Request([
        'fecha_desde' => Carbon::now()->startOfMonth()->format('Y-m-d'),
        'fecha_hasta' => Carbon::now()->format('Y-m-d')
    ]);
    
    $response = $controller->libro_mayor($request);
    echo "  ✅ Libro Mayor: Respuesta obtenida correctamente\n";
    
} catch (\Exception $e) {
    echo "  ❌ Error en Libro Mayor: " . $e->getMessage() . "\n";
}

// 3. Probar Reporte Fiscal IVA
echo "\n🏛️ 3. PROBANDO REPORTE FISCAL IVA...\n";
try {
    $request = new Request([
        'fecha_inicio' => Carbon::now()->startOfMonth()->format('Y-m-d'),
        'fecha_fin' => Carbon::now()->format('Y-m-d')
    ]);
    
    $response = $controller->reporte_fiscal_iva($request);
    echo "  ✅ Reporte Fiscal IVA: Respuesta obtenida correctamente\n";
    
} catch (\Exception $e) {
    echo "  ❌ Error en Reporte Fiscal IVA: " . $e->getMessage() . "\n";
}

// 4. Probar Reporte Fiscal Retenciones
echo "\n💰 4. PROBANDO REPORTE FISCAL RETENCIONES...\n";
try {
    $request = new Request([
        'fecha_inicio' => Carbon::now()->startOfMonth()->format('Y-m-d'),
        'fecha_fin' => Carbon::now()->format('Y-m-d')
    ]);
    
    $response = $controller->reporte_fiscal_retenciones($request);
    echo "  ✅ Reporte Fiscal Retenciones: Respuesta obtenida correctamente\n";
    
} catch (\Exception $e) {
    echo "  ❌ Error en Reporte Fiscal Retenciones: " . $e->getMessage() . "\n";
}

echo "\n🎯 VERIFICACIÓN DE RUTAS:\n";
echo "  📚 Libro Diario: http://127.0.0.1:8000/contabilidad/reportes/libro-diario\n";
echo "  📖 Libro Mayor: http://127.0.0.1:8000/contabilidad/reportes/libro-mayor\n";
echo "  🏛️ Fiscal IVA: http://127.0.0.1:8000/contabilidad/reportes/fiscal-iva\n";
echo "  💰 Fiscal Retenciones: http://127.0.0.1:8000/contabilidad/reportes/fiscal-retenciones\n";

echo "\n✅ Prueba completada. Si no hay errores, todos los reportes deberían funcionar.\n";
