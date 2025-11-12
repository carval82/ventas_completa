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
    echo "Unidad de medida: " . ($producto->unidad_medida ?: 'No definida') . PHP_EOL;
    
    // Agregar item a la lista
    $items[] = [
        'id' => intval($producto->id_alegra),
        'price' => (float)$detalle->precio_unitario,
        'quantity' => (float)$detalle->cantidad,
        'description' => $producto->nombre
    ];
}

if (empty($items)) {
    echo "No se pudieron sincronizar los productos con Alegra." . PHP_EOL;
    exit(1);
}

// Preparar datos para la factura electrónica
$alegraData = [
    'date' => date('Y-m-d'),
    'dueDate' => date('Y-m-d'),
    'client' => [
        'id' => intval($cliente->id_alegra)
    ],
    'items' => $items,
    'paymentForm' => 'CASH',
    'paymentMethod' => 'CASH',
    'payment' => [
        'paymentMethod' => ['id' => 10],
        'account' => ['id' => 1]
    ],
    'numberTemplate' => [
        'id' => 19
    ]
];

// Guardar los datos para el script JS
$jsonFile = __DIR__ . '/invoice_data.json';
file_put_contents($jsonFile, json_encode($alegraData, JSON_PRETTY_PRINT));

echo "Datos guardados en $jsonFile" . PHP_EOL;
echo "Ejecutando script JavaScript..." . PHP_EOL;

// Crear script JS para enviar la factura
$jsScript = <<<'JS'
const fs = require('fs');
const https = require('https');

// Leer datos de la factura
const invoiceDataPath = process.argv[2];
const invoiceData = JSON.parse(fs.readFileSync(invoiceDataPath, 'utf8'));

// Credenciales de Alegra
const email = 'pcapacho24@hotmail.com';
const token = '4398994d2a44f8153123';
const auth = Buffer.from(`${email}:${token}`).toString('base64');

// Opciones de la solicitud
const options = {
  hostname: 'api.alegra.com',
  port: 443,
  path: '/api/v1/invoices',
  method: 'POST',
  headers: {
    'Authorization': `Basic ${auth}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
};

console.log('Enviando datos a Alegra...');
console.log(JSON.stringify(invoiceData, null, 2));

// Realizar la solicitud
const req = https.request(options, (res) => {
  console.log(`Código de estado: ${res.statusCode}`);
  
  let data = '';
  
  res.on('data', (chunk) => {
    data += chunk;
  });
  
  res.on('end', () => {
    try {
      const responseData = JSON.parse(data);
      console.log('Respuesta de Alegra:');
      console.log(JSON.stringify(responseData, null, 2));
      
      if (res.statusCode >= 200 && res.statusCode < 300) {
        console.log('Factura creada exitosamente');
        process.exit(0);
      } else {
        console.error('Error al crear factura:');
        console.error(JSON.stringify(responseData, null, 2));
        process.exit(1);
      }
    } catch (e) {
      console.error('Error al procesar la respuesta:', e.message);
      console.error('Datos recibidos:', data);
      process.exit(1);
    }
  });
});

req.on('error', (e) => {
  console.error('Error en la solicitud:', e.message);
  process.exit(1);
});

// Enviar los datos
req.write(JSON.stringify(invoiceData));
req.end();
JS;

$jsFile = __DIR__ . '/create_invoice.cjs';
file_put_contents($jsFile, $jsScript);

// Ejecutar el script JS
$command = "node $jsFile $jsonFile";
echo "Ejecutando: $command" . PHP_EOL;
$output = [];
$returnCode = 0;
exec($command, $output, $returnCode);

echo PHP_EOL . "Salida del script JS:" . PHP_EOL;
echo implode(PHP_EOL, $output) . PHP_EOL;

echo PHP_EOL . "Código de retorno: $returnCode" . PHP_EOL;

if ($returnCode === 0) {
    echo "La factura se creó exitosamente en Alegra." . PHP_EOL;
} else {
    echo "Hubo un error al crear la factura en Alegra." . PHP_EOL;
}
