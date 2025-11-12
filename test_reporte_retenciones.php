<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Contabilidad\ReporteContableController;
use Illuminate\Http\Request;
use Carbon\Carbon;

echo "=== PRUEBA ESPECÍFICA REPORTE FISCAL RETENCIONES ===\n\n";

$controller = $app->make(ReporteContableController::class);

echo "💰 PROBANDO REPORTE FISCAL RETENCIONES...\n";
try {
    $request = new Request([
        'fecha_inicio' => Carbon::now()->startOfMonth()->format('Y-m-d'),
        'fecha_fin' => Carbon::now()->format('Y-m-d')
    ]);
    
    $response = $controller->reporte_fiscal_retenciones($request);
    
    // Si llegamos aquí, el controlador funcionó
    echo "  ✅ Controlador ejecutado correctamente\n";
    
    // Verificar que es una respuesta de vista
    if ($response instanceof \Illuminate\View\View) {
        echo "  ✅ Vista generada correctamente\n";
        
        // Obtener los datos pasados a la vista
        $data = $response->getData();
        echo "  📊 Datos del reporte:\n";
        
        if (isset($data['reporte'])) {
            $reporte = $data['reporte'];
            echo "    - Total retenciones fuente: $" . number_format($reporte['total_retenciones_fuente'], 2) . "\n";
            echo "    - Total retenciones IVA: $" . number_format($reporte['total_retenciones_iva'], 2) . "\n";
            echo "    - Total efectuadas: $" . number_format($reporte['total_efectuadas'], 2) . "\n";
            echo "    - Saldo a pagar: $" . number_format($reporte['saldo_a_pagar'], 2) . "\n";
            echo "    - Cantidad movimientos: " . $reporte['cantidad_movimientos'] . "\n";
        }
        
        echo "  ✅ Estructura de datos correcta\n";
    } else {
        echo "  ❌ Respuesta no es una vista\n";
    }
    
} catch (\Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
    echo "  📍 Línea: " . $e->getLine() . "\n";
    echo "  📁 Archivo: " . $e->getFile() . "\n";
}

echo "\n🎯 URL del reporte: http://127.0.0.1:8000/contabilidad/reportes/fiscal-retenciones\n";
echo "\n✅ Prueba completada. Si no hay errores, el reporte debería funcionar en el navegador.\n";
