<?php
/**
 * Script solución final para abrir facturas en Alegra
 * Este script utiliza un enfoque directo modificando la factura completa
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Empresa;

// Verificar argumentos
if (!isset($argv[1])) {
    echo "Error: Debe proporcionar el ID de la factura como argumento.\n";
    echo "Uso: php abrirFactura_solucion_final.php <id_factura>\n";
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

// 1. Consultar estado inicial y datos completos de la factura
echo "1. Consultando estado inicial y datos de la factura...\n";
$facturaInicial = consultarFactura($facturaId, $email, $token);

if (!isset($facturaInicial['id'])) {
    echo "   Error: No se pudo obtener la factura.\n";
    exit(1);
}

echo "   Estado inicial: " . ($facturaInicial['status'] ?? 'desconocido') . "\n";
echo "   Número: " . ($facturaInicial['numberTemplate']['fullNumber'] ?? 'N/A') . "\n\n";

// Si la factura ya está abierta, no hacer nada
if (isset($facturaInicial['status']) && $facturaInicial['status'] === 'open') {
    echo "La factura ya está en estado 'open'. No es necesario cambiarla.\n";
    exit(0);
}

// 2. Intentar abrir la factura usando el endpoint específico
echo "2. Intentando abrir la factura usando el endpoint específico...\n";

// Payload para el endpoint /open
$payloadOpen = [
    "paymentForm" => "CASH",
    "paymentMethod" => "CASH"
];

echo "   Payload: " . json_encode($payloadOpen) . "\n";

$resultadoOpen = abrirFacturaEndpoint($facturaId, $email, $token, $payloadOpen);
echo "   Resultado: " . ($resultadoOpen['success'] ? 'Éxito' : 'Error') . " (HTTP " . $resultadoOpen['http_code'] . ")\n";

// Esperar un momento
echo "   Esperando 3 segundos...\n";
sleep(3);

// 3. Verificar estado después del primer intento
echo "3. Verificando estado después del primer intento...\n";
$estadoIntermedio = consultarFactura($facturaId, $email, $token);
echo "   Estado intermedio: " . ($estadoIntermedio['status'] ?? 'desconocido') . "\n\n";

// Si el primer intento no funcionó, intentar modificando la factura completa
if (!isset($estadoIntermedio['status']) || $estadoIntermedio['status'] !== 'open') {
    echo "4. El primer intento no funcionó. Intentando modificar la factura completa...\n";
    
    // Crear un payload basado en la factura original pero con estado 'open'
    $facturaModificada = $facturaInicial;
    $facturaModificada['status'] = 'open';
    
    // Asegurarse de que tenga los campos de pago requeridos
    $facturaModificada['paymentForm'] = 'CASH';
    $facturaModificada['paymentMethod'] = 'CASH';
    
    // Eliminar campos que no se pueden modificar
    unset($facturaModificada['id']);
    unset($facturaModificada['numberTemplate']);
    unset($facturaModificada['date']);
    unset($facturaModificada['dueDate']);
    
    echo "   Intentando actualizar la factura completa...\n";
    
    $resultadoModificar = modificarFactura($facturaId, $email, $token, $facturaModificada);
    echo "   Resultado: " . ($resultadoModificar['success'] ? 'Éxito' : 'Error') . " (HTTP " . $resultadoModificar['http_code'] . ")\n";
    
    // Esperar un momento
    echo "   Esperando 3 segundos...\n";
    sleep(3);
    
    // 5. Verificar estado final
    echo "5. Verificando estado final...\n";
    $estadoFinal = consultarFactura($facturaId, $email, $token);
    echo "   Estado final: " . ($estadoFinal['status'] ?? 'desconocido') . "\n\n";
    
    // 6. Mostrar resultado final
    echo "6. Resultado final:\n";
    if (isset($estadoFinal['status']) && $estadoFinal['status'] === 'open') {
        echo "   ✅ La factura se abrió correctamente mediante la modificación completa.\n";
    } else {
        echo "   ❌ La factura NO se abrió. Sigue en estado: " . ($estadoFinal['status'] ?? 'desconocido') . "\n";
        
        // Último intento: usar el endpoint directo con un payload más específico
        echo "\n7. Realizando último intento con payload específico...\n";
        
        // Payload específico con ID
        $payloadFinal = [
            "id" => intval($facturaId),
            "status" => "open",
            "paymentForm" => "CASH",
            "paymentMethod" => "CASH"
        ];
        
        echo "   Payload: " . json_encode($payloadFinal) . "\n";
        
        $resultadoFinal = abrirFacturaEndpoint($facturaId, $email, $token, $payloadFinal);
        echo "   Resultado: " . ($resultadoFinal['success'] ? 'Éxito' : 'Error') . " (HTTP " . $resultadoFinal['http_code'] . ")\n";
        
        // Esperar un momento
        echo "   Esperando 3 segundos...\n";
        sleep(3);
        
        // Verificar estado
        $estadoUltimo = consultarFactura($facturaId, $email, $token);
        echo "   Estado después del último intento: " . ($estadoUltimo['status'] ?? 'desconocido') . "\n";
        
        if (isset($estadoUltimo['status']) && $estadoUltimo['status'] === 'open') {
            echo "   ✅ La factura se abrió correctamente con el último intento.\n";
        } else {
            echo "   ❌ La factura NO se pudo abrir con ninguno de los métodos intentados.\n";
            echo "   Recomendación: Verificar permisos en Alegra o contactar soporte.\n";
        }
    }
} else {
    echo "4. Resultado final:\n";
    echo "   ✅ La factura se abrió correctamente con el primer intento.\n";
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
 * Función para abrir una factura en Alegra usando el endpoint específico
 */
function abrirFacturaEndpoint($facturaId, $email, $token, $datos) {
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
 * Función para modificar una factura completa en Alegra
 */
function modificarFactura($facturaId, $email, $token, $datos) {
    // Configurar cURL
    $ch = curl_init();
    $url = "https://api.alegra.com/api/v1/invoices/{$facturaId}";
    
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
