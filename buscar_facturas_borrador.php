<?php
/**
 * Script para buscar facturas en estado borrador (draft) en Alegra
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Empresa;

// Obtener credenciales
$empresa = Empresa::first();

if (!$empresa || empty($empresa->alegra_email) || empty($empresa->alegra_token)) {
    echo "Error: No se encontraron credenciales de Alegra válidas.\n";
    exit(1);
}

$email = $empresa->alegra_email;
$token = $empresa->alegra_token;

echo "Buscando facturas en estado borrador (draft)...\n\n";

// Configurar cURL para buscar facturas
$ch = curl_init();
$url = "https://api.alegra.com/api/v1/invoices?status=draft&limit=10";

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Basic ' . base64_encode($email . ':' . $token)
]);

// Ejecutar la solicitud
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

// Verificar si hubo errores
if (!empty($error)) {
    echo "Error cURL: {$error}\n";
    exit(1);
}

if ($httpCode != 200) {
    echo "Error HTTP: {$httpCode}\n";
    echo "Respuesta: {$response}\n";
    exit(1);
}

// Procesar la respuesta
$facturas = json_decode($response, true);

if (empty($facturas)) {
    echo "No se encontraron facturas en estado borrador.\n";
    exit(0);
}

echo "Se encontraron " . count($facturas) . " facturas en estado borrador:\n\n";

// Mostrar información de las facturas
foreach ($facturas as $index => $factura) {
    echo "Factura #" . ($index + 1) . ":\n";
    echo "ID: " . $factura['id'] . "\n";
    echo "Número: " . ($factura['numberTemplate']['prefix'] ?? '') . ($factura['numberTemplate']['number'] ?? '') . "\n";
    echo "Fecha: " . $factura['date'] . "\n";
    echo "Cliente: " . $factura['client']['name'] . "\n";
    echo "Total: " . $factura['total'] . "\n";
    echo "Estado: " . $factura['status'] . "\n";
    echo "----------------------------------------\n";
}

echo "\nPara probar la apertura de una factura, use el comando:\n";
echo "php test_abrir_factura_mejorado_v2.php <id_factura>\n";
echo "Ejemplo: php test_abrir_factura_mejorado_v2.php " . $facturas[0]['id'] . "\n";
