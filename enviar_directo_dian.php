<?php

// Script para enviar una factura directamente a la DIAN sin intentar abrirla
// Cargar el framework Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// ID de la factura a enviar
$idFactura = isset($argv[1]) ? $argv[1] : null;

if (!$idFactura) {
    echo "Error: Debe proporcionar el ID de la factura como argumento\n";
    echo "Uso: php enviar_directo_dian.php ID_FACTURA\n";
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
    
    // Verificar si ya tiene CUFE (ya está emitida)
    if (isset($factura['stamp']) && isset($factura['stamp']['cufe'])) {
        echo "La factura ya está emitida electrónicamente con CUFE: " . $factura['stamp']['cufe'] . "\n";
        exit(0);
    }
} else {
    echo "Error al verificar estado: HTTP {$httpCode}\n";
    exit(1);
}

// Intentar enviar directamente a la DIAN sin abrir
echo "Intentando enviar la factura directamente a la DIAN...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.alegra.com/api/v1/invoices/{$idFactura}/stamp");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'generateStamp' => true,
    'generateQrCode' => true
]));
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

if ($httpCode >= 200 && $httpCode < 300) {
    echo "✅ La factura se envió correctamente a la DIAN.\n";
    
    // Verificar si tiene CUFE
    $responseData = json_decode($response, true);
    if (isset($responseData['stamp']) && isset($responseData['stamp']['cufe'])) {
        echo "CUFE: " . $responseData['stamp']['cufe'] . "\n";
    }
} else {
    echo "❌ Error al enviar la factura a la DIAN.\n";
    
    // Mostrar detalles del error si están disponibles
    $errorData = json_decode($response, true);
    if (isset($errorData['message'])) {
        echo "Mensaje de error: " . $errorData['message'] . "\n";
    }
}
