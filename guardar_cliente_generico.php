<?php
require __DIR__.'/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\AlegraService;
use App\Models\Cliente;
use Illuminate\Support\Facades\Log;

echo "Iniciando proceso para guardar el cliente genérico en la tabla de clientes...\n";

// Crear instancia del servicio Alegra
$alegraService = new AlegraService();

// Obtener el cliente genérico
echo "Obteniendo cliente genérico de Alegra...\n";
$clienteGenerico = $alegraService->obtenerClienteGenerico();

// Verificar si se obtuvo correctamente
if ($clienteGenerico['success'] && isset($clienteGenerico['data']['id'])) {
    echo "Cliente genérico encontrado en Alegra:\n";
    echo "ID: " . $clienteGenerico['data']['id'] . "\n";
    echo "Nombre: " . $clienteGenerico['data']['name'] . "\n";
    echo "Identificación: " . $clienteGenerico['data']['identification'] . "\n";
    
    // Verificar si ya existe en la base de datos
    $clienteExistente = Cliente::where('id_alegra', $clienteGenerico['data']['id'])->first();
    
    if ($clienteExistente) {
        echo "El cliente genérico ya existe en la base de datos:\n";
        echo "ID local: " . $clienteExistente->id . "\n";
        echo "ID Alegra: " . $clienteExistente->id_alegra . "\n";
        echo "Nombre: " . $clienteExistente->nombres . " " . $clienteExistente->apellidos . "\n";
    } else {
        // Crear el cliente en la base de datos
        echo "Creando cliente genérico en la base de datos...\n";
        
        $cliente = new Cliente([
            'nombres' => 'Consumidor',
            'apellidos' => 'Final',
            'cedula' => $clienteGenerico['data']['identification'] ?? '9999999999',
            'telefono' => '',
            'email' => '',
            'direccion' => '',
            'estado' => true,
            'id_alegra' => $clienteGenerico['data']['id']
        ]);
        
        $cliente->save();
        
        echo "Cliente genérico guardado en la base de datos:\n";
        echo "ID local: " . $cliente->id . "\n";
        echo "ID Alegra: " . $cliente->id_alegra . "\n";
        
        Log::info('Cliente genérico guardado en la tabla de clientes', [
            'id' => $cliente->id,
            'id_alegra' => $cliente->id_alegra
        ]);
    }
} else {
    echo "Error al obtener el cliente genérico de Alegra:\n";
    echo "Mensaje: " . $clienteGenerico['message'] . "\n";
}

echo "\nProceso finalizado.\n";
