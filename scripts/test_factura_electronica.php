<?php

// Cargar el entorno de Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Venta;
use App\Models\Cliente;
use App\Models\Producto;
use Illuminate\Support\Facades\Log;

// Configurar log
Log::info('Iniciando prueba de factura electrónica');

// Buscar una venta existente para probar
$venta = Venta::with(['cliente', 'detalles.producto'])->first();

if (!$venta) {
    echo "No se encontraron ventas para probar.\n";
    exit(1);
}

echo "Venta encontrada: ID {$venta->id}, Cliente: {$venta->cliente->nombres} {$venta->cliente->apellidos}\n";
echo "Fecha: {$venta->fecha_venta}, Total: {$venta->total}\n";
echo "Detalles:\n";

foreach ($venta->detalles as $detalle) {
    echo "- {$detalle->cantidad} x {$detalle->producto->nombre} ({$detalle->precio_unitario})\n";
}

// Sincronizar cliente si no tiene id_alegra
$cliente = $venta->cliente;
if (!$cliente->id_alegra) {
    echo "\nSincronizando cliente con Alegra...\n";
    $resultadoSync = $cliente->syncToAlegra();
    if ($resultadoSync['success']) {
        echo "Cliente sincronizado correctamente. ID Alegra: {$cliente->id_alegra}\n";
    } else {
        echo "Error al sincronizar cliente: {$resultadoSync['error']}\n";
        exit(1);
    }
} else {
    echo "\nCliente ya sincronizado. ID Alegra: {$cliente->id_alegra}\n";
}

// Sincronizar productos si no tienen id_alegra
echo "\nSincronizando productos con Alegra...\n";
foreach ($venta->detalles as $detalle) {
    $producto = $detalle->producto;
    if (!$producto->id_alegra) {
        echo "Sincronizando producto {$producto->nombre}...\n";
        $resultadoSync = $producto->syncToAlegra();
        if ($resultadoSync['success']) {
            echo "- Producto sincronizado correctamente. ID Alegra: {$producto->id_alegra}\n";
        } else {
            echo "- Error al sincronizar producto {$producto->nombre}: {$resultadoSync['error']}\n";
        }
    } else {
        echo "- Producto {$producto->nombre} ya sincronizado. ID Alegra: {$producto->id_alegra}\n";
    }
}

// Crear factura electrónica
echo "\nCreando factura electrónica...\n";
$controller = app(\App\Http\Controllers\VentaController::class);
$response = $controller->generarFacturaElectronica($venta);

// Mostrar resultado
echo "\nRespuesta:\n";
echo json_encode($response->getData(), JSON_PRETTY_PRINT);
echo "\n";
