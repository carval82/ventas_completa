<?php
/**
 * Script mínimo para abrir facturas en Alegra
 * Versión ultra-compacta para resultados claros
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Empresa;

// Verificar argumentos
if ($argc < 2) {
    echo "Uso: php abrir_factura_minimo.php <id_factura>\n";
    exit(1);
}

$facturaId = $argv[1];
$empresa = Empresa::first();
$email = $empresa->alegra_email;
$token = $empresa->alegra_token;

// Función para consultar factura
function getFactura($id, $email, $token) {
    $ch = curl_init("https://api.alegra.com/api/v1/invoices/{$id}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode($email . ':' . $token)
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'data' => json_decode($res, true)];
}

// Función para modificar factura
function putFactura($id, $email, $token, $data) {
    $ch = curl_init("https://api.alegra.com/api/v1/invoices/{$id}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($email . ':' . $token)
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'data' => json_decode($res, true)];
}

// Función para abrir factura con endpoint específico
function openFactura($id, $email, $token) {
    $ch = curl_init("https://api.alegra.com/api/v1/invoices/{$id}/open");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['paymentForm' => 'CASH', 'paymentMethod' => 'CASH']));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($email . ':' . $token)
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'data' => json_decode($res, true)];
}

// 1. Verificar estado inicial
$inicial = getFactura($facturaId, $email, $token);
echo "Estado inicial: " . ($inicial['data']['status'] ?? 'error') . "\n";

// 2. Intentar método 1: Modificar factura directamente
echo "Método 1 (PUT /invoices/{id}): ";
$res1 = putFactura($facturaId, $email, $token, [
    'status' => 'open',
    'paymentForm' => 'CASH',
    'paymentMethod' => 'CASH'
]);
echo "HTTP " . $res1['code'] . "\n";
sleep(2);

// Verificar estado
$check1 = getFactura($facturaId, $email, $token);
echo "Estado después de método 1: " . ($check1['data']['status'] ?? 'error') . "\n";

// Si no funcionó, intentar método 2
if ($check1['data']['status'] !== 'open') {
    echo "Método 2 (PUT /invoices/{id}/open): ";
    $res2 = openFactura($facturaId, $email, $token);
    echo "HTTP " . $res2['code'] . "\n";
    sleep(2);
    
    // Verificar estado final
    $check2 = getFactura($facturaId, $email, $token);
    echo "Estado después de método 2: " . ($check2['data']['status'] ?? 'error') . "\n";
    
    echo ($check2['data']['status'] === 'open' ? "✅ Éxito con método 2" : "❌ Ambos métodos fallaron") . "\n";
} else {
    echo "✅ Éxito con método 1\n";
}
