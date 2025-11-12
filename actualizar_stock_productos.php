<?php

// Script para actualizar el inventario de productos en Alegra
// Cargar el framework Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Recibir parámetros: nombre o id_alegra, y cantidad
$nombreBusqueda = isset($argv[1]) ? $argv[1] : null;
$cantidadInventario = isset($argv[2]) ? intval($argv[2]) : 100; // Por defecto 100 unidades

if (!$nombreBusqueda) {
    echo "Error: Debe proporcionar el nombre del producto a actualizar\n";
    echo "Uso: php actualizar_stock_productos.php NOMBRE_PRODUCTO [CANTIDAD]\n";
    exit(1);
}

// Obtener credenciales de Alegra
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

// Función para hacer peticiones a la API de Alegra
function alegraApi($method, $endpoint, $data = null, $email, $token) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.alegra.com/api/v1/" . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($email . ':' . $token)
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } else if ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => $response,
        'data' => json_decode($response, true)
    ];
}

// 1. Buscar todos los productos en Alegra
echo "Buscando productos en Alegra...\n";
$response = alegraApi('GET', 'items', null, $email, $token);

if ($response['code'] >= 200 && $response['code'] < 300) {
    $productos = $response['data'];
    echo "Se encontraron " . count($productos) . " productos en Alegra.\n";
    
    // Buscar producto por nombre (parcial o completo)
    $productosEncontrados = [];
    foreach ($productos as $producto) {
        if (stripos($producto['name'], $nombreBusqueda) !== false) {
            $productosEncontrados[] = $producto;
        }
    }
    
    if (empty($productosEncontrados)) {
        echo "❌ No se encontró ningún producto que coincida con: '{$nombreBusqueda}'\n";
        
        // Mostrar algunos productos para referencia
        echo "\nProductos disponibles en Alegra (primeros 10):\n";
        $count = 0;
        foreach ($productos as $producto) {
            echo "- " . $producto['name'] . " (ID: " . $producto['id'] . ")\n";
            $count++;
            if ($count >= 10) break;
        }
        exit(1);
    }
    
    echo "Se encontraron " . count($productosEncontrados) . " productos que coinciden con '{$nombreBusqueda}':\n";
    
    // Si hay más de uno, mostrar listado y pedir selección
    foreach ($productosEncontrados as $index => $producto) {
        $stock = isset($producto['inventory']['availableQuantity']) ? $producto['inventory']['availableQuantity'] : 'N/A';
        echo ($index + 1) . ". {$producto['name']} (ID: {$producto['id']}, Stock actual: {$stock})\n";
    }
    
    // Si solo hay uno, seleccionar automáticamente
    $productoSeleccionado = null;
    if (count($productosEncontrados) === 1) {
        $productoSeleccionado = $productosEncontrados[0];
        echo "Seleccionando automáticamente el único producto encontrado.\n";
    } else {
        // Preguntar cuál actualizar (si hay terminal interactiva)
        echo "\nIngrese el número del producto a actualizar (1-" . count($productosEncontrados) . "): ";
        $seleccion = intval(trim(fgets(STDIN)));
        
        if ($seleccion > 0 && $seleccion <= count($productosEncontrados)) {
            $productoSeleccionado = $productosEncontrados[$seleccion - 1];
        } else {
            echo "❌ Selección inválida.\n";
            exit(1);
        }
    }
    
    // 2. Actualizar el inventario del producto seleccionado
    echo "Actualizando inventario del producto: {$productoSeleccionado['name']} (ID: {$productoSeleccionado['id']})\n";
    
    // Preparar datos para actualizar inventario
    $datosMensaje = [
        'date' => date('Y-m-d'), // Fecha actual
        'quantity' => $cantidadInventario, // Cantidad a establecer
        'type' => 'initial', // Tipo initial establece el inventario en la cantidad especificada
        'warehouse' => [
            'id' => 1 // ID del almacén principal
        ]
    ];
    
    echo "Estableciendo inventario a {$cantidadInventario} unidades...\n";
    $response = alegraApi('POST', "items/{$productoSeleccionado['id']}/inventory/messages", $datosMensaje, $email, $token);
    
    if ($response['code'] >= 200 && $response['code'] < 300) {
        echo "✅ Inventario actualizado correctamente.\n";
        
        // Verificar inventario actualizado
        echo "Verificando inventario actualizado...\n";
        $response = alegraApi('GET', "items/{$productoSeleccionado['id']}", null, $email, $token);
        
        if ($response['code'] >= 200 && $response['code'] < 300) {
            $productoActualizado = $response['data'];
            if (isset($productoActualizado['inventory']) && isset($productoActualizado['inventory']['availableQuantity'])) {
                echo "Cantidad disponible actualizada: " . $productoActualizado['inventory']['availableQuantity'] . "\n";
            }
        }
        
        echo "\nAhora puede intentar abrir y emitir la factura nuevamente.\n";
    } else {
        echo "❌ Error al actualizar el inventario: HTTP " . $response['code'] . "\n";
        echo "Respuesta: " . $response['body'] . "\n";
    }
} else {
    echo "❌ Error al consultar productos en Alegra: HTTP " . $response['code'] . "\n";
    exit(1);
}
