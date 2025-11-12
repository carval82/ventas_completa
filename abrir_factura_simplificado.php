<?php
/**
 * Script simplificado para abrir una factura usando solo el campo paymentForm
 * y luego enviarla a la DIAN
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
    echo "Uso: php abrir_factura_simplificado.php ID_FACTURA_ALEGRA\n";
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
echo "  PROCESO SIMPLIFICADO PARA ABRIR Y ENVIAR FACTURA A DIAN\n";
echo "=================================================================\n";
echo "ID de Factura: $idFactura\n";
echo "Credenciales: $email\n";
echo "-----------------------------------------------------------------\n";

// Paso 1: Verificar el estado actual de la factura
echo "\n>>> PASO 1: Verificando estado actual de la factura\n";

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
        
        if ($factura['status'] === 'open') {
            echo "La factura ya está abierta. Procediendo a enviarla a DIAN...\n";
            $debeAbrirse = false;
        } else {
            echo "Solo se pueden enviar a DIAN facturas en estado 'open'. No se puede continuar.\n";
            exit(1);
        }
    } else {
        $debeAbrirse = true;
    }
} else {
    echo "❌ Error al obtener la factura: HTTP " . $httpCode . "\n";
    echo "Respuesta: " . $response . "\n";
    exit(1);
}

// Paso 2: Abrir la factura si está en borrador
if ($debeAbrirse) {
    echo "\n>>> PASO 2: Abriendo la factura con formato mínimo (solo paymentForm)\n";
    
    $ch = curl_init();
    $url = "https://api.alegra.com/api/v1/invoices/{$idFactura}/open";
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['paymentForm' => 'CASH']));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($email . ':' . $token)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Código de respuesta HTTP: " . $httpCode . "\n";
    
    if ($httpCode >= 200 && $httpCode < 300) {
        echo "✅ Factura abierta exitosamente\n";
        $facturaAbierta = json_decode($response, true);
        echo "Nuevo estado: " . $facturaAbierta['status'] . "\n";
    } else {
        echo "❌ Error al abrir la factura: HTTP " . $httpCode . "\n";
        echo "Respuesta: " . $response . "\n";
        
        // Intenta mostrar un mensaje de error más específico
        $errorData = json_decode($response, true);
        if ($errorData && isset($errorData['message'])) {
            echo "Mensaje de error: " . $errorData['message'] . "\n";
        }
        
        exit(1);
    }
}

// Paso 3: Enviar la factura a DIAN
echo "\n>>> PASO 3: Enviando la factura a DIAN\n";

$ch = curl_init();
$url = "https://api.alegra.com/api/v1/invoices/{$idFactura}/stamp";
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'generateStamp' => true,
    'generateQrCode' => true
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Basic ' . base64_encode($email . ':' . $token)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Código de respuesta HTTP: " . $httpCode . "\n";

if ($httpCode >= 200 && $httpCode < 300) {
    echo "✅ Factura enviada exitosamente a DIAN\n";
    $resultadoDian = json_decode($response, true);
    echo "Estado DIAN: " . ($resultadoDian['status'] ?? 'No disponible') . "\n";
    echo "CUFE: " . ($resultadoDian['cufe'] ?? 'No disponible') . "\n";
    
    // Opcional: Actualizar la base de datos si tenemos ID de venta
    $ventaId = isset($argv[2]) ? $argv[2] : null;
    if ($ventaId) {
        try {
            $venta = \App\Models\Venta::find($ventaId);
            if ($venta) {
                $venta->update([
                    'estado_dian' => $resultadoDian['status'] ?? 'Enviado',
                    'cufe' => $resultadoDian['cufe'] ?? null,
                    'qr_code' => $resultadoDian['qrCode'] ?? null,
                ]);
                echo "✅ Base de datos actualizada para la venta ID: {$ventaId}\n";
            }
        } catch (\Exception $e) {
            echo "⚠️ No se pudo actualizar la base de datos: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "❌ Error al enviar a DIAN: HTTP " . $httpCode . "\n";
    echo "Respuesta: " . $response . "\n";
    
    // Intenta mostrar un mensaje de error más específico
    $errorData = json_decode($response, true);
    if ($errorData && isset($errorData['message'])) {
        echo "Mensaje de error: " . $errorData['message'] . "\n";
    }
}

echo "\n=================================================================\n";
echo "        PROCESO FINALIZADO - SOLUCIÓN SIMPLIFICADA\n";
echo "=================================================================\n";
echo "\nNOTA IMPORTANTE:\n";
echo "Para solucionar el problema en el código principal, debe modificar el método\n";
echo "abrirFacturaDirecto en AlegraService.php para usar solo el campo 'paymentForm':\n\n";
echo "\$datos = json_encode(['paymentForm' => 'CASH']);\n\n";
echo "No incluya paymentMethod ni otros campos en la solicitud.\n";
echo "=================================================================\n";
