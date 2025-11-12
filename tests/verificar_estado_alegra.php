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

// Configuración para asegurar que la salida se muestre correctamente
ob_implicit_flush(true);
ini_set('output_buffering', 'off');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "=== VERIFICACIÓN DE ESTADO DE INTEGRACIÓN CON ALEGRA ===\n\n";

// Crear una instancia del servicio Alegra
$alegraService = new AlegraService();

// 1. Probar la conexión
echo "1. PRUEBA DE CONEXIÓN CON ALEGRA\n";
echo "-------------------------------\n";
$resultado = $alegraService->probarConexion();

if ($resultado['success']) {
    echo "✅ Conexión exitosa con Alegra.\n\n";
} else {
    echo "❌ Error al conectar con Alegra: " . ($resultado['message'] ?? 'Error desconocido') . "\n\n";
    exit(1);
}

// 2. Verificar estado de productos
echo "2. ESTADO DE PRODUCTOS\n";
echo "--------------------\n";

// Contar productos totales
$totalProductos = Producto::count();
echo "Total de productos en la base de datos local: {$totalProductos}\n";

// Contar productos sincronizados
$productosSincronizados = Producto::whereNotNull('id_alegra')->where('id_alegra', '!=', '')->count();
echo "Productos sincronizados con Alegra: {$productosSincronizados}\n";

// Calcular porcentaje de sincronización
if ($totalProductos > 0) {
    $porcentajeSincronizacion = round(($productosSincronizados / $totalProductos) * 100, 2);
    echo "Porcentaje de sincronización: {$porcentajeSincronizacion}%\n";
}

// Mostrar algunos productos sincronizados como ejemplo
if ($productosSincronizados > 0) {
    $ejemplos = Producto::whereNotNull('id_alegra')->where('id_alegra', '!=', '')->take(5)->get();
    echo "\nEjemplos de productos sincronizados:\n";
    
    foreach ($ejemplos as $producto) {
        echo "- {$producto->nombre} (ID Local: {$producto->id}, ID Alegra: {$producto->id_alegra})\n";
    }
}

echo "\n";

// 3. Verificar estado de clientes
echo "3. ESTADO DE CLIENTES\n";
echo "-------------------\n";

// Contar clientes totales
$totalClientes = Cliente::count();
echo "Total de clientes en la base de datos local: {$totalClientes}\n";

// Contar clientes sincronizados
$clientesSincronizados = Cliente::whereNotNull('id_alegra')->where('id_alegra', '!=', '')->count();
echo "Clientes sincronizados con Alegra: {$clientesSincronizados}\n";

// Calcular porcentaje de sincronización
if ($totalClientes > 0) {
    $porcentajeSincronizacion = round(($clientesSincronizados / $totalClientes) * 100, 2);
    echo "Porcentaje de sincronización: {$porcentajeSincronizacion}%\n";
}

// Mostrar algunos clientes sincronizados como ejemplo
if ($clientesSincronizados > 0) {
    $ejemplos = Cliente::whereNotNull('id_alegra')->where('id_alegra', '!=', '')->take(5)->get();
    echo "\nEjemplos de clientes sincronizados:\n";
    
    foreach ($ejemplos as $cliente) {
        echo "- {$cliente->nombre} (ID Local: {$cliente->id}, ID Alegra: {$cliente->id_alegra})\n";
    }
}

echo "\n";

// 4. Recordatorio de formatos correctos para Alegra
echo "4. FORMATOS CORRECTOS PARA INTEGRACIÓN CON ALEGRA\n";
echo "----------------------------------------------\n";

echo "a) Formato correcto para cliente:\n";
echo "   { \"client\": { \"id\": ID_ALEGRA_DEL_CLIENTE } }\n\n";

echo "b) Formato correcto para método de pago:\n";
echo "   { \"payment\": { \"paymentMethod\": { \"id\": 10 }, \"account\": { \"id\": 1 } } }\n\n";

echo "c) Formatos para envío de IVA a Alegra:\n";
echo "   - taxRate a nivel de ítem (formato simple):\n";
echo "     { \"items\": [ { \"id\": ID_ALEGRA_DEL_PRODUCTO, \"price\": 10000, \"quantity\": 1, \"taxRate\": 19 } ] }\n\n";
echo "   - tax a nivel de ítem (formato completo):\n";
echo "     { \"items\": [ { \"id\": ID_ALEGRA_DEL_PRODUCTO, \"price\": 10000, \"quantity\": 1, \"tax\": { \"id\": 1, \"name\": \"IVA\", \"percentage\": 19, \"value\": 1900 } } ] }\n\n";
echo "   - taxes como array a nivel de ítem:\n";
echo "     { \"items\": [ { \"id\": ID_ALEGRA_DEL_PRODUCTO, \"price\": 10000, \"quantity\": 1, \"taxes\": [ { \"id\": 1, \"name\": \"IVA\", \"percentage\": 19, \"value\": 1900 } ] } ] }\n\n";
echo "   - totalTaxes a nivel de factura:\n";
echo "     { \"items\": [ { \"id\": ID_ALEGRA_DEL_PRODUCTO, \"price\": 10000, \"quantity\": 1 } ], \"totalTaxes\": [ { \"id\": 1, \"name\": \"IVA\", \"percentage\": 19, \"value\": 1900 } ] }\n\n";

echo "Verificación de estado completada.\n";
