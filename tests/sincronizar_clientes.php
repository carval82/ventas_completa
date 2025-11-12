<?php

// Importar las clases necesarias
require_once __DIR__ . '/../vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Cliente;
use App\Services\AlegraService;
use Illuminate\Support\Facades\Log;

// Configuración para asegurar que la salida se muestre correctamente
ob_implicit_flush(true);
ini_set('output_buffering', 'off');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "=== SINCRONIZACIÓN DE CLIENTES CON ALEGRA ===\n\n";

// Crear una instancia del servicio Alegra
$alegraService = new AlegraService();

// Probar la conexión primero
echo "Probando conexión con Alegra...\n";
$resultado = $alegraService->probarConexion();

if (!$resultado['success']) {
    echo "❌ Error al conectar con Alegra: " . ($resultado['message'] ?? 'Error desconocido') . "\n";
    exit(1);
}

echo "✅ Conexión exitosa con Alegra.\n\n";

// Obtener todos los clientes que no están sincronizados
$clientes = Cliente::whereNull('id_alegra')->orWhere('id_alegra', '')->get();
$totalClientes = count($clientes);

echo "Se encontraron {$totalClientes} clientes para sincronizar.\n\n";

if ($totalClientes === 0) {
    echo "✅ Todos los clientes ya están sincronizados con Alegra.\n";
    exit(0);
}

// Sincronizar cada cliente
$sincronizados = 0;
$errores = 0;

foreach ($clientes as $index => $cliente) {
    $numeroCliente = $index + 1;
    echo "Sincronizando cliente {$numeroCliente}/{$totalClientes}: {$cliente->nombres} {$cliente->apellidos} (ID: {$cliente->id})...\n";
    
    try {
        // Verificar que el cliente tenga los campos requeridos
        $camposFaltantes = [];
        
        if (empty($cliente->nombres)) $camposFaltantes[] = 'nombres';
        if (empty($cliente->apellidos)) $camposFaltantes[] = 'apellidos';
        if (empty($cliente->cedula)) $camposFaltantes[] = 'cedula';
        
        if (!empty($camposFaltantes)) {
            echo "❌ El cliente no tiene los campos requeridos: " . implode(', ', $camposFaltantes) . "\n";
            $errores++;
            continue;
        }
        
        // Intentar sincronizar el cliente
        $resultado = $cliente->syncToAlegra();
        
        if ($resultado['success']) {
            echo "✅ Cliente sincronizado correctamente. ID Alegra: {$resultado['id_alegra']}\n";
            $sincronizados++;
        } else {
            echo "❌ Error al sincronizar cliente: " . ($resultado['message'] ?? 'Error desconocido') . "\n";
            if (isset($resultado['error'])) {
                echo "   Detalles: " . $resultado['error'] . "\n";
            }
            $errores++;
        }
    } catch (\Exception $e) {
        echo "❌ Excepción al sincronizar cliente: " . $e->getMessage() . "\n";
        $errores++;
    }
    
    echo "\n";
    
    // Pequeña pausa para no saturar la API
    usleep(500000); // 0.5 segundos
}

// Resumen final
echo "=== RESUMEN DE SINCRONIZACIÓN ===\n";
echo "Total de clientes procesados: {$totalClientes}\n";
echo "Clientes sincronizados exitosamente: {$sincronizados}\n";
echo "Errores de sincronización: {$errores}\n";

// Verificar el estado actual
$totalClientesDB = Cliente::count();
$clientesSincronizados = Cliente::whereNotNull('id_alegra')->where('id_alegra', '!=', '')->count();
$porcentaje = round(($clientesSincronizados / $totalClientesDB) * 100, 2);

echo "\nEstado actual: {$clientesSincronizados}/{$totalClientesDB} clientes sincronizados ({$porcentaje}%)\n";
echo "\nSincronización completada.\n";
