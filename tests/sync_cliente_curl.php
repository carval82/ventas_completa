<?php

// Importar las clases necesarias
require_once __DIR__ . '/../vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Cliente;
use App\Models\Empresa;

// Configuración para asegurar que la salida se muestre correctamente
ob_implicit_flush(true);
ini_set('output_buffering', 'off');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "=== SINCRONIZACIÓN DE CLIENTE CON ALEGRA USANDO CURL ===\n\n";

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

echo "Credenciales de Alegra obtenidas de la empresa.\n";
echo "Email: " . $email . "\n";
echo "Token: " . substr($token, 0, 3) . '...' . substr($token, -3) . "\n\n";

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

// Hacer la petición usando cURL
echo "Realizando petición cURL a Alegra...\n";

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
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

echo "Código de respuesta HTTP: " . $httpCode . "\n";

if ($error) {
    echo "Error cURL: " . $error . "\n";
}

echo "Respuesta completa:\n" . $response . "\n\n";

if ($httpCode >= 200 && $httpCode < 300) {
    $data = json_decode($response, true);
    
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
    }
} else {
    echo "❌ Error al crear cliente en Alegra.\n";
}

echo "\nSincronización completada.\n";

// Verificar el estado actual
$clienteActualizado = Cliente::find($cliente->id);
echo "\nEstado final del cliente:\n";
echo "ID Alegra: " . ($clienteActualizado->id_alegra ?: 'No sincronizado') . "\n";
