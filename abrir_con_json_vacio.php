<?php
/**
 * Script para abrir una factura usando un objeto JSON vacío
 * Este enfoque directo evita las capas de abstracción de la aplicación
 */

// Cargar el framework Laravel para tener acceso a las credenciales
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// ID de la factura de Alegra (se pasa como argumento)
$idFactura = isset($argv[1]) ? $argv[1] : null;

if (!$idFactura) {
    echo "Error: Debe proporcionar el ID de la factura en Alegra\n";
    echo "Uso: php abrir_con_json_vacio.php ID_FACTURA_ALEGRA\n";
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

echo "=================================================================\n";
echo "          APERTURA DE FACTURA CON JSON VACÍO\n";
echo "=================================================================\n";
echo "ID de Factura: $idFactura\n";
echo "Credenciales: $email\n";
echo "-----------------------------------------------------------------\n";

// Primero verificamos el estado actual de la factura
$ch = curl_init();
$url = "https://api.alegra.com/api/v1/invoices/{$idFactura}";
curl_setopt($ch, CURLOPT_URL, $url);
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
    echo "✅ Estado actual de la factura: " . $factura['status'] . "\n";
    
    if ($factura['status'] !== 'draft') {
        echo "⚠️ La factura no está en estado borrador (draft), está en estado: " . $factura['status'] . "\n";
        echo "Solo se pueden abrir facturas en estado borrador.\n";
        exit(1);
    }
} else {
    echo "❌ Error al obtener la factura: HTTP " . $httpCode . "\n";
    echo "Respuesta: " . $response . "\n";
    exit(1);
}

// Intentar abrir la factura con un objeto JSON vacío
echo "\n>>> Intentando abrir la factura con JSON vacío...\n";

$ch = curl_init();
$url = "https://api.alegra.com/api/v1/invoices/{$idFactura}/open";
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_POSTFIELDS, "{}"); // JSON vacío
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Basic ' . base64_encode($email . ':' . $token)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Código de respuesta HTTP: " . $httpCode . "\n";
echo "Respuesta completa: " . $response . "\n";

if ($httpCode >= 200 && $httpCode < 300) {
    echo "✅ ¡Éxito! La factura se abrió correctamente con JSON vacío.\n";
    
    // Verificar el nuevo estado
    $ch = curl_init();
    $url = "https://api.alegra.com/api/v1/invoices/{$idFactura}";
    curl_setopt($ch, CURLOPT_URL, $url);
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
        echo "Estado actual de la factura: " . $factura['status'] . "\n";
    }
} else {
    echo "❌ Error al abrir la factura: HTTP " . $httpCode . "\n";
    
    // Intentar analizar la respuesta de error
    $errorData = json_decode($response, true);
    if ($errorData && isset($errorData['message'])) {
        echo "Mensaje de error: " . $errorData['message'] . "\n";
    }
    
    echo "\n>>> Probemos ahora con un formato básico mínimo...\n";
    
    // Intentar con formato básico
    $ch = curl_init();
    $url = "https://api.alegra.com/api/v1/invoices/{$idFactura}/open";
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{"paymentForm":"CASH"}'); // Solo paymentForm
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($email . ':' . $token)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Código de respuesta HTTP: " . $httpCode . "\n";
    echo "Respuesta completa: " . $response . "\n";
    
    if ($httpCode >= 200 && $httpCode < 300) {
        echo "✅ ¡Éxito! La factura se abrió correctamente con formato básico.\n";
    }
}

echo "\n=================================================================\n";
echo "                    PROCESO FINALIZADO\n";
echo "=================================================================\n";
