<?php

// Script simplificado para actualizar el inventario de ANKOFEN en Alegra
// Cargar el framework Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// ID de Alegra del producto ANKOFEN (ya lo conocemos de la respuesta anterior)
$idProductoAlegra = 67; // ID de ANKOFEN
$cantidadInventario = isset($argv[1]) ? intval($argv[1]) : 100; // Por defecto 100 unidades

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

// Obtener información actual del producto
echo "Obteniendo información actual del producto ANKOFEN (ID: {$idProductoAlegra})...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.alegra.com/api/v1/items/{$idProductoAlegra}");
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
    $producto = json_decode($response, true);
    echo "Información actual del producto:\n";
    echo "Nombre: " . $producto['name'] . "\n";
    
    if (isset($producto['inventory']) && isset($producto['inventory']['availableQuantity'])) {
        echo "Cantidad disponible actual: " . $producto['inventory']['availableQuantity'] . "\n";
    } else {
        echo "El producto no tiene información de inventario.\n";
    }
    
    // Preparar datos para actualizar inventario
    $datosMensaje = [
        'date' => date('Y-m-d'), // Fecha actual
        'quantity' => $cantidadInventario, // Cantidad a agregar/establecer
        'type' => 'initial', // Tipo initial establece el inventario en la cantidad especificada
        'warehouse' => [
            'id' => 1 // ID del almacén principal
        ]
    ];
    
    echo "Actualizando inventario a {$cantidadInventario} unidades...\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.alegra.com/api/v1/items/{$idProductoAlegra}/inventory/messages");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datosMensaje));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($email . ':' . $token)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Respuesta HTTP: {$httpCode}\n";
    
    if ($httpCode >= 200 && $httpCode < 300) {
        $resultadoInventario = json_decode($response, true);
        echo "✅ Inventario actualizado correctamente.\n";
        
        // Verificar inventario actualizado
        echo "Verificando inventario actualizado...\n";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.alegra.com/api/v1/items/{$idProductoAlegra}");
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
            $productoActualizado = json_decode($response, true);
            if (isset($productoActualizado['inventory']) && isset($productoActualizado['inventory']['availableQuantity'])) {
                echo "Cantidad disponible actualizada: " . $productoActualizado['inventory']['availableQuantity'] . "\n";
            }
        }
        
        echo "\nAhora puede intentar abrir y emitir la factura nuevamente.\n";
    } else {
        echo "❌ Error al actualizar el inventario: HTTP {$httpCode}\n";
        echo "Respuesta: {$response}\n";
    }
} else {
    echo "Error al obtener información del producto: HTTP {$httpCode}\n";
    exit(1);
}
