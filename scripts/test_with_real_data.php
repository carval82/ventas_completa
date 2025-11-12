<?php
/**
 * Script de prueba para la integración de Alegra con datos reales de la aplicación
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Cargar el framework Laravel para acceder a los modelos
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Cliente;
use App\Models\Producto;
use Illuminate\Support\Facades\Log;

// Configurar logging
$logFile = __DIR__ . '/../storage/logs/test_real_data.log';
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Iniciando prueba con datos reales\n", FILE_APPEND);

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

// 1. Obtener un cliente de la base de datos
$cliente = Cliente::where('estado', 1)->first();
if (!$cliente) {
    log_message("Error: No se encontró ningún cliente activo en la base de datos");
    exit(1);
}

log_message("Cliente seleccionado", [
    'id' => $cliente->id,
    'nombre' => $cliente->nombres . ' ' . $cliente->apellidos,
    'cedula' => $cliente->cedula,
    'id_alegra' => $cliente->id_alegra
]);

// 2. Sincronizar cliente con Alegra si no tiene id_alegra
if (!$cliente->id_alegra) {
    log_message("Cliente no tiene ID de Alegra, sincronizando...");
    $resultadoSync = $cliente->syncToAlegra();
    
    if (!$resultadoSync['success']) {
        log_message("Error al sincronizar cliente con Alegra", $resultadoSync['error'] ?? 'Error desconocido');
        exit(1);
    }
    
    log_message("Cliente sincronizado con Alegra", [
        'id_alegra' => $cliente->id_alegra
    ]);
}

// 3. Obtener un producto de la base de datos
$producto = Producto::where('estado', 1)->where('stock', '>', 0)->first();
if (!$producto) {
    log_message("Error: No se encontró ningún producto activo con stock en la base de datos");
    exit(1);
}

log_message("Producto seleccionado", [
    'id' => $producto->id,
    'nombre' => $producto->nombre,
    'precio' => $producto->precio_venta,
    'id_alegra' => $producto->id_alegra
]);

// 4. Sincronizar producto con Alegra si no tiene id_alegra
if (!$producto->id_alegra) {
    log_message("Producto no tiene ID de Alegra, sincronizando...");
    $resultadoSync = $producto->syncToAlegra();
    
    if (!$resultadoSync['success']) {
        log_message("Error al sincronizar producto con Alegra", $resultadoSync['error'] ?? 'Error desconocido');
        exit(1);
    }
    
    log_message("Producto sincronizado con Alegra", [
        'id_alegra' => $producto->id_alegra
    ]);
}

// 5. Preparar datos para la factura electrónica
$invoiceData = [
    'client' => [
        'id' => (int)$cliente->id_alegra
    ],
    'items' => [
        [
            'id' => (int)$producto->id_alegra,
            'price' => (float)$producto->precio_venta,
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

// 6. Guardar los datos de la factura en un archivo temporal
$tempJsonFile = __DIR__ . '/../storage/app/temp_invoice_data.json';
file_put_contents($tempJsonFile, json_encode($invoiceData));
log_message("Datos guardados en archivo temporal", $tempJsonFile);

// 7. Ejecutar el script Python
$pythonScript = __DIR__ . '/create_invoice_from_file.py';
$command = 'python ' . escapeshellarg($pythonScript) . ' ' . escapeshellarg($tempJsonFile);
log_message("Ejecutando comando", $command);

$output = [];
$returnCode = 0;
exec($command, $output, $returnCode);

// 8. Eliminar archivo temporal
unlink($tempJsonFile);
log_message("Archivo temporal eliminado");

// 9. Procesar la respuesta
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

log_message("Prueba con datos reales finalizada");
