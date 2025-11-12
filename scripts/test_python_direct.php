<?php

// Script para probar directamente el servicio Python
require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Cargar el entorno de Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Datos de prueba con el formato exacto que espera el servicio Python
$testData = [
    'client' => [
        'id' => intval(38)  // ID de cliente en Alegra como entero
    ],
    'items' => [
        [
            'id' => intval(67),  // ID de producto en Alegra como entero
            'price' => 26000,
            'quantity' => 1
        ]
    ],
    'date' => '2025-03-10',
    'dueDate' => '2025-03-10',
    'paymentForm' => 'CASH',
    'paymentMethod' => 'CASH',
    'payment' => [
        'paymentMethod' => ['id' => 10],
        'account' => ['id' => 1]
    ],
    // Agregar la plantilla de numeración electrónica
    'numberTemplate' => [
        'id' => 1  // Asegúrate de que este ID corresponda a una plantilla electrónica activa
    ]
];

// Mostrar datos que se enviarán
echo "Enviando datos al servicio Python:\n";
echo json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

// Enviar solicitud al servicio Python
try {
    $response = Http::post('http://localhost:8001/invoices', $testData);
    
    echo "Código de respuesta: " . $response->status() . "\n";
    echo "Respuesta del servicio Python:\n";
    echo json_encode($response->json(), JSON_PRETTY_PRINT) . "\n";
    
    if ($response->successful()) {
        echo "\n¡Éxito! La factura se creó correctamente.\n";
    } else {
        echo "\nError al crear la factura. Revisa la respuesta para más detalles.\n";
    }
} catch (\Exception $e) {
    echo "Error al conectar con el servicio Python: " . $e->getMessage() . "\n";
}
