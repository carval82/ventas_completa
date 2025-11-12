<?php

// Script para habilitar ventas en negativo en un producto de Alegra
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// ID de Alegra del producto a modificar
$idProductoAlegra = isset($argv[1]) ? $argv[1] : 67; // Por defecto, ANKOFEN

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
echo "Obteniendo información del producto (ID: {$idProductoAlegra})...\n";
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
    
    // Ver si tiene configuración de inventario
    if (isset($producto['inventory'])) {
        echo "Unidad: " . ($producto['inventory']['unit'] ?? 'No especificada') . "\n";
        echo "Cantidad disponible: " . ($producto['inventory']['availableQuantity'] ?? 'No especificada') . "\n";
        echo "Costo unitario: " . ($producto['inventory']['unitCost'] ?? 'No especificado') . "\n";
        echo "Ventas en negativo: " . (isset($producto['inventory']['negativeSale']) && $producto['inventory']['negativeSale'] ? 'Permitidas' : 'No permitidas') . "\n";
    } else {
        echo "El producto no tiene configuración de inventario.\n";
    }
    
    // Preparar datos para actualizar el producto
    // Mantenemos los datos existentes y solo modificamos la propiedad negativeSale
    $datosProducto = [
        'name' => $producto['name'],
        'description' => $producto['description'] ?? '',
        'reference' => $producto['reference'] ?? '',
        'price' => $producto['price'] ?? 0,
    ];
    
    // Configurar inventario si existe
    if (isset($producto['inventory'])) {
        $datosProducto['inventory'] = [
            'unit' => $producto['inventory']['unit'] ?? 'unit',
            'negativeSale' => true, // Forzar ventas en negativo
        ];
        
        // Mantener costo unitario si existe
        if (isset($producto['inventory']['unitCost'])) {
            $datosProducto['inventory']['unitCost'] = $producto['inventory']['unitCost'];
        }
    }
    
    echo "Actualizando producto para permitir ventas en negativo...\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.alegra.com/api/v1/items/{$idProductoAlegra}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datosProducto));
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
        $productoActualizado = json_decode($response, true);
        echo "✅ Producto actualizado correctamente.\n";
        
        // Verificar la configuración actualizada
        if (isset($productoActualizado['inventory']) && isset($productoActualizado['inventory']['negativeSale'])) {
            echo "Ventas en negativo: " . ($productoActualizado['inventory']['negativeSale'] ? 'Permitidas' : 'No permitidas') . "\n";
        }
        
        echo "\nAhora intente abrir y emitir la factura nuevamente.\n";
    } else {
        echo "❌ Error al actualizar el producto: HTTP {$httpCode}\n";
        echo "Respuesta: {$response}\n";
    }
} else {
    echo "Error al obtener información del producto: HTTP {$httpCode}\n";
    exit(1);
}
