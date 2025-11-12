<?php
/**
 * Script de prueba para la integración directa de Laravel con Alegra
 * Este script simula el proceso que realizará el controlador VentaController
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno desde .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Configurar logging
$logFile = __DIR__ . '/../storage/logs/test_laravel_alegra.log';
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Iniciando prueba de integración con Alegra\n", FILE_APPEND);

function log_message($message, $data = null) {
    global $logFile;
    $log = "[" . date('Y-m-d H:i:s') . "] $message";
    if ($data !== null) {
        $log .= ": " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    $log .= "\n";
    file_put_contents($logFile, $log, FILE_APPEND);
    echo $log;
}

// Datos de prueba para la factura
$invoiceData = [
    'client' => [
        'id' => 23  // ID de cliente en Alegra (agroservicios R & D S.A.S.)
    ],
    'items' => [
        [
            'id' => 45,  // ID de producto en Alegra (desinstalacion sistema CCTV)
            'price' => 300000,
            'quantity' => 1
        ]
    ],
    'date' => date('Y-m-d'),
    'dueDate' => date('Y-m-d'),
    'paymentForm' => 'CASH',
    'paymentMethod' => 'CASH',
    'payment' => [
        'paymentMethod' => ['id' => 10],  // 10 = Efectivo según DIAN
        'account' => ['id' => 1]          // Cuenta por defecto
    ],
    'numberTemplate' => [
        'id' => 19  // ID de plantilla de factura electrónica (ELECTRONICA 2025)
    ]
];

log_message("Datos de factura preparados", $invoiceData);

// Guardar los datos de la factura en un archivo temporal
$tempJsonFile = __DIR__ . '/../storage/app/temp_invoice_data.json';
file_put_contents($tempJsonFile, json_encode($invoiceData));
log_message("Datos guardados en archivo temporal", $tempJsonFile);

// Ejecutar el script Python
$pythonScript = __DIR__ . '/create_invoice_from_file.py';
$command = 'python ' . escapeshellarg($pythonScript) . ' ' . escapeshellarg($tempJsonFile);
log_message("Ejecutando comando", $command);

$output = [];
$returnCode = 0;
exec($command, $output, $returnCode);

// Eliminar archivo temporal
unlink($tempJsonFile);
log_message("Archivo temporal eliminado");

// Procesar la respuesta
$outputString = implode("\n", $output);
$response = json_decode($outputString, true);

log_message("Código de retorno", $returnCode);
log_message("Respuesta del script Python", $response);

if ($returnCode === 0 && isset($response['success']) && $response['success']) {
    log_message("¡Factura creada exitosamente!", $response['data']);
    
    // Aquí simularíamos la actualización de la venta en la base de datos
    log_message("Datos que se actualizarían en la venta:");
    log_message("- alegra_id: " . $response['data']['id']);
    
    if (isset($response['data']['stamp'])) {
        log_message("- cufe: " . ($response['data']['stamp']['cufe'] ?? 'No disponible'));
        log_message("- qr_code: " . ($response['data']['stamp']['barCodeContent'] ?? 'No disponible'));
        log_message("- estado_dian: " . ($response['data']['stamp']['legalStatus'] ?? 'No disponible'));
    } else {
        log_message("- No hay datos de sello electrónico disponibles");
    }
    
    if (isset($response['data']['numberTemplate'])) {
        log_message("- url_pdf: " . ($response['data']['numberTemplate']['fullNumber'] ?? 'No disponible'));
    }
} else {
    log_message("Error al crear la factura", isset($response['error']) ? $response['error'] : 'Error desconocido');
}

log_message("Prueba de integración finalizada");
