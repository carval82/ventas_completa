<?php

// Importar las clases necesarias
require_once __DIR__ . '/../vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Producto;
use App\Models\Cliente;
use App\Services\AlegraService;
use Illuminate\Support\Facades\Log;

// Aumentar el tiempo máximo de ejecución
set_time_limit(300);

echo "=== SINCRONIZACIÓN SIMPLE CON ALEGRA ===\n\n";

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

// Sincronizar un producto local con Alegra
function sincronizarProducto($id) {
    echo "Buscando producto con ID: {$id}\n";
    
    $producto = Producto::find($id);
    
    if (!$producto) {
        echo "Producto no encontrado.\n";
        return;
    }
    
    echo "Producto encontrado: {$producto->nombre}\n";
    echo "Sincronizando con Alegra...\n";
    
    try {
        $resultado = $producto->syncToAlegra();
        
        if ($resultado['success']) {
            echo "Producto sincronizado correctamente con Alegra.\n";
            echo "ID Alegra asignado: {$resultado['id_alegra']}\n";
        } else {
            echo "Error al sincronizar producto: " . ($resultado['message'] ?? 'Error desconocido') . "\n";
            if (isset($resultado['error'])) {
                echo "Detalles del error: " . $resultado['error'] . "\n";
            }
        }
    } catch (\Exception $e) {
        echo "Excepción al sincronizar producto: " . $e->getMessage() . "\n";
    }
}

// Sincronizar un cliente local con Alegra
function sincronizarCliente($id) {
    echo "Buscando cliente con ID: {$id}\n";
    
    $cliente = Cliente::find($id);
    
    if (!$cliente) {
        echo "Cliente no encontrado.\n";
        return;
    }
    
    echo "Cliente encontrado: {$cliente->nombre}\n";
    echo "Sincronizando con Alegra...\n";
    
    try {
        $resultado = $cliente->syncToAlegra();
        
        if ($resultado['success']) {
            echo "Cliente sincronizado correctamente con Alegra.\n";
            echo "ID Alegra asignado: {$resultado['id_alegra']}\n";
        } else {
            echo "Error al sincronizar cliente: " . ($resultado['message'] ?? 'Error desconocido') . "\n";
            if (isset($resultado['error'])) {
                echo "Detalles del error: " . $resultado['error'] . "\n";
            }
        }
    } catch (\Exception $e) {
        echo "Excepción al sincronizar cliente: " . $e->getMessage() . "\n";
    }
}

// Verificar si un producto ya está sincronizado
function verificarSincronizacionProducto($id) {
    $producto = Producto::find($id);
    
    if (!$producto) {
        echo "Producto no encontrado.\n";
        return;
    }
    
    echo "Producto: {$producto->nombre}\n";
    
    if ($producto->id_alegra) {
        echo "El producto ya está sincronizado con Alegra (ID Alegra: {$producto->id_alegra}).\n";
    } else {
        echo "El producto NO está sincronizado con Alegra.\n";
    }
}

// Verificar si un cliente ya está sincronizado
function verificarSincronizacionCliente($id) {
    $cliente = Cliente::find($id);
    
    if (!$cliente) {
        echo "Cliente no encontrado.\n";
        return;
    }
    
    echo "Cliente: {$cliente->nombre}\n";
    
    if ($cliente->id_alegra) {
        echo "El cliente ya está sincronizado con Alegra (ID Alegra: {$cliente->id_alegra}).\n";
    } else {
        echo "El cliente NO está sincronizado con Alegra.\n";
    }
}

// Ejecutar las funciones de sincronización
echo "=== VERIFICACIÓN DE PRODUCTOS ===\n";
verificarSincronizacionProducto(1); // Verificar el producto con ID 1

echo "\n=== SINCRONIZACIÓN DE PRODUCTOS ===\n";
sincronizarProducto(1); // Sincronizar el producto con ID 1

echo "\n=== VERIFICACIÓN DE CLIENTES ===\n";
verificarSincronizacionCliente(1); // Verificar el cliente con ID 1

echo "\n=== SINCRONIZACIÓN DE CLIENTES ===\n";
sincronizarCliente(1); // Sincronizar el cliente con ID 1

echo "\nProceso completado.\n";
