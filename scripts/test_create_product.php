<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AlegraService;
use App\Models\Producto;
use Illuminate\Support\Facades\Log;

// Configurar el log para mostrar en consola
Log::info('Iniciando prueba de creación de producto en Alegra');

try {
    // Crear un producto de prueba
    $producto = new Producto([
        'codigo' => 'TEST' . time(),
        'nombre' => 'Producto de Prueba ' . date('Y-m-d H:i:s'),
        'descripcion' => 'Descripción del producto de prueba',
        'precio_compra' => 10000,
        'precio_venta' => 15000,
        'stock' => 10,
        'stock_minimo' => 5,
        'estado' => true
    ]);

    echo "[" . date('Y-m-d H:i:s') . "] Producto de prueba creado: " . json_encode($producto->toArray(), JSON_PRETTY_PRINT) . "\n";

    // Obtener el servicio de Alegra
    $alegraService = app(AlegraService::class);

    // Crear el producto en Alegra
    echo "[" . date('Y-m-d H:i:s') . "] Creando producto en Alegra...\n";
    $result = $alegraService->crearProductoAlegra($producto);

    if ($result['success']) {
        echo "[" . date('Y-m-d H:i:s') . "] Producto creado exitosamente en Alegra: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
        
        // Asignar el ID de Alegra al producto
        $producto->id_alegra = $result['data']['id'];
        echo "[" . date('Y-m-d H:i:s') . "] ID de Alegra asignado al producto: " . $producto->id_alegra . "\n";
        
        // En un caso real, aquí se guardaría el producto en la base de datos
        // $producto->save();
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] Error al crear producto en Alegra: " . ($result['error'] ?? 'Error desconocido') . "\n";
    }

} catch (\Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Excepción: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "[" . date('Y-m-d H:i:s') . "] Prueba finalizada\n";
