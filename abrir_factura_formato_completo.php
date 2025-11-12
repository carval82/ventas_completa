<?php

// Script para abrir una factura en Alegra usando un formato completo para el método de pago
// Cargar el framework Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// ID de la factura a abrir
$idFactura = isset($argv[1]) ? $argv[1] : null;

if (!$idFactura) {
    echo "Error: Debe proporcionar el ID de la factura como argumento\n";
    echo "Uso: php abrir_factura_formato_completo.php ID_FACTURA\n";
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

// Verificar estado actual
echo "Verificando estado actual de la factura {$idFactura}...\n";
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
    echo "Estado actual: " . $factura['status'] . "\n";
} else {
    echo "Error al verificar estado: HTTP {$httpCode}\n";
    exit(1);
}

// Abrir la factura con formato completo para el método de pago
echo "Intentando abrir la factura con formato completo para el método de pago...\n";

// Formato completo según los requisitos
$datos = json_encode([
    'payment' => [
        'paymentMethod' => [
            'id' => 10  // ID del método de pago (efectivo)
        ],
        'account' => [
            'id' => 1   // ID de la cuenta
        ]
    ]
]);

echo "Datos enviados: " . $datos . "\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.alegra.com/api/v1/invoices/{$idFactura}/open");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_POSTFIELDS, $datos);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Basic ' . base64_encode($email . ':' . $token)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Respuesta HTTP: {$httpCode}\n";
echo "Respuesta: {$response}\n";

// Esperar un momento
echo "Esperando 3 segundos...\n";
sleep(3);

// Verificar estado nuevamente
echo "Verificando estado después de intentar abrir...\n";
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
    echo "Estado después de intentar abrir: " . $factura['status'] . "\n";
    
    if ($factura['status'] === 'open') {
        echo "✅ La factura se abrió correctamente.\n";
    } else {
        echo "❌ La factura no cambió a estado abierto.\n";
    }
} else {
    echo "Error al verificar estado: HTTP {$httpCode}\n";
}
