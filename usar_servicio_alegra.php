<?php

// Script para usar el servicio AlegraService directamente
// Cargar el framework Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Importar las clases necesarias
use App\Services\AlegraService;
use Illuminate\Support\Facades\Log;

// ID de la factura a procesar
$idFactura = isset($argv[1]) ? $argv[1] : null;

if (!$idFactura) {
    echo "Error: Debe proporcionar el ID de la factura como argumento\n";
    echo "Uso: php usar_servicio_alegra.php ID_FACTURA\n";
    exit(1);
}

// Crear una instancia del servicio Alegra
$alegraService = app(AlegraService::class);

// Verificar estado actual de la factura
echo "Verificando estado actual de la factura {$idFactura}...\n";
$ch = curl_init();
$credenciales = $alegraService->obtenerCredencialesAlegra();
if (!$credenciales['success']) {
    echo "Error al obtener credenciales: " . $credenciales['message'] . "\n";
    exit(1);
}

$email = $credenciales['email'];
$token = $credenciales['token'];

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

// Si la factura está en borrador, intentar abrirla usando el servicio
if ($factura['status'] === 'draft') {
    echo "La factura está en estado borrador, intentando abrirla usando AlegraService...\n";
    
    $resultado = $alegraService->abrirFacturaDirecto($idFactura);
    
    echo "Resultado de abrirFacturaDirecto: " . json_encode($resultado) . "\n";
    
    // Verificar si se abrió correctamente
    echo "Verificando estado después de intentar abrir...\n";
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
        echo "Estado después de intentar abrir: " . $factura['status'] . "\n";
        
        if ($factura['status'] !== 'open') {
            echo "❌ La factura no cambió a estado abierto. No se puede enviar a la DIAN.\n";
            exit(1);
        }
    } else {
        echo "Error al verificar estado: HTTP {$httpCode}\n";
        exit(1);
    }
}

// Si llegamos aquí, la factura está abierta o se abrió correctamente
echo "Enviando factura a la DIAN usando AlegraService...\n";

$resultado = $alegraService->enviarFacturaADian($idFactura);

echo "Resultado de enviarFacturaADian: " . json_encode($resultado) . "\n";

if (isset($resultado['success']) && $resultado['success']) {
    echo "✅ La factura se envió correctamente a la DIAN.\n";
    
    // Verificar si tiene CUFE
    if (isset($resultado['data']['stamp']) && isset($resultado['data']['stamp']['cufe'])) {
        echo "CUFE: " . $resultado['data']['stamp']['cufe'] . "\n";
    }
} else {
    echo "❌ Error al enviar la factura a la DIAN.\n";
    
    if (isset($resultado['message'])) {
        echo "Mensaje de error: " . $resultado['message'] . "\n";
    }
}
