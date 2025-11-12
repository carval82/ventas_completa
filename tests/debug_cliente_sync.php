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
use Illuminate\Support\Facades\Http;

// Configuración para asegurar que la salida se muestre correctamente
ob_implicit_flush(true);
ini_set('output_buffering', 'off');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "=== DIAGNÓSTICO DE SINCRONIZACIÓN DE CLIENTES CON ALEGRA ===\n\n";

// Crear una instancia del servicio Alegra
$alegraService = new AlegraService();

// Probar la conexión primero
echo "1. Probando conexión con Alegra...\n";
$resultado = $alegraService->probarConexion();

if (!$resultado['success']) {
    echo "❌ Error al conectar con Alegra: " . ($resultado['message'] ?? 'Error desconocido') . "\n";
    exit(1);
}

echo "✅ Conexión exitosa con Alegra.\n\n";

// Obtener el cliente para sincronizar
echo "2. Buscando cliente para diagnóstico...\n";
$cliente = Cliente::first();

if (!$cliente) {
    echo "❌ No se encontraron clientes en la base de datos local.\n";
    exit(1);
}

echo "Cliente encontrado: " . $cliente->nombres . " " . $cliente->apellidos . " (ID: " . $cliente->id . ")\n";
echo "Cédula: " . $cliente->cedula . "\n";
echo "Email: " . $cliente->email . "\n";
echo "Teléfono: " . $cliente->telefono . "\n";
echo "Dirección: " . $cliente->direccion . "\n";
echo "ID Alegra actual: " . ($cliente->id_alegra ?: 'No sincronizado') . "\n\n";

// Verificar si el cliente ya existe en Alegra por su identificación
echo "3. Buscando cliente en Alegra por identificación...\n";
$clientesAlegra = $alegraService->obtenerClientes();

if (!$clientesAlegra['success']) {
    echo "❌ Error al obtener clientes de Alegra: " . ($clientesAlegra['message'] ?? 'Error desconocido') . "\n";
    echo "Detalles: " . ($clientesAlegra['error'] ?? 'Sin detalles') . "\n\n";
} else {
    echo "✅ Se obtuvieron " . count($clientesAlegra['data']) . " clientes de Alegra.\n";
    
    $clienteEncontrado = false;
    
    foreach ($clientesAlegra['data'] as $clienteAlegra) {
        if (isset($clienteAlegra['identification']) && $clienteAlegra['identification'] == $cliente->cedula) {
            $clienteEncontrado = true;
            echo "✅ Cliente encontrado en Alegra con ID: " . $clienteAlegra['id'] . "\n";
            echo "Nombre en Alegra: " . $clienteAlegra['name'] . "\n";
            echo "Identificación en Alegra: " . $clienteAlegra['identification'] . "\n\n";
            
            // Actualizar el ID de Alegra en el cliente local
            echo "Actualizando ID de Alegra en cliente local...\n";
            $cliente->id_alegra = $clienteAlegra['id'];
            $cliente->save();
            
            echo "✅ Cliente actualizado con ID de Alegra: " . $cliente->id_alegra . "\n\n";
            break;
        }
    }
    
    if (!$clienteEncontrado) {
        echo "❌ Cliente no encontrado en Alegra. Procediendo a crearlo...\n\n";
    }
}

// Si el cliente no se encontró en Alegra, intentar crearlo manualmente
if (empty($cliente->id_alegra)) {
    echo "4. Creando cliente en Alegra...\n";
    
    // Preparar datos del cliente para Alegra
    $datos = [
        'name' => $cliente->nombres . ' ' . $cliente->apellidos,
        'identification' => $cliente->cedula,
        'email' => $cliente->email ?: 'sin@email.com',
        'phonePrimary' => $cliente->telefono ?: '0000000000',
        'address' => [
            'address' => $cliente->direccion ?? 'Sin dirección'
        ],
        'type' => 'client',
        'kindOfPerson' => 'PERSON_ENTITY',
        'regime' => 'SIMPLIFIED_REGIME'
    ];
    
    echo "Datos a enviar a Alegra:\n";
    echo json_encode($datos, JSON_PRETTY_PRINT) . "\n\n";
    
    // Obtener credenciales de Alegra
    $empresa = \App\Models\Empresa::first();
    
    if (!$empresa || empty($empresa->alegra_email) || empty($empresa->alegra_token)) {
        echo "❌ No se encontraron credenciales de Alegra en la empresa.\n";
        exit(1);
    }
    
    $email = $empresa->alegra_email;
    $token = $empresa->alegra_token;
    
    echo "Usando credenciales de Alegra de la empresa:\n";
    echo "Email: " . $email . "\n";
    echo "Token: " . substr($token, 0, 3) . '...' . substr($token, -3) . "\n\n";
    
    // Hacer la petición HTTP directamente
    echo "Realizando petición HTTP a Alegra...\n";
    
    try {
        $response = Http::withBasicAuth($email, $token)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])
            ->post('https://api.alegra.com/api/v1/contacts', $datos);
        
        echo "Código de respuesta: " . $response->status() . "\n";
        
        if ($response->successful()) {
            $responseData = $response->json();
            echo "✅ Cliente creado exitosamente en Alegra.\n";
            echo "ID Alegra asignado: " . $responseData['id'] . "\n";
            
            // Actualizar el ID de Alegra en el cliente local
            $cliente->id_alegra = $responseData['id'];
            $cliente->save();
            
            echo "✅ Cliente actualizado con ID de Alegra: " . $cliente->id_alegra . "\n";
        } else {
            echo "❌ Error al crear cliente en Alegra.\n";
            echo "Respuesta: " . $response->body() . "\n";
        }
    } catch (\Exception $e) {
        echo "❌ Excepción al crear cliente en Alegra: " . $e->getMessage() . "\n";
    }
}

// Verificar el estado final
echo "\n5. Estado final del cliente:\n";
$cliente = Cliente::find($cliente->id); // Recargar el cliente desde la base de datos
echo "Cliente: " . $cliente->nombres . " " . $cliente->apellidos . " (ID: " . $cliente->id . ")\n";
echo "ID Alegra: " . ($cliente->id_alegra ?: 'No sincronizado') . "\n";

if ($cliente->id_alegra) {
    echo "✅ Cliente correctamente sincronizado con Alegra.\n";
} else {
    echo "❌ Cliente no sincronizado con Alegra.\n";
}

echo "\nDiagnóstico completado.\n";
