<?php

// Script para revisar una factura ya abierta en Alegra
// Cargar el framework Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// ID de la factura a revisar
$idFactura = isset($argv[1]) ? $argv[1] : null;

if (!$idFactura) {
    echo "Error: Debe proporcionar el ID de la factura como argumento\n";
    echo "Uso: php revisar_factura_abierta.php ID_FACTURA\n";
    exit(1);
}

// Obtener credenciales
$empresa = \App\Models\Empresa::first();
if ($empresa && $empresa->alegra_email && $empresa->alegra_token) {
    $email = $empresa->alegra_email;
    $token = $empresa->alegra_token;
    echo "Usando credenciales de la empresa\n";
} else {
    $email = config('alegra.user');
    $token = config('alegra.token');
    echo "Usando credenciales del archivo .env\n";
}

// Obtener detalles de la factura
echo "Obteniendo detalles de la factura {$idFactura}...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.alegra.com/api/v1/invoices/{$idFactura}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Basic ' . base64_encode($email . ':' . $token)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    $factura = json_decode($response, true);
    
    echo "\n=== DETALLES DE LA FACTURA ===\n";
    echo "ID: " . $factura['id'] . "\n";
    echo "Número: " . $factura['numberTemplate']['fullNumber'] . "\n";
    echo "Estado: " . $factura['status'] . "\n";
    echo "Fecha: " . $factura['date'] . "\n";
    echo "Cliente: " . $factura['client']['name'] . " (ID: " . $factura['client']['id'] . ")\n";
    
    echo "\n=== DETALLES DE PAGO ===\n";
    echo "Forma de pago: " . ($factura['paymentForm'] ?? 'No especificada') . "\n";
    echo "Método de pago: " . ($factura['paymentMethod'] ?? 'No especificado') . "\n";
    
    if (isset($factura['payment'])) {
        echo "\nObjeto 'payment':\n";
        print_r($factura['payment']);
    } else {
        echo "No hay objeto 'payment' en la factura\n";
    }
    
    echo "\n=== DETALLES DE ITEMS ===\n";
    foreach ($factura['items'] as $index => $item) {
        echo "Item " . ($index + 1) . ": " . $item['name'] . " - Cantidad: " . $item['quantity'] . " - Precio: " . $item['price'] . "\n";
    }
    
    echo "\n=== DETALLES DE PLANTILLA DE NUMERACIÓN ===\n";
    echo "ID: " . $factura['numberTemplate']['id'] . "\n";
    echo "Prefijo: " . $factura['numberTemplate']['prefix'] . "\n";
    echo "Número: " . $factura['numberTemplate']['number'] . "\n";
    echo "Es electrónica: " . ($factura['numberTemplate']['isElectronic'] ? 'Sí' : 'No') . "\n";
    
    if (isset($factura['stamp'])) {
        echo "\n=== DETALLES DE SELLO ELECTRÓNICO ===\n";
        echo "CUFE: " . ($factura['stamp']['cufe'] ?? 'No disponible') . "\n";
        echo "QR Code: " . (isset($factura['stamp']['qrCode']) ? 'Disponible' : 'No disponible') . "\n";
    } else {
        echo "\nNo hay información de sello electrónico\n";
    }
    
    // Mostrar la estructura completa para análisis
    echo "\n=== ESTRUCTURA COMPLETA DE LA FACTURA ===\n";
    print_r($factura);
    
} else {
    echo "Error al obtener detalles de la factura: HTTP {$httpCode}\n";
    echo "Respuesta: {$response}\n";
}
