<?php

// Cargar el entorno de Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Venta;
use App\Models\Cliente;
use App\Models\Producto;
use App\Services\AlegraService;
use Illuminate\Support\Facades\Log;

// Configurar log para mostrar en consola
Log::listen(function ($log) {
    echo "[" . $log->level . "] " . $log->message . PHP_EOL;
    if (!empty($log->context)) {
        echo "Contexto: " . json_encode($log->context, JSON_PRETTY_PRINT) . PHP_EOL;
    }
    echo "-------------------------------------------" . PHP_EOL;
});

// Buscar una venta para prueba
$venta = Venta::with(['detalles', 'cliente'])->first();

if (!$venta) {
    echo "No se encontraron ventas para probar." . PHP_EOL;
    exit(1);
}

echo "Venta encontrada: ID {$venta->id}, Cliente: {$venta->cliente->nombres} {$venta->cliente->apellidos}" . PHP_EOL;
echo "Fecha: {$venta->fecha_venta}, Total: {$venta->total}" . PHP_EOL;
echo "Detalles:" . PHP_EOL;

foreach ($venta->detalles as $detalle) {
    $producto = Producto::find($detalle->producto_id);
    echo "- {$detalle->cantidad} x {$producto->nombre} ({$detalle->precio_unitario})" . PHP_EOL;
}

echo PHP_EOL;

// Sincronizar cliente con Alegra
echo "Sincronizando cliente con Alegra..." . PHP_EOL;
$cliente = Cliente::find($venta->cliente_id);
$resultadoCliente = $cliente->syncToAlegra();

if (!$resultadoCliente['success']) {
    echo "Error al sincronizar cliente: {$resultadoCliente['error']}" . PHP_EOL;
    exit(1);
}

echo "Cliente sincronizado con Alegra. ID Alegra: {$cliente->id_alegra}" . PHP_EOL;

// Sincronizar productos con Alegra
echo "Sincronizando productos con Alegra..." . PHP_EOL;
$items = [];

foreach ($venta->detalles as $detalle) {
    $producto = Producto::find($detalle->producto_id);
    $resultadoProducto = $producto->syncToAlegra();
    
    if (!$resultadoProducto['success']) {
        echo "Error al sincronizar producto {$producto->nombre}: {$resultadoProducto['error']}" . PHP_EOL;
        continue;
    }
    
    echo "Producto {$producto->nombre} sincronizado con Alegra. ID Alegra: {$producto->id_alegra}" . PHP_EOL;
    
    // Agregar item a la lista
    $items[] = [
        'id' => intval($producto->id_alegra),
        'price' => (float)$detalle->precio_unitario,
        'quantity' => (int)$detalle->cantidad,
        'description' => $producto->nombre
    ];
}

if (empty($items)) {
    echo "No se pudieron sincronizar los productos con Alegra." . PHP_EOL;
    exit(1);
}

// Crear servicio Alegra
$alegraService = app(AlegraService::class);

// Mapear forma de pago
$formaPago = $alegraService->mapearFormaPago($venta->forma_pago ?? 'efectivo');

// Preparar datos para la factura electrónica
$alegraData = [
    'date' => $venta->fecha_venta,
    'dueDate' => $venta->fecha_venta,
    'client' => [
        'id' => intval($cliente->id_alegra)
    ],
    'items' => $items,
    'payment' => $formaPago,
    'useElectronicInvoice' => true,
    'stamp' => [
        'generateStamp' => true,
        'generateQrCode' => true
    ]
];

echo "Enviando datos a Alegra..." . PHP_EOL;
echo "Datos: " . json_encode($alegraData, JSON_PRETTY_PRINT) . PHP_EOL;

// Crear factura en Alegra
$response = $alegraService->crearFactura($alegraData);

echo PHP_EOL . "Respuesta de Alegra:" . PHP_EOL;
echo json_encode($response, JSON_PRETTY_PRINT) . PHP_EOL;

if ($response['success']) {
    echo PHP_EOL . "¡Factura creada exitosamente en Alegra!" . PHP_EOL;
    echo "ID de factura en Alegra: {$response['data']['id']}" . PHP_EOL;
    
    // Actualizar venta con datos de Alegra
    $venta->update([
        'alegra_id' => $response['data']['id'],
        'cufe' => $response['data']['stamp']['cufe'] ?? null,
        'qr_code' => $response['data']['stamp']['barCodeContent'] ?? null,
        'estado_dian' => $response['data']['stamp']['legalStatus'] ?? null,
        'url_pdf' => $response['data']['numberTemplate']['fullNumber'] ?? null
    ]);
    
    echo "Venta actualizada con datos de Alegra." . PHP_EOL;
} else {
    echo PHP_EOL . "Error al crear factura en Alegra: {$response['error']}" . PHP_EOL;
}
