<?php

// Importar las clases necesarias
require_once __DIR__ . '/../vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Producto;
use App\Services\AlegraService;
use Illuminate\Support\Facades\Log;

// Aumentar el tiempo máximo de ejecución
set_time_limit(300);

// Activar la salida de buffer
ob_implicit_flush(true);

echo "=== SINCRONIZACIÓN DE PRODUCTOS CON ALEGRA ===\n\n";

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

// Función para importar productos desde Alegra
function importarProductosDesdeAlegra($alegraService) {
    echo "Obteniendo productos desde Alegra (límite 10)...\n";
    
    // Obtener productos de Alegra con un límite
    $resultado = $alegraService->obtenerProductos(['limit' => 10]);
    
    if (!$resultado['success']) {
        echo "Error al obtener productos: " . ($resultado['message'] ?? 'Error desconocido') . "\n";
        return false;
    }
    
    $productosAlegra = $resultado['data'] ?? [];
    
    if (empty($productosAlegra)) {
        echo "No se encontraron productos en Alegra.\n";
        return false;
    }
    
    echo "Se encontraron " . count($productosAlegra) . " productos en Alegra.\n\n";
    
    $productosCreados = 0;
    $productosActualizados = 0;
    
    foreach ($productosAlegra as $productoAlegra) {
        echo "\nProcesando producto: " . json_encode($productoAlegra, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        
        $idAlegra = $productoAlegra['id'] ?? null;
        $nombre = $productoAlegra['name'] ?? 'Sin nombre';
        
        if (!$idAlegra) {
            echo "Producto sin ID de Alegra, omitiendo: {$nombre}\n";
            continue;
        }
        
        // Buscar si el producto ya existe en la base de datos local
        $productoLocal = Producto::where('id_alegra', $idAlegra)->first();
        
        if ($productoLocal) {
            echo "Producto ya existe: {$nombre} (ID Alegra: {$idAlegra})\n";
            $productosActualizados++;
        } else {
            // Crear un nuevo producto con los datos mínimos necesarios
            $nuevoProducto = new Producto();
            $nuevoProducto->nombre = $nombre;
            $nuevoProducto->codigo = $productoAlegra['reference'] ?? '';
            $nuevoProducto->precio_venta = is_array($productoAlegra['price']) ? 0 : $productoAlegra['price'];
            $nuevoProducto->precio_compra = is_array($productoAlegra['price']) ? 0 : $productoAlegra['price'];
            $nuevoProducto->id_alegra = $idAlegra;
            
            // Manejar el stock con cuidado para evitar errores de array
            if (isset($productoAlegra['inventory']) && isset($productoAlegra['inventory']['available'])) {
                $stock = $productoAlegra['inventory']['available'];
                $nuevoProducto->stock = is_array($stock) ? 0 : $stock;
            } else {
                $nuevoProducto->stock = 0;
            }
            
            $nuevoProducto->stock_minimo = 0;
            $nuevoProducto->estado = true;
            
            // Manejar la descripción con cuidado
            if (isset($productoAlegra['description'])) {
                $descripcion = $productoAlegra['description'];
                $nuevoProducto->descripcion = is_array($descripcion) ? json_encode($descripcion) : $descripcion;
            } else {
                $nuevoProducto->descripcion = '';
            }
            
            // Añadir unidad de medida si existe
            if (isset($productoAlegra['unit']) && isset($productoAlegra['unit']['name'])) {
                $nuevoProducto->unidad_medida = $productoAlegra['unit']['name'];
            } else {
                $nuevoProducto->unidad_medida = 'UND';
            }
            
            try {
                echo "Intentando guardar producto con datos: " . json_encode($nuevoProducto->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                $nuevoProducto->save();
                echo "Producto creado: {$nombre} (ID Alegra: {$idAlegra})\n";
                $productosCreados++;
            } catch (\Exception $e) {
                echo "Error al crear producto {$nombre}: " . $e->getMessage() . "\n";
                echo "Traza: " . $e->getTraceAsString() . "\n";
            }
        }
    }
    
    echo "\nImportación completada.\n";
    echo "Productos creados: {$productosCreados}\n";
    echo "Productos ya existentes: {$productosActualizados}\n";
    
    return true;
}

// Función para sincronizar productos locales con Alegra
function sincronizarProductosConAlegra() {
    echo "\nSincronizando productos locales con Alegra...\n";
    
    // Obtener productos locales que no tienen id_alegra
    $productos = Producto::whereNull('id_alegra')->orWhere('id_alegra', '')->take(5)->get();
    
    if ($productos->isEmpty()) {
        echo "No hay productos locales para sincronizar con Alegra.\n";
        return;
    }
    
    echo "Se encontraron " . $productos->count() . " productos locales para sincronizar.\n\n";
    
    $sincronizados = 0;
    $errores = 0;
    
    foreach ($productos as $producto) {
        echo "Sincronizando producto: " . $producto->nombre . " (ID local: " . $producto->id . ")\n";
        
        try {
            $resultado = $producto->syncToAlegra();
            
            if ($resultado['success']) {
                echo "Producto sincronizado correctamente. ID Alegra: " . $resultado['id_alegra'] . "\n";
                $sincronizados++;
            } else {
                echo "Error al sincronizar producto: " . ($resultado['message'] ?? 'Error desconocido') . "\n";
                $errores++;
            }
        } catch (\Exception $e) {
            echo "Excepción al sincronizar producto: " . $e->getMessage() . "\n";
            $errores++;
        }
    }
    
    echo "\nSincronización completada.\n";
    echo "Productos sincronizados: {$sincronizados}\n";
    echo "Errores: {$errores}\n";
}

// Ejecutar las funciones
echo "=== FASE 1: IMPORTAR PRODUCTOS DESDE ALEGRA ===\n";
importarProductosDesdeAlegra($alegraService);

echo "\n=== FASE 2: SINCRONIZAR PRODUCTOS LOCALES CON ALEGRA ===\n";
sincronizarProductosConAlegra();

echo "\nProceso completado.\n";
