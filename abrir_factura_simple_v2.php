<?php
/**
 * Script simple para abrir facturas en Alegra
 * Versión 2.0 - Enfoque directo con mejor manejo de errores
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Empresa;

// Verificar argumentos
if (!isset($argv[1])) {
    echo "Error: Debe proporcionar el ID de la factura como argumento.\n";
    echo "Uso: php abrir_factura_simple_v2.php <id_factura>\n";
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

// 2. Intentar abrir la factura con formato completo
echo "2. Intentando abrir la factura con formato completo...\n";
$resultadoCompleto = abrirFactura($facturaId, $email, $token, [
    'paymentForm' => 'CASH',
    'paymentMethod' => 'CASH'
]);
echo "   Resultado: " . ($resultadoCompleto['success'] ? 'Éxito' : 'Error') . " (HTTP " . $resultadoCompleto['http_code'] . ")\n";
echo "   Respuesta: " . substr($resultadoCompleto['response'], 0, 100) . "\n\n";

// Esperar un momento
echo "   Esperando 3 segundos...\n";
sleep(3);

// 3. Verificar estado después del intento
echo "3. Verificando estado después del intento...\n";
$estadoFinal = consultarFactura($facturaId, $email, $token);
echo "   Estado final: " . ($estadoFinal['status'] ?? 'desconocido') . "\n\n";

// 4. Mostrar resultado final
echo "4. Resultado final:\n";
if ($estadoFinal['status'] === 'open') {
    echo "   ✅ La factura se abrió correctamente.\n";
} else {
    echo "   ❌ La factura NO se abrió. Sigue en estado: " . $estadoFinal['status'] . "\n";
    
    // Si falló con el formato completo, intentar con formato mínimo
    if ($estadoFinal['status'] !== 'open') {
        echo "\n5. Intentando con formato mínimo...\n";
        $resultadoMinimo = abrirFactura($facturaId, $email, $token, [
            'paymentForm' => 'CASH'
        ]);
        echo "   Resultado: " . ($resultadoMinimo['success'] ? 'Éxito' : 'Error') . " (HTTP " . $resultadoMinimo['http_code'] . ")\n";
        
        // Esperar un momento
        echo "   Esperando 3 segundos...\n";
        sleep(3);
        
        // Verificar estado
        $estadoFinal2 = consultarFactura($facturaId, $email, $token);
        echo "   Estado después del segundo intento: " . ($estadoFinal2['status'] ?? 'desconocido') . "\n";
        
        if ($estadoFinal2['status'] === 'open') {
            echo "   ✅ La factura se abrió correctamente con el formato mínimo.\n";
        } else {
            echo "   ❌ La factura NO se abrió con el formato mínimo.\n";
        }
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
 * Función para abrir una factura en Alegra
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
