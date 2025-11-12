<?php
// Script para probar el envío de una factura a la DIAN

// Cargar el entorno de Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AlegraService;
use Illuminate\Support\Facades\Log;

// ID de la factura a enviar (pasar como argumento o usar un valor por defecto)
$facturaId = $argv[1] ?? null;

if (!$facturaId) {
    echo "Error: Debe proporcionar el ID de la factura como argumento\n";
    exit(1);
}

echo "Iniciando prueba de envío de factura a la DIAN...\n";
echo "Factura ID: {$facturaId}\n\n";

// Crear instancia del servicio de Alegra
$alegraService = app(AlegraService::class);

// Intentar enviar la factura a la DIAN
echo "Enviando factura a la DIAN...\n";
$resultado = $alegraService->enviarFacturaADian($facturaId);

// Mostrar el resultado
echo "\nResultado del envío:\n";
echo "Éxito: " . ($resultado['success'] ? 'SÍ' : 'NO') . "\n";

if ($resultado['success']) {
    echo "Datos de respuesta: " . json_encode($resultado['data'], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "Error: " . $resultado['error'] . "\n";
}

echo "\nProceso completado.\n";
