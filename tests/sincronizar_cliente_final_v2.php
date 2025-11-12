<?php

// Importar las clases necesarias
require_once __DIR__ . '/../vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Cliente;
use App\Models\Empresa;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

echo "=== SINCRONIZACIÓN FINAL DE CLIENTE CON ALEGRA ===\n\n";

// Obtener el cliente
$cliente = Cliente::first();

if (!$cliente) {
    echo "❌ No se encontraron clientes en la base de datos local.\n";
    exit(1);
}

echo "Cliente encontrado: " . $cliente->nombres . " " . $cliente->apellidos . " (ID: " . $cliente->id . ")\n";
echo "Cédula: " . $cliente->cedula . "\n";
echo "Email: " . $cliente->email . "\n";
echo "Teléfono: " . $cliente->telefono . "\n";
echo "Dirección: " . $cliente->direccion . "\n\n";

// Obtener credenciales de Alegra
$empresa = Empresa::first();

if (!$empresa || empty($empresa->alegra_email) || empty($empresa->alegra_token)) {
    echo "❌ No se encontraron credenciales de Alegra en la empresa.\n";
    exit(1);
}

$email = $empresa->alegra_email;
$token = $empresa->alegra_token;

echo "Credenciales de Alegra obtenidas correctamente.\n";
echo "Email: " . $email . "\n";
echo "Token: " . substr($token, 0, 3) . '...' . substr($token, -3) . "\n\n";

// Primero verificar si el cliente ya existe en Alegra por su identificación
echo "Verificando si el cliente ya existe en Alegra...\n";

// Configurar cliente HTTP con autenticación básica
$http = Http::withBasicAuth($email, $token)
    ->withHeaders([
        'Content-Type' => 'application/json',
        'Accept' => 'application/json'
    ])
    ->baseUrl('https://api.alegra.com/api/v1');

// Buscar cliente por identificación
$response = $http->get('contacts', [
    'identification' => $cliente->cedula,
    'type' => 'client'
]);

if ($response->successful()) {
    $clientesAlegra = $response->json();
    
    if (!empty($clientesAlegra)) {
        foreach ($clientesAlegra as $clienteAlegra) {
            if (isset($clienteAlegra['identification']) && $clienteAlegra['identification'] == $cliente->cedula) {
                echo "✅ Cliente encontrado en Alegra con ID: " . $clienteAlegra['id'] . "\n";
                
                // Actualizar el ID de Alegra en el cliente local
                $cliente->id_alegra = $clienteAlegra['id'];
                $cliente->save();
                
                echo "✅ Cliente actualizado con ID de Alegra: " . $cliente->id_alegra . "\n\n";
                
                // Verificar que el formato para enviar a Alegra en una factura es correcto
                echo "Formato correcto para incluir este cliente en una factura:\n";
                echo "{ \"client\": { \"id\": " . $cliente->id_alegra . " } }\n";
                
                // Verificar el estado actual de sincronización
                $totalClientes = Cliente::count();
                $clientesSincronizados = Cliente::whereNotNull('id_alegra')->where('id_alegra', '!=', '')->count();
                $porcentaje = round(($clientesSincronizados / $totalClientes) * 100, 2);
                
                echo "\nEstado actual: {$clientesSincronizados}/{$totalClientes} clientes sincronizados ({$porcentaje}%)\n";
                echo "\nSincronización completada.\n";
                
                exit(0);
            }
        }
    }
    
    echo "Cliente no encontrado en Alegra. Procediendo a crearlo...\n\n";
} else {
    echo "Error al buscar cliente en Alegra: " . $response->status() . "\n";
    echo "Respuesta: " . $response->body() . "\n";
    echo "Procediendo a crear el cliente...\n\n";
}

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

// Hacer la petición a Alegra para crear el cliente
echo "Enviando cliente a Alegra usando HTTP Client...\n";

// Usar el cliente HTTP para crear el cliente
$response = $http->post('contacts', $datos);

echo "Código de respuesta HTTP: " . $response->status() . "\n";
echo "Respuesta completa:\n" . $response->body() . "\n\n";

if ($response->successful()) {
    $data = $response->json();
    
    if (isset($data['id'])) {
        echo "✅ Cliente creado exitosamente en Alegra.\n";
        echo "ID Alegra asignado: " . $data['id'] . "\n\n";
        
        // Actualizar el ID de Alegra en el cliente local
        $cliente->id_alegra = $data['id'];
        $cliente->save();
        
        echo "✅ Cliente actualizado con ID de Alegra: " . $cliente->id_alegra . "\n";
        
        // Verificar que el formato para enviar a Alegra en una factura es correcto
        echo "\nFormato correcto para incluir este cliente en una factura:\n";
        echo "{ \"client\": { \"id\": " . $cliente->id_alegra . " } }\n";
    } else {
        echo "❌ Error: La respuesta no contiene un ID de Alegra.\n";
        echo "Respuesta completa: " . $response->body() . "\n";
    }
} else {
    echo "❌ Error al crear cliente en Alegra.\n";
    echo "Respuesta: " . $response->body() . "\n";
    
    // Intentar con cURL como último recurso
    echo "\nIntentando con cURL como último recurso...\n";
    
    // Configurar cURL
    $ch = curl_init();
    $url = 'https://api.alegra.com/api/v1/contacts';
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($email . ':' . $token)
    ]);
    
    // Ejecutar la solicitud
    $curlResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    echo "Código de respuesta cURL: " . $httpCode . "\n";
    
    if ($error) {
        echo "Error cURL: " . $error . "\n";
    }
    
    echo "Respuesta cURL:\n" . $curlResponse . "\n\n";
    
    if ($httpCode >= 200 && $httpCode < 300) {
        $data = json_decode($curlResponse, true);
        
        if (isset($data['id'])) {
            echo "✅ Cliente creado exitosamente en Alegra usando cURL.\n";
            echo "ID Alegra asignado: " . $data['id'] . "\n\n";
            
            // Actualizar el ID de Alegra en el cliente local
            $cliente->id_alegra = $data['id'];
            $cliente->save();
            
            echo "✅ Cliente actualizado con ID de Alegra: " . $cliente->id_alegra . "\n";
            
            // Verificar que el formato para enviar a Alegra en una factura es correcto
            echo "\nFormato correcto para incluir este cliente en una factura:\n";
            echo "{ \"client\": { \"id\": " . $cliente->id_alegra . " } }\n";
        }
    }
}

// Verificar estado actual de sincronización
$totalClientes = Cliente::count();
$clientesSincronizados = Cliente::whereNotNull('id_alegra')->where('id_alegra', '!=', '')->count();
$porcentaje = round(($clientesSincronizados / $totalClientes) * 100, 2);

echo "\nEstado actual: {$clientesSincronizados}/{$totalClientes} clientes sincronizados ({$porcentaje}%)\n";
echo "\nSincronización completada.\n";
