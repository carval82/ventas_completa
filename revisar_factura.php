<?php

// Script para revisar en detalle una factura en Alegra
// Cargar el framework Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// ID de la factura a revisar
$idFactura = isset($argv[1]) ? $argv[1] : null;

if (!$idFactura) {
    echo "Error: Debe proporcionar el ID de la factura como argumento\n";
    echo "Uso: php revisar_factura.php ID_FACTURA\n";
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

// Obtener detalles completos de la factura
echo "Obteniendo detalles completos de la factura {$idFactura}...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.alegra.com/api/v1/invoices/{$idFactura}?expand=items,client,payments,attachments,observations,metadata");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Basic ' . base64_encode($email . ':' . $token)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    $factura = json_decode($response, true);
    
    // Mostrar información detallada de la factura
    echo "\n=== INFORMACIÓN DETALLADA DE LA FACTURA ===\n";
    echo "ID: " . $factura['id'] . "\n";
    echo "Número: " . ($factura['numberTemplate']['fullNumber'] ?? 'No disponible') . "\n";
    echo "Estado: " . $factura['status'] . "\n";
    echo "Fecha: " . $factura['date'] . "\n";
    echo "Fecha de vencimiento: " . $factura['dueDate'] . "\n";
    echo "Cliente: " . $factura['client']['name'] . " (ID: " . $factura['client']['id'] . ")\n";
    echo "Total: " . number_format($factura['total'], 2) . "\n";
    
    // Verificar si la plantilla de numeración es electrónica y está activa
    echo "\n=== PLANTILLA DE NUMERACIÓN ===\n";
    echo "ID: " . $factura['numberTemplate']['id'] . "\n";
    echo "Prefijo: " . $factura['numberTemplate']['prefix'] . "\n";
    echo "Número: " . $factura['numberTemplate']['number'] . "\n";
    echo "Número completo: " . $factura['numberTemplate']['fullNumber'] . "\n";
    echo "Es electrónica: " . ($factura['numberTemplate']['isElectronic'] ? 'Sí' : 'No') . "\n";
    
    // Verificar forma de pago y método de pago
    echo "\n=== FORMA DE PAGO Y MÉTODO DE PAGO ===\n";
    echo "Forma de pago: " . $factura['paymentForm'] . "\n";
    echo "Método de pago: " . $factura['paymentMethod'] . "\n";
    
    // Verificar si tiene pagos asociados
    echo "\n=== PAGOS ASOCIADOS ===\n";
    if (isset($factura['payments']) && !empty($factura['payments'])) {
        foreach ($factura['payments'] as $index => $pago) {
            echo "Pago #" . ($index + 1) . ":\n";
            echo "  ID: " . $pago['id'] . "\n";
            echo "  Fecha: " . $pago['date'] . "\n";
            echo "  Monto: " . number_format($pago['value'], 2) . "\n";
        }
    } else {
        echo "No tiene pagos asociados\n";
    }
    
    // Verificar si tiene CUFE (ya está emitida)
    echo "\n=== INFORMACIÓN DE EMISIÓN ELECTRÓNICA ===\n";
    if (isset($factura['stamp']) && isset($factura['stamp']['cufe'])) {
        echo "CUFE: " . $factura['stamp']['cufe'] . "\n";
        echo "La factura ya está emitida electrónicamente\n";
    } else {
        echo "La factura no está emitida electrónicamente\n";
    }
    
    // Verificar si tiene metadatos
    echo "\n=== METADATOS ===\n";
    if (isset($factura['metadata']) && !empty($factura['metadata'])) {
        foreach ($factura['metadata'] as $metadata) {
            echo $metadata['key'] . ": " . $metadata['value'] . "\n";
        }
    } else {
        echo "No tiene metadatos\n";
    }
    
    // Verificar si tiene observaciones
    echo "\n=== OBSERVACIONES ===\n";
    if (isset($factura['observations']) && !empty($factura['observations'])) {
        echo $factura['observations'] . "\n";
    } else {
        echo "No tiene observaciones\n";
    }
    
    // Verificar si tiene items
    echo "\n=== ITEMS ===\n";
    if (isset($factura['items']) && !empty($factura['items'])) {
        foreach ($factura['items'] as $index => $item) {
            echo "Item #" . ($index + 1) . ": " . $item['name'] . "\n";
            echo "  Cantidad: " . $item['quantity'] . "\n";
            echo "  Precio: " . number_format($item['price'], 2) . "\n";
            echo "  Total: " . number_format($item['total'], 2) . "\n";
        }
    } else {
        echo "No tiene items\n";
    }
    
    // Guardar la respuesta completa en un archivo para análisis
    file_put_contents("factura_{$idFactura}_detalle.json", $response);
    echo "\nSe ha guardado la respuesta completa en el archivo factura_{$idFactura}_detalle.json\n";
    
} else {
    echo "Error al obtener detalles de la factura: HTTP {$httpCode}\n";
    echo "Respuesta: {$response}\n";
}
