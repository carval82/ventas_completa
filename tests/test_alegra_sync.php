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

// Configurar salida
ob_implicit_flush(true);
ini_set('output_buffering', 'off');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "=== TEST DE SINCRONIZACIÓN CON ALEGRA ===\n\n";

// Crear una instancia del servicio Alegra
$alegraService = new AlegraService();

// Probar la conexión
echo "1. Probando conexión con Alegra...\n";
$resultado = $alegraService->probarConexion();

if ($resultado['success']) {
    echo "✅ Conexión exitosa con Alegra.\n\n";
} else {
    echo "❌ Error al conectar con Alegra: " . ($resultado['message'] ?? 'Error desconocido') . "\n";
    exit(1);
}

// Verificar y sincronizar un producto
echo "2. Buscando un producto para sincronizar...\n";
$producto = Producto::first();

if (!$producto) {
    echo "❌ No se encontraron productos en la base de datos local.\n";
} else {
    echo "Producto encontrado: " . $producto->nombre . " (ID: " . $producto->id . ")\n";
    
    if ($producto->id_alegra) {
        echo "✅ Producto ya sincronizado con Alegra (ID Alegra: " . $producto->id_alegra . ")\n\n";
    } else {
        echo "Producto no sincronizado con Alegra. Intentando sincronizar...\n";
        
        try {
            $resultado = $producto->syncToAlegra();
            
            if ($resultado['success']) {
                echo "✅ Producto sincronizado correctamente con Alegra.\n";
                echo "ID Alegra asignado: " . $resultado['id_alegra'] . "\n\n";
            } else {
                echo "❌ Error al sincronizar producto: " . ($resultado['message'] ?? 'Error desconocido') . "\n";
                if (isset($resultado['error'])) {
                    echo "Detalles del error: " . $resultado['error'] . "\n\n";
                }
            }
        } catch (\Exception $e) {
            echo "❌ Excepción al sincronizar producto: " . $e->getMessage() . "\n\n";
        }
    }
}

// Verificar y sincronizar un cliente
echo "3. Buscando un cliente para sincronizar...\n";
$cliente = Cliente::first();

if (!$cliente) {
    echo "❌ No se encontraron clientes en la base de datos local.\n";
} else {
    echo "Cliente encontrado: " . $cliente->nombre . " (ID: " . $cliente->id . ")\n";
    
    if ($cliente->id_alegra) {
        echo "✅ Cliente ya sincronizado con Alegra (ID Alegra: " . $cliente->id_alegra . ")\n\n";
    } else {
        echo "Cliente no sincronizado con Alegra. Intentando sincronizar...\n";
        
        try {
            $resultado = $cliente->syncToAlegra();
            
            if ($resultado['success']) {
                echo "✅ Cliente sincronizado correctamente con Alegra.\n";
                echo "ID Alegra asignado: " . $resultado['id_alegra'] . "\n\n";
            } else {
                echo "❌ Error al sincronizar cliente: " . ($resultado['message'] ?? 'Error desconocido') . "\n";
                if (isset($resultado['error'])) {
                    echo "Detalles del error: " . $resultado['error'] . "\n\n";
                }
            }
        } catch (\Exception $e) {
            echo "❌ Excepción al sincronizar cliente: " . $e->getMessage() . "\n\n";
        }
    }
}

// Verificar formatos correctos para Alegra
echo "4. Formatos correctos para integración con Alegra:\n\n";

echo "a) Formato correcto para cliente:\n";
echo "   { \"client\": { \"id\": " . ($cliente && $cliente->id_alegra ? intval($cliente->id_alegra) : "ID_ALEGRA_DEL_CLIENTE") . " } }\n\n";

echo "b) Formato correcto para método de pago:\n";
echo "   { \"payment\": { \"paymentMethod\": { \"id\": 10 }, \"account\": { \"id\": 1 } } }\n\n";

echo "c) Formato para IVA (taxRate a nivel de ítem):\n";
echo "   { \"items\": [ { \"id\": " . ($producto && $producto->id_alegra ? $producto->id_alegra : "ID_ALEGRA_DEL_PRODUCTO") . ", \"price\": 10000, \"quantity\": 1, \"taxRate\": 19 } ] }\n\n";

echo "Test de sincronización completado.\n";
