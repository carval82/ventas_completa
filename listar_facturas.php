<?php

// Cargar el framework Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Importar las clases necesarias
use App\Services\AlegraService;
use Illuminate\Support\Facades\Log;

// Crear instancia del servicio de Alegra
$alegraService = new AlegraService();

// Obtener credenciales
$credenciales = null;
try {
    // Intentar obtener las credenciales de la empresa
    $empresa = \App\Models\Empresa::first();
    
    if ($empresa && $empresa->alegra_email && $empresa->alegra_token) {
        // Usar credenciales de la empresa
        $email = $empresa->alegra_email;
        $token = $empresa->alegra_token;
    } else {
        // Usar credenciales del archivo .env como respaldo
        $email = config('alegra.user');
        $token = config('alegra.token');
    }
    
    $credenciales = [
        'email' => $email,
        'token' => $token
    ];
} catch (\Exception $e) {
    echo "Error al obtener credenciales: " . $e->getMessage() . "\n";
    exit(1);
}

// Configurar cURL para listar facturas
$ch = curl_init();
$url = "https://api.alegra.com/api/v1/invoices?status=draft,open&limit=10";

// Configurar opciones de cURL
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Basic ' . base64_encode($credenciales['email'] . ':' . $credenciales['token'])
]);

// Ejecutar la solicitud
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

// Verificar si hubo errores
if ($httpCode < 200 || $httpCode >= 300) {
    echo "Error al obtener facturas: HTTP $httpCode\n";
    echo "Respuesta: $response\n";
    echo "Error cURL: $error\n";
    exit(1);
}

// Procesar la respuesta
$facturas = json_decode($response, true);

if (empty($facturas)) {
    echo "No se encontraron facturas en estado borrador o abierto.\n";
    exit(0);
}

// Mostrar las facturas encontradas
echo "Facturas encontradas (" . count($facturas) . "):\n";
echo str_repeat('-', 80) . "\n";
echo sprintf("%-10s | %-20s | %-15s | %-15s | %-10s\n", "ID", "Fecha", "Cliente", "Total", "Estado");
echo str_repeat('-', 80) . "\n";

foreach ($facturas as $factura) {
    $id = $factura['id'];
    $fecha = $factura['date'];
    $cliente = isset($factura['client']['name']) ? $factura['client']['name'] : 'N/A';
    $total = isset($factura['total']) ? number_format($factura['total'], 2) : 'N/A';
    $estado = $factura['status'];
    
    echo sprintf("%-10s | %-20s | %-15s | %-15s | %-10s\n", 
        $id, $fecha, substr($cliente, 0, 15), $total, $estado);
}

echo str_repeat('-', 80) . "\n";
echo "Para emitir una factura, ejecute: php test_emitir_factura_existente.php ID_FACTURA\n";
