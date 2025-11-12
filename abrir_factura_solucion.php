<?php
/**
 * Script solución para abrir facturas en Alegra
 * Este script utiliza un enfoque específico basado en la documentación de Alegra
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Empresa;

// Verificar argumentos
if (!isset($argv[1])) {
    echo "Error: Debe proporcionar el ID de la factura como argumento.\n";
    echo "Uso: php abrir_factura_solucion.php <id_factura>\n";
    exit(1);
}

$facturaId = $argv[1];
echo "Iniciando proceso para factura con ID: {$facturaId}\n\n";

// Obtener credenciales
$empresa = Empresa::first();

if (!$empresa || empty($empresa->alegra_email) || empty($empresa->alegra_token)) {
    echo "Error: No se encontraron credenciales de Alegra válidas.\n";
    exit(1);
}

$email = $empresa->alegra_email;
$token = $empresa->alegra_token;

// 1. Consultar estado inicial
echo "1. Consultando estado inicial de la factura...\n";
$estadoInicial = consultarFactura($facturaId, $email, $token);
echo "   Estado inicial: " . ($estadoInicial['status'] ?? 'desconocido') . "\n\n";

// Si la factura ya está abierta, no hacer nada
if (isset($estadoInicial['status']) && $estadoInicial['status'] === 'open') {
    echo "La factura ya está en estado 'open'. No es necesario cambiarla.\n";
    exit(0);
}

// 2. Intentar abrir la factura con el formato específico
echo "2. Intentando abrir la factura...\n";

// Payload específico según la documentación de Alegra
$payload = [
    "id" => intval($facturaId),
    "paymentForm" => "CASH",
    "paymentMethod" => "CASH",
    "status" => "open"
];

echo "   Payload: " . json_encode($payload) . "\n";

$resultado = abrirFactura($facturaId, $email, $token, $payload);
echo "   Resultado: " . ($resultado['success'] ? 'Éxito' : 'Error') . " (HTTP " . $resultado['http_code'] . ")\n";
echo "   Respuesta: " . substr($resultado['response'], 0, 100) . "\n\n";

// Esperar un momento
echo "   Esperando 3 segundos...\n";
sleep(3);

// 3. Verificar estado después del intento
echo "3. Verificando estado después del intento...\n";
$estadoFinal = consultarFactura($facturaId, $email, $token);
echo "   Estado final: " . ($estadoFinal['status'] ?? 'desconocido') . "\n\n";

// 4. Mostrar resultado final
echo "4. Resultado final:\n";
if (isset($estadoFinal['status']) && $estadoFinal['status'] === 'open') {
    echo "   ✅ La factura se abrió correctamente.\n";
} else {
    echo "   ❌ La factura NO se abrió. Sigue en estado: " . ($estadoFinal['status'] ?? 'desconocido') . "\n";
    
    // Si falló con el primer payload, intentar con otro formato
    echo "\n5. Intentando con formato alternativo...\n";
    
    // Payload alternativo
    $payloadAlt = [
        "paymentForm" => "CASH",
        "paymentMethod" => "CASH"
    ];
    
    echo "   Payload: " . json_encode($payloadAlt) . "\n";
    
    $resultadoAlt = abrirFacturaDirecto($facturaId, $email, $token);
    echo "   Resultado: " . ($resultadoAlt['success'] ? 'Éxito' : 'Error') . " (HTTP " . $resultadoAlt['http_code'] . ")\n";
    
    // Esperar un momento
    echo "   Esperando 3 segundos...\n";
    sleep(3);
    
    // Verificar estado
    $estadoFinal2 = consultarFactura($facturaId, $email, $token);
    echo "   Estado después del segundo intento: " . ($estadoFinal2['status'] ?? 'desconocido') . "\n";
    
    if (isset($estadoFinal2['status']) && $estadoFinal2['status'] === 'open') {
        echo "   ✅ La factura se abrió correctamente con el formato alternativo.\n";
    } else {
        echo "   ❌ La factura NO se abrió con el formato alternativo.\n";
    }
}

echo "\nProceso finalizado.\n";

/**
 * Función para consultar una factura en Alegra
 */
function consultarFactura($facturaId, $email, $token) {
    // Configurar cURL
    $ch = curl_init();
    $url = "https://api.alegra.com/api/v1/invoices/{$facturaId}";
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
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
    
    // Procesar la respuesta
    if ($httpCode >= 200 && $httpCode < 300) {
        return json_decode($response, true);
    }
    
    return [
        'success' => false,
        'error' => $response,
        'http_code' => $httpCode
    ];
}

/**
 * Función para abrir una factura en Alegra usando PUT en /invoices/{id}/open
 */
function abrirFactura($facturaId, $email, $token, $datos) {
    // Configurar cURL
    $ch = curl_init();
    $url = "https://api.alegra.com/api/v1/invoices/{$facturaId}/open";
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
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
    
    return [
        'success' => ($httpCode >= 200 && $httpCode < 300),
        'http_code' => $httpCode,
        'response' => $response,
        'error' => $error
    ];
}

/**
 * Función alternativa para abrir una factura en Alegra usando PUT en /invoices/{id}
 */
function abrirFacturaDirecto($facturaId, $email, $token) {
    // Configurar cURL
    $ch = curl_init();
    $url = "https://api.alegra.com/api/v1/invoices/{$facturaId}";
    
    // Payload para cambiar el estado directamente
    $datos = json_encode([
        "status" => "open",
        "paymentForm" => "CASH",
        "paymentMethod" => "CASH"
    ]);
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datos);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
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
    
    return [
        'success' => ($httpCode >= 200 && $httpCode < 300),
        'http_code' => $httpCode,
        'response' => $response,
        'error' => $error
    ];
}
