<?php

// Importar las clases necesarias
require_once __DIR__ . '/../vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Producto;
use App\Services\AlegraService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Aumentar el tiempo máximo de ejecución
set_time_limit(300);

// Activar la salida de buffer
ob_implicit_flush(true);

echo "=== IMPORTACIÓN DE UN PRODUCTO DESDE ALEGRA ===\n\n";

// Crear una instancia del servicio Alegra
$alegraService = new AlegraService();

// Probar la conexión
echo "Probando conexión con Alegra...\n";
$resultado = $alegraService->probarConexion();

if ($resultado['success']) {
    echo "Conexión exitosa con Alegra.\n\n";
} else {
    echo "Error al conectar con Alegra: " . ($resultado['message'] ?? 'Error desconocido') . "\n";
    exit(1);
}

// Obtener un producto específico de Alegra (ID 1381 según la salida anterior)
$idProductoAlegra = 1381;
echo "Obteniendo producto con ID {$idProductoAlegra} de Alegra...\n";

try {
    // Usar el método público obtenerProductos con un filtro para obtener solo el producto específico
    $resultado = $alegraService->obtenerProductos(['id' => $idProductoAlegra]);
    
    if (!$resultado['success']) {
        echo "Error al obtener producto: " . ($resultado['message'] ?? 'Error desconocido') . "\n";
        exit(1);
    }
    
    // La respuesta es un array de productos, pero deberíamos tener solo uno
    $productos = $resultado['data'] ?? [];
    
    if (empty($productos)) {
        echo "No se encontró el producto con ID {$idProductoAlegra} en Alegra.\n";
        exit(1);
    }
    
    // Tomar el primer producto (debería ser el único)
    $productoAlegra = $productos[0] ?? null;
    
    if (!$productoAlegra) {
        echo "Error al obtener datos del producto.\n";
        exit(1);
    }
    
    // Mostrar información del producto obtenido
    
    echo "Producto obtenido correctamente:\n";
    echo "ID: " . ($productoAlegra['id'] ?? 'N/A') . "\n";
    echo "Nombre: " . ($productoAlegra['name'] ?? 'N/A') . "\n";
    echo "Referencia: " . ($productoAlegra['reference'] ?? 'N/A') . "\n";
    echo "Precio: " . ($productoAlegra['price'] ?? 'N/A') . "\n";
    
    // Verificar si el producto ya existe en la base de datos local
    $productoLocal = Producto::where('id_alegra', $productoAlegra['id'])->first();
    
    if ($productoLocal) {
        echo "\nEl producto ya existe en la base de datos local (ID: {$productoLocal->id}).\n";
        echo "Actualizando información...\n";
        
        $productoLocal->nombre = $productoAlegra['name'];
        $productoLocal->codigo = $productoAlegra['reference'] ?? '';
        $productoLocal->precio_venta = $productoAlegra['price'] ?? 0;
        $productoLocal->precio_compra = $productoAlegra['price'] ?? 0;
        
        if (isset($productoAlegra['inventory']) && isset($productoAlegra['inventory']['available'])) {
            $productoLocal->stock = $productoAlegra['inventory']['available'];
        }
        
        $productoLocal->save();
        
        echo "Producto actualizado correctamente.\n";
    } else {
        echo "\nEl producto no existe en la base de datos local.\n";
        echo "Creando nuevo producto...\n";
        
        $nuevoProducto = new Producto();
        $nuevoProducto->nombre = $productoAlegra['name'];
        $nuevoProducto->codigo = $productoAlegra['reference'] ?? '';
        $nuevoProducto->precio_venta = $productoAlegra['price'] ?? 0;
        $nuevoProducto->precio_compra = $productoAlegra['price'] ?? 0;
        $nuevoProducto->id_alegra = $productoAlegra['id'];
        
        if (isset($productoAlegra['inventory']) && isset($productoAlegra['inventory']['available'])) {
            $nuevoProducto->stock = $productoAlegra['inventory']['available'];
        } else {
            $nuevoProducto->stock = 0;
        }
        
        $nuevoProducto->estado = true;
        
        // Añadir valores predeterminados para campos obligatorios
        $nuevoProducto->stock_minimo = 0;
        $nuevoProducto->descripcion = $productoAlegra['description'] ?? '';
        
        $nuevoProducto->save();
        
        echo "Producto creado correctamente (ID local: {$nuevoProducto->id}).\n";
    }
    
    echo "\nProceso completado con éxito.\n";
    
} catch (\Exception $e) {
    echo "Error durante la importación del producto: " . $e->getMessage() . "\n";
    echo "Traza: " . $e->getTraceAsString() . "\n";
}
