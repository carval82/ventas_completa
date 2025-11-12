<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Contabilidad\ReporteContableController;
use Illuminate\Http\Request;
use Carbon\Carbon;

echo "=== PRUEBA ESPECÍFICA REPORTE FISCAL IVA ===\n\n";

$controller = $app->make(ReporteContableController::class);

echo "🏛️ PROBANDO REPORTE FISCAL IVA...\n";
try {
    $request = new Request([
        'fecha_inicio' => Carbon::now()->startOfMonth()->format('Y-m-d'),
        'fecha_fin' => Carbon::now()->format('Y-m-d')
    ]);
    
    $response = $controller->reporte_fiscal_iva($request);
    
    // Si llegamos aquí, el controlador funcionó
    echo "  ✅ Controlador ejecutado correctamente\n";
    
    // Verificar que es una respuesta de vista
    if ($response instanceof \Illuminate\View\View) {
        echo "  ✅ Vista generada correctamente\n";
        echo "  📄 Vista: " . $response->name() . "\n";
        
        // Obtener los datos pasados a la vista
        $data = $response->getData();
        echo "  📊 Datos disponibles:\n";
        foreach (array_keys($data) as $key) {
            echo "    - {$key}\n";
        }
        
    } else {
        echo "  ❌ Respuesta no es una vista\n";
        echo "  📝 Tipo de respuesta: " . get_class($response) . "\n";
    }
    
} catch (\Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
    echo "  📍 Línea: " . $e->getLine() . "\n";
    echo "  📁 Archivo: " . basename($e->getFile()) . "\n";
    echo "  🔍 Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n🎯 URL del reporte: http://127.0.0.1:8000/contabilidad/reportes/fiscal-iva\n";
echo "🔗 Ruta nombrada: reportes.fiscal-iva\n";

// Verificar que la vista existe
$vistaPath = resource_path('views/contabilidad/reportes/fiscal_iva.blade.php');
if (file_exists($vistaPath)) {
    echo "✅ Vista existe en: {$vistaPath}\n";
} else {
    echo "❌ Vista NO existe en: {$vistaPath}\n";
}

echo "\n✅ Prueba completada.\n";
