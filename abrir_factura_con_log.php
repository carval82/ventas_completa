<?php
/**
 * Script para abrir facturas en Alegra con registro en archivo de log
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Empresa;

// Configurar archivo de log
$logFile = __DIR__ . '/abrir_factura_log.txt';
file_put_contents($logFile, "=== Abrir Factura " . date('Y-m-d H:i:s') . " ===\n\n");

// Función para escribir en el log
function log_write($message) {
    global $logFile;
    file_put_contents($logFile, $message . "\n", FILE_APPEND);
    echo $message . "\n";
}

// Verificar argumentos
if ($argc < 2) {
    log_write("Uso: php abrir_factura_con_log.php <id_factura>");
    exit(1);
}

$facturaId = $argv[1];
log_write("Procesando factura ID: {$facturaId}");

// Obtener credenciales
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
    return ['code' => $code, 'data' => json_decode($res, true), 'response' => $res];
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
    return ['code' => $code, 'data' => json_decode($res, true), 'response' => $res];
}

// 1. Verificar estado inicial
$inicial = getFactura($facturaId, $email, $token);
log_write("Estado inicial: " . ($inicial['data']['status'] ?? 'error'));
log_write("Número: " . ($inicial['data']['numberTemplate']['fullNumber'] ?? 'N/A'));

// Si ya está abierta, no hacer nada
if ($inicial['data']['status'] === 'open') {
    log_write("La factura ya está abierta. No es necesario cambiarla.");
    exit(0);
}

// 2. Intentar método 1: Modificar factura directamente
log_write("\nMétodo 1 (PUT /invoices/{id}):");
$payload1 = [
    'status' => 'open',
    'paymentForm' => 'CASH',
    'paymentMethod' => 'CASH'
];
log_write("Payload: " . json_encode($payload1));

$res1 = putFactura($facturaId, $email, $token, $payload1);
log_write("HTTP " . $res1['code']);
log_write("Respuesta: " . substr($res1['response'], 0, 100) . (strlen($res1['response']) > 100 ? "..." : ""));
sleep(2);

// Verificar estado
$check1 = getFactura($facturaId, $email, $token);
log_write("Estado después de método 1: " . ($check1['data']['status'] ?? 'error'));

// Si no funcionó, intentar método 2
if ($check1['data']['status'] !== 'open') {
    log_write("\nMétodo 2 (PUT /invoices/{id}/open):");
    $payload2 = ['paymentForm' => 'CASH', 'paymentMethod' => 'CASH'];
    log_write("Payload: " . json_encode($payload2));
    
    $res2 = openFactura($facturaId, $email, $token);
    log_write("HTTP " . $res2['code']);
    log_write("Respuesta: " . substr($res2['response'], 0, 100) . (strlen($res2['response']) > 100 ? "..." : ""));
    sleep(2);
    
    // Verificar estado final
    $check2 = getFactura($facturaId, $email, $token);
    log_write("Estado después de método 2: " . ($check2['data']['status'] ?? 'error'));
    
    if ($check2['data']['status'] === 'open') {
        log_write("\n✅ Éxito con método 2 (endpoint específico /open)");
    } else {
        log_write("\n❌ Ambos métodos fallaron. La factura sigue en estado: " . $check2['data']['status']);
        
        // Intentar método 3: Actualizar solo los campos de pago
        log_write("\nMétodo 3 (Actualizar solo campos de pago):");
        $payload3 = [
            'paymentForm' => 'CASH',
            'paymentMethod' => 'CASH'
        ];
        log_write("Payload: " . json_encode($payload3));
        
        $res3 = putFactura($facturaId, $email, $token, $payload3);
        log_write("HTTP " . $res3['code']);
        log_write("Respuesta: " . substr($res3['response'], 0, 100) . (strlen($res3['response']) > 100 ? "..." : ""));
        sleep(2);
        
        // Verificar estado final
        $check3 = getFactura($facturaId, $email, $token);
        log_write("Estado después de método 3: " . ($check3['data']['status'] ?? 'error'));
        
        if ($check3['data']['status'] === 'open') {
            log_write("\n✅ Éxito con método 3 (actualizar solo campos de pago)");
        } else {
            log_write("\n❌ Todos los métodos fallaron. Posibles causas:");
            log_write("   - Permisos insuficientes en la cuenta de Alegra");
            log_write("   - La factura no cumple requisitos para ser abierta");
            log_write("   - Problemas con la API de Alegra");
        }
    }
} else {
    log_write("\n✅ Éxito con método 1 (actualizar estado directamente)");
}

log_write("\nProceso finalizado. Resultados guardados en: {$logFile}");
