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

echo "=== VERIFICACIÓN RÁPIDA DE ALEGRA ===\n\n";

// Crear una instancia del servicio Alegra
$alegraService = new AlegraService();

// 1. Probar la conexión
echo "1. Conexión con Alegra: ";
$resultado = $alegraService->probarConexion();

if ($resultado['success']) {
    echo "✅ OK\n";
} else {
    echo "❌ ERROR: " . ($resultado['message'] ?? 'Error desconocido') . "\n";
}

// 2. Verificar productos sincronizados
$totalProductos = Producto::count();
$productosSincronizados = Producto::whereNotNull('id_alegra')->where('id_alegra', '!=', '')->count();
$porcentaje = ($totalProductos > 0) ? round(($productosSincronizados / $totalProductos) * 100, 2) : 0;

echo "2. Productos: {$productosSincronizados}/{$totalProductos} sincronizados ({$porcentaje}%)\n";

// 3. Verificar clientes sincronizados
$totalClientes = Cliente::count();
$clientesSincronizados = Cliente::whereNotNull('id_alegra')->where('id_alegra', '!=', '')->count();
$porcentaje = ($totalClientes > 0) ? round(($clientesSincronizados / $totalClientes) * 100, 2) : 0;

echo "3. Clientes: {$clientesSincronizados}/{$totalClientes} sincronizados ({$porcentaje}%)\n";

echo "\nRecordatorio de formatos correctos:\n";
echo "- Cliente: { \"client\": { \"id\": ID_ALEGRA } }\n";
echo "- Pago: { \"payment\": { \"paymentMethod\": { \"id\": 10 }, \"account\": { \"id\": 1 } } }\n";
echo "- IVA: { \"items\": [ { \"id\": ID_ALEGRA, \"price\": 10000, \"quantity\": 1, \"taxRate\": 19 } ] }\n";
