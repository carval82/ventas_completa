<?php

// Script para probar la integración con Alegra usando el cliente JavaScript
require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Cargar el entorno de Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Configurar log para mostrar en consola
Log::listen(function ($log) {
    echo "[" . $log->level . "] " . $log->message . PHP_EOL;
    if (!empty($log->context)) {
        echo "Contexto: " . json_encode($log->context, JSON_PRETTY_PRINT) . PHP_EOL;
    }
    echo "-------------------------------------------" . PHP_EOL;
});

// Datos de prueba
$testData = [
    'date' => '2025-03-10',
    'dueDate' => '2025-03-10',
    'client' => [
        'id' => "38"  // ID de cliente en Alegra como string
    ],
    'items' => [
        [
            'id' => "67",  // ID de producto en Alegra como string
            'price' => 26000,
            'quantity' => 1,
            'description' => 'ANKOFEN'
        ]
    ],
    'payment' => [
        'paymentMethod' => ['id' => 10],
        'account' => ['id' => 1]
    ],
    'paymentForm' => 'CASH',
    'paymentMethod' => 'CASH',
    'useElectronicInvoice' => true,
    'stamp' => [
        'generateStamp' => true,
        'generateQrCode' => true
    ]
];

// Guardar los datos en un archivo temporal para que Node.js pueda leerlos
$tempFile = __DIR__ . '/temp_invoice_data.json';
file_put_contents($tempFile, json_encode($testData, JSON_PRETTY_PRINT));

echo "Datos guardados en archivo temporal: $tempFile\n\n";

// Ejecutar el script de Node.js para enviar los datos a Alegra
$command = "node -e \"
const fs = require('fs');
const fetch = require('node-fetch');

// Leer datos del archivo
const data = JSON.parse(fs.readFileSync('$tempFile', 'utf8'));
console.log('Datos leídos del archivo:', JSON.stringify(data, null, 2));

// Enviar datos al servicio Python
fetch('http://localhost:8001/invoices', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify(data)
})
.then(response => response.json())
.then(result => {
    console.log('Respuesta del servicio:', JSON.stringify(result, null, 2));
    fs.writeFileSync('$tempFile.response', JSON.stringify(result, null, 2));
})
.catch(error => {
    console.error('Error:', error);
    fs.writeFileSync('$tempFile.error', JSON.stringify({error: error.message}, null, 2));
});
\"";

echo "Ejecutando comando Node.js...\n";
echo "$command\n\n";

// Ejecutar el comando
$output = [];
$returnVar = 0;
exec($command, $output, $returnVar);

echo "Salida del comando (código $returnVar):\n";
echo implode("\n", $output) . "\n\n";

// Esperar un momento para que el script de Node.js termine
sleep(2);

// Verificar si hay respuesta
if (file_exists("$tempFile.response")) {
    echo "Respuesta recibida:\n";
    echo file_get_contents("$tempFile.response") . "\n";
} elseif (file_exists("$tempFile.error")) {
    echo "Error recibido:\n";
    echo file_get_contents("$tempFile.error") . "\n";
} else {
    echo "No se recibió respuesta ni error.\n";
}

// Limpiar archivos temporales
@unlink($tempFile);
@unlink("$tempFile.response");
@unlink("$tempFile.error");
