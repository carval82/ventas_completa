<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\BalanceGeneralController;
use Illuminate\Http\Request;
use Carbon\Carbon;

echo "=== PRUEBA RESPUESTA CONTROLADOR ===\n";

// Crear una instancia del controlador
$controller = new BalanceGeneralController();

// Crear un request simulado
$request = new Request([
    'fecha_corte' => Carbon::now()->format('Y-m-d'),
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
    echo "Fecha corte: " . $data['fecha_corte'] . "\n\n";
    
    echo "=== BALANCE RECIBIDO ===\n";
    echo "Activos: " . count($data['balance']['activos']) . " cuentas\n";
    echo "Pasivos: " . count($data['balance']['pasivos']) . " cuentas\n";
    echo "Patrimonio: " . count($data['balance']['patrimonio']) . " cuentas\n\n";
    
    echo "=== TOTALES ===\n";
    echo "Total Activos: " . $data['balance']['totales']['total_activos'] . "\n";
    echo "Total Pasivos: " . $data['balance']['totales']['total_pasivos'] . "\n";
    echo "Total Patrimonio: " . $data['balance']['totales']['total_patrimonio'] . "\n";
    echo "Total Pasivo + Patrimonio: " . $data['balance']['totales']['total_pasivo_patrimonio'] . "\n\n";
    
    echo "=== TOTALES FORMATEADOS ===\n";
    echo "Total Activos Formateado: " . ($data['balance']['totales']['total_activos_formateado'] ?? 'NO EXISTE') . "\n";
    echo "Total Pasivos Formateado: " . ($data['balance']['totales']['total_pasivos_formateado'] ?? 'NO EXISTE') . "\n";
    echo "Total Patrimonio Formateado: " . ($data['balance']['totales']['total_patrimonio_formateado'] ?? 'NO EXISTE') . "\n";
    echo "Total Pasivo + Patrimonio Formateado: " . ($data['balance']['totales']['total_pasivo_patrimonio_formateado'] ?? 'NO EXISTE') . "\n\n";
    
    echo "=== CUENTAS DETALLADAS ===\n";
    echo "ACTIVOS:\n";
    foreach ($data['balance']['activos'] as $cuenta) {
        echo "  {$cuenta['codigo']} - {$cuenta['nombre']}: {$cuenta['saldo']} (Formateado: {$cuenta['saldo_formateado']})\n";
    }
    
    echo "\nPATRIMONIO:\n";
    foreach ($data['balance']['patrimonio'] as $cuenta) {
        echo "  {$cuenta['codigo']} - {$cuenta['nombre']}: {$cuenta['saldo']} (Formateado: {$cuenta['saldo_formateado']})\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
