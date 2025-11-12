<?php

// Importar las clases necesarias
require_once __DIR__ . '/../vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Producto;
use App\Models\Empresa;
use Illuminate\Support\Facades\Log;

echo "=== SINCRONIZACIÓN DE PRODUCTO PENDIENTE CON ALEGRA ===\n\n";

// Obtener credenciales de Alegra
$empresa = Empresa::first();

if (!$empresa || empty($empresa->alegra_email) || empty($empresa->alegra_token)) {
    echo "❌ No se encontraron credenciales de Alegra en la empresa.\n";
    exit(1);
}

$email = $empresa->alegra_email;
$token = $empresa->alegra_token;

echo "Credenciales de Alegra obtenidas correctamente.\n\n";

// Buscar el producto pendiente de sincronización
$producto = Producto::whereNull('id_alegra')->orWhere('id_alegra', '')->first();

if (!$producto) {
    echo "✅ Todos los productos ya están sincronizados con Alegra.\n";
    exit(0);
}

echo "Producto pendiente encontrado: " . $producto->nombre . " (ID: " . $producto->id . ")\n";
echo "Precio de venta: " . $producto->precio_venta . "\n";
echo "Precio de compra: " . $producto->precio_compra . "\n";
echo "Stock: " . $producto->stock . "\n\n";

// Preparar datos del producto para Alegra
$datos = [
    'name' => $producto->nombre,
    'reference' => $producto->id,
    'description' => $producto->descripcion ?: $producto->nombre,
    'price' => (float) $producto->precio_venta,
    'cost' => (float) $producto->precio_compra,
    'inventory' => [
        'unit' => $producto->unidad_medida ?: 'unidad',
        'initialQuantity' => (float) $producto->stock,
        'available' => true
    ]
];

echo "Datos a enviar a Alegra:\n";
echo json_encode($datos, JSON_PRETTY_PRINT) . "\n\n";

// Hacer la petición a Alegra
echo "Enviando producto a Alegra...\n";

// Configurar cURL
$ch = curl_init();
$url = 'https://api.alegra.com/api/v1/items';

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));
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

echo "Código de respuesta HTTP: " . $httpCode . "\n";

if ($error) {
    echo "Error cURL: " . $error . "\n";
}

if ($httpCode >= 200 && $httpCode < 300) {
    $data = json_decode($response, true);
    
    if (isset($data['id'])) {
        echo "✅ Producto creado exitosamente en Alegra.\n";
        echo "ID Alegra asignado: " . $data['id'] . "\n\n";
        
        // Actualizar el ID de Alegra en el producto local
        $producto->id_alegra = $data['id'];
        $producto->save();
        
        echo "✅ Producto actualizado con ID de Alegra: " . $producto->id_alegra . "\n";
        
        // Verificar que el formato para enviar a Alegra en una factura es correcto
        echo "\nFormato correcto para incluir este producto en una factura:\n";
        echo "{ \"items\": [ { \"id\": " . $producto->id_alegra . ", \"price\": " . $producto->precio_venta . ", \"quantity\": 1, \"taxRate\": 19 } ] }\n";
    } else {
        echo "❌ Error: La respuesta no contiene un ID de Alegra.\n";
        echo "Respuesta completa: " . $response . "\n";
    }
} else {
    echo "❌ Error al crear producto en Alegra.\n";
    echo "Respuesta: " . $response . "\n";
}

// Verificar estado actual de sincronización
$totalProductos = Producto::count();
$productosSincronizados = Producto::whereNotNull('id_alegra')->where('id_alegra', '!=', '')->count();
$porcentaje = round(($productosSincronizados / $totalProductos) * 100, 2);

echo "\nEstado actual: {$productosSincronizados}/{$totalProductos} productos sincronizados ({$porcentaje}%)\n";
echo "\nSincronización completada.\n";
