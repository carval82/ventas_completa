<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\EstadoResultadosController;
use Illuminate\Http\Request;
use Carbon\Carbon;

echo "=== PRUEBA ESTADO DE RESULTADOS NIF ===\n";

// Crear una instancia del controlador
$controller = new EstadoResultadosController();

// Crear un request simulado
$fechaInicio = Carbon::now()->startOfYear();
$fechaFin = Carbon::now();

$request = new Request([
    'fecha_inicio' => $fechaInicio->format('Y-m-d'),
    'fecha_fin' => $fechaFin->format('Y-m-d'),
    'nivel_detalle' => 4,
    'mostrar_ceros' => false
]);

try {
    // Llamar al método generar
    $response = $controller->generar($request);
    
    // Obtener el contenido JSON
    $content = $response->getContent();
    $data = json_decode($content, true);
    
    echo "Respuesta del controlador:\n";
    echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
    echo "Período: " . $data['periodo'] . "\n\n";
    
    echo "=== ESTADO DE RESULTADOS RECIBIDO ===\n";
    echo "Ingresos Operacionales: " . count($data['estado_resultados']['ingresos_operacionales']) . " cuentas\n";
    echo "Ingresos No Operacionales: " . count($data['estado_resultados']['ingresos_no_operacionales']) . " cuentas\n";
    echo "Gastos Operacionales: " . count($data['estado_resultados']['gastos_operacionales']) . " cuentas\n";
    echo "Gastos No Operacionales: " . count($data['estado_resultados']['gastos_no_operacionales']) . " cuentas\n";
    echo "Costos de Ventas: " . count($data['estado_resultados']['costos_ventas']) . " cuentas\n\n";
    
    echo "=== TOTALES ===\n";
    $totales = $data['estado_resultados']['totales'];
    echo "Total Ingresos Operacionales: $" . number_format($totales['total_ingresos_operacionales'], 0, ',', '.') . "\n";
    echo "Total Ingresos No Operacionales: $" . number_format($totales['total_ingresos_no_operacionales'], 0, ',', '.') . "\n";
    echo "Total Gastos Operacionales: $" . number_format($totales['total_gastos_operacionales'], 0, ',', '.') . "\n";
    echo "Total Gastos No Operacionales: $" . number_format($totales['total_gastos_no_operacionales'], 0, ',', '.') . "\n";
    echo "Total Costos de Ventas: $" . number_format($totales['total_costos_ventas'], 0, ',', '.') . "\n";
    echo "Utilidad Bruta: $" . number_format($totales['utilidad_bruta'], 0, ',', '.') . "\n";
    echo "Utilidad Operacional: $" . number_format($totales['utilidad_operacional'], 0, ',', '.') . "\n";
    echo "Utilidad Neta: $" . number_format($totales['utilidad_neta'], 0, ',', '.') . "\n\n";
    
    echo "=== CUENTAS DETALLADAS ===\n";
    echo "INGRESOS OPERACIONALES:\n";
    foreach ($data['estado_resultados']['ingresos_operacionales'] as $cuenta) {
        echo "  {$cuenta['codigo']} - {$cuenta['nombre']}: $" . number_format($cuenta['saldo'], 0, ',', '.') . "\n";
    }
    
    echo "\nGASTOS OPERACIONALES:\n";
    foreach ($data['estado_resultados']['gastos_operacionales'] as $cuenta) {
        echo "  {$cuenta['codigo']} - {$cuenta['nombre']}: $" . number_format($cuenta['saldo'], 0, ',', '.') . "\n";
    }
    
    echo "\nCOSTOS DE VENTAS:\n";
    foreach ($data['estado_resultados']['costos_ventas'] as $cuenta) {
        echo "  {$cuenta['codigo']} - {$cuenta['nombre']}: $" . number_format($cuenta['saldo'], 0, ',', '.') . "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
