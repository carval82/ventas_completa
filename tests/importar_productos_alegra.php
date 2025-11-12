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

// Aumentar el tiempo máximo de ejecución a 5 minutos
set_time_limit(300);

// Aumentar el límite de memoria
ini_set('memory_limit', '512M');

// Crear una instancia del servicio Alegra
$alegraService = new AlegraService();

// Activar la salida de buffer
ob_implicit_flush(true);

// Probar la conexión
echo "Probando conexión con Alegra...\n";
$resultado = $alegraService->probarConexion();

if ($resultado['success']) {
    echo "Conexión exitosa con Alegra.\n";
} else {
    echo "Error al conectar con Alegra: " . ($resultado['message'] ?? 'Error desconocido') . "\n";
    exit(1);
}

// Función para importar productos desde Alegra
function importarProductos($alegraService) {
    echo "\n=== IMPORTACIÓN DE PRODUCTOS DESDE ALEGRA ===\n";
    
    // Contadores
    $productosCreados = 0;
    $productosActualizados = 0;
    
    try {
        // Obtener productos de Alegra con un límite pequeño
        echo "Obteniendo productos de Alegra (límite 10)...\n";
        
        // Usar el método público obtenerProductos() con un límite pequeño
        $resultado = $alegraService->obtenerProductos(['limit' => 10]);
        
        if (!isset($resultado['success']) || !$resultado['success']) {
            echo "Error al obtener productos: " . ($resultado['message'] ?? 'Error desconocido') . "\n";
            return false;
        }
        
        $productosAlegra = $resultado['data'] ?? [];
        
        if (empty($productosAlegra)) {
            echo "No se encontraron productos en Alegra.\n";
            return false;
        }
        
        $totalProductos = count($productosAlegra);
        echo "Se encontraron {$totalProductos} productos en Alegra para importar.\n";
        
        // Procesar cada producto
        foreach ($productosAlegra as $productoAlegra) {
            echo "\nProcesando producto: " . json_encode($productoAlegra, JSON_PRETTY_PRINT) . "\n";
            
            $idAlegra = $productoAlegra['id'] ?? null;
            $nombre = $productoAlegra['name'] ?? 'Sin nombre';
            $referencia = $productoAlegra['reference'] ?? '';
            $precio = $productoAlegra['price'] ?? 0;
            
            echo "ID Alegra: {$idAlegra}, Nombre: {$nombre}, Referencia: {$referencia}, Precio: {$precio}\n";
            
            if (!$idAlegra) {
                echo "Producto sin ID de Alegra, omitiendo: {$nombre}\n";
                continue;
            }
            
            // Buscar si el producto ya existe en la base de datos local
            $productoLocal = Producto::where('id_alegra', $idAlegra)->first();
            
            if ($productoLocal) {
                // Actualizar producto existente
                $productoLocal->nombre = $nombre;
                $productoLocal->codigo = $referencia;
                $productoLocal->precio_venta = $precio;
                $productoLocal->precio_compra = $precio; // Usamos el mismo precio como referencia
                $productoLocal->save();
                
                echo "Producto actualizado: {$nombre} (ID Alegra: {$idAlegra})\n";
                $productosActualizados++;
            } else {
                // Crear nuevo producto
                $nuevoProducto = new Producto();
                $nuevoProducto->nombre = $nombre;
                $nuevoProducto->codigo = $referencia;
                $nuevoProducto->precio_venta = $precio;
                $nuevoProducto->precio_compra = $precio; // Usamos el mismo precio como referencia
                $nuevoProducto->id_alegra = $idAlegra;
                $nuevoProducto->stock = $productoAlegra['inventory']['available'] ?? 0;
                $nuevoProducto->estado = true; // Activar el producto por defecto
                $nuevoProducto->save();
                
                echo "Producto creado: {$nombre} (ID Alegra: {$idAlegra})\n";
                $productosCreados++;
            }
        }
        
        echo "\nImportación de productos completada.\n";
        echo "Productos creados: {$productosCreados}\n";
        echo "Productos actualizados: {$productosActualizados}\n";
        
        return true;
    } catch (\Exception $e) {
        echo "Error durante la importación de productos: " . $e->getMessage() . "\n";
        echo "Traza: " . $e->getTraceAsString() . "\n";
        return false;
    }
}

// Ejecutar la importación de productos
importarProductos($alegraService);

echo "\nProceso completado.\n";
