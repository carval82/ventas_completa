<?php

// Importar las clases necesarias
require_once __DIR__ . '/../vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Cliente;
use App\Models\Empresa;
use Illuminate\Support\Facades\Http;

echo "=== SINCRONIZACIÓN DIRECTA DE CLIENTE CON ALEGRA ===\n\n";

// Obtener el cliente
$cliente = Cliente::first();

if (!$cliente) {
    echo "❌ No se encontraron clientes en la base de datos local.\n";
    exit(1);
}

echo "Cliente encontrado: " . $cliente->nombres . " " . $cliente->apellidos . " (ID: " . $cliente->id . ")\n\n";

// Obtener credenciales de Alegra
$empresa = Empresa::first();

if (!$empresa || empty($empresa->alegra_email) || empty($empresa->alegra_token)) {
    echo "❌ No se encontraron credenciales de Alegra en la empresa.\n";
    exit(1);
}

$email = $empresa->alegra_email;
$token = $empresa->alegra_token;

echo "Credenciales de Alegra obtenidas de la empresa.\n\n";

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

// Hacer la petición HTTP directamente
echo "Realizando petición HTTP a Alegra...\n";

try {
    $response = Http::withBasicAuth($email, $token)
        ->withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])
        ->post('https://api.alegra.com/api/v1/contacts', $datos);
    
    echo "Código de respuesta: " . $response->status() . "\n\n";
    
    if ($response->successful()) {
        $responseData = $response->json();
        echo "✅ Cliente creado exitosamente en Alegra.\n";
        echo "ID Alegra asignado: " . $responseData['id'] . "\n\n";
        
        // Actualizar el ID de Alegra en el cliente local
        $cliente->id_alegra = $responseData['id'];
        $cliente->save();
        
        echo "✅ Cliente actualizado con ID de Alegra: " . $cliente->id_alegra . "\n";
        
        // Verificar que el formato para enviar a Alegra en una factura es correcto
        echo "\nFormato correcto para incluir este cliente en una factura:\n";
        echo "{ \"client\": { \"id\": " . $cliente->id_alegra . " } }\n";
    } else {
        echo "❌ Error al crear cliente en Alegra.\n";
        echo "Respuesta: " . $response->body() . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Excepción al crear cliente en Alegra: " . $e->getMessage() . "\n";
}

echo "\nSincronización completada.\n";
