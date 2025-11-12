<?php
/**
 * Script compacto para abrir facturas en Alegra
 * Versión optimizada para mostrar resultados claros
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Empresa;

// Verificar argumentos
if (!isset($argv[1])) {
    echo "Error: Debe proporcionar el ID de la factura como argumento.\n";
    echo "Uso: php abrir_factura_compacto.php <id_factura>\n";
    exit(1);
}

$facturaId = $argv[1];
echo "Iniciando proceso para factura con ID: {$facturaId}\n\n";

// Obtener credenciales
$empresa = Empresa::first();
$email = $empresa->alegra_email;
$token = $empresa->alegra_token;

// 1. Consultar estado inicial
echo "1. Estado inicial: ";
$facturaInicial = consultarFactura($facturaId, $email, $token);
echo ($facturaInicial['status'] ?? 'desconocido') . "\n";

// 2. Intentar modificar la factura directamente
echo "2. Modificando factura directamente... ";
$resultado = modificarFactura($facturaId, $email, $token, [
    'status' => 'open',
    'paymentForm' => 'CASH',
    'paymentMethod' => 'CASH'
]);
echo "HTTP " . $resultado['http_code'] . "\n";

// Esperar un momento
sleep(3);

// 3. Verificar estado final
echo "3. Estado final: ";
$facturaFinal = consultarFactura($facturaId, $email, $token);
echo ($facturaFinal['status'] ?? 'desconocido') . "\n";

// 4. Resultado
if (isset($facturaFinal['status']) && $facturaFinal['status'] === 'open') {
    echo "\n✅ La factura se abrió correctamente.\n";
} else {
    echo "\n❌ La factura NO se abrió. Intentando método alternativo...\n";
    
    // Intentar con el endpoint específico
    echo "5. Usando endpoint específico... ";
    $resultadoEndpoint = abrirFacturaEndpoint($facturaId, $email, $token);
    echo "HTTP " . $resultadoEndpoint['http_code'] . "\n";
    
    // Esperar un momento
    sleep(3);
    
    // Verificar estado final
    echo "6. Estado final: ";
    $facturaFinal2 = consultarFactura($facturaId, $email, $token);
    echo ($facturaFinal2['status'] ?? 'desconocido') . "\n";
    
    if (isset($facturaFinal2['status']) && $facturaFinal2['status'] === 'open') {
        echo "\n✅ La factura se abrió correctamente con el método alternativo.\n";
    } else {
        echo "\n❌ La factura NO se pudo abrir con ninguno de los métodos.\n";
    }
}

/**
 * Función para consultar una factura en Alegra
 */
function consultarFactura($facturaId, $email, $token) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.alegra.com/api/v1/invoices/{$facturaId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($email . ':' . $token)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ($httpCode >= 200 && $httpCode < 300) ? json_decode($response, true) : ['status' => 'error'];
}

/**
 * Función para modificar una factura completa en Alegra
 */
function modificarFactura($facturaId, $email, $token, $datos) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.alegra.com/api/v1/invoices/{$facturaId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($email . ':' . $token)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['http_code' => $httpCode, 'response' => $response];
}

/**
 * Función para abrir una factura usando el endpoint específico
 */
function abrirFacturaEndpoint($facturaId, $email, $token) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.alegra.com/api/v1/invoices/{$facturaId}/open");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'paymentForm' => 'CASH',
        'paymentMethod' => 'CASH'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($email . ':' . $token)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['http_code' => $httpCode, 'response' => $response];
}
