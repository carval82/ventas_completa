<?php

// Importar las clases necesarias
require_once __DIR__ . '/../vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Cliente;
use App\Models\Empresa;

echo "=== CREACIÓN SIMPLE DE CLIENTE EN ALEGRA ===\n\n";

// Obtener credenciales de Alegra
$empresa = Empresa::first();

if (!$empresa || empty($empresa->alegra_email) || empty($empresa->alegra_token)) {
    echo "❌ No se encontraron credenciales de Alegra en la empresa.\n";
    exit(1);
}

$email = $empresa->alegra_email;
$token = $empresa->alegra_token;

echo "Credenciales de Alegra obtenidas correctamente.\n\n";

// Buscar el cliente local
$cliente = Cliente::first();

if (!$cliente) {
    echo "❌ No se encontraron clientes en la base de datos local.\n";
    exit(1);
}

echo "Cliente local encontrado: " . $cliente->nombres . " " . $cliente->apellidos . " (ID: " . $cliente->id . ")\n";
echo "Cédula: " . $cliente->cedula . "\n\n";

// Datos mínimos para crear un cliente en Alegra
$datosMinimos = [
    'name' => $cliente->nombres . ' ' . $cliente->apellidos,
    'identification' => $cliente->cedula,
    'type' => 'client'
];

echo "Datos mínimos a enviar a Alegra:\n";
echo json_encode($datosMinimos, JSON_PRETTY_PRINT) . "\n\n";

// Hacer la petición a Alegra
echo "Enviando cliente a Alegra...\n";

// Configurar cURL
$ch = curl_init();
$url = 'https://api.alegra.com/api/v1/contacts';

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datosMinimos));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Basic ' . base64_encode($email . ':' . $token)
]);
curl_setopt($ch, CURLOPT_VERBOSE, true);

// Capturar la salida detallada
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

// Ejecutar la solicitud
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

// Obtener información detallada de la solicitud
rewind($verbose);
$verboseLog = stream_get_contents($verbose);
fclose($verbose);

echo "Detalles de la solicitud cURL:\n";
echo $verboseLog . "\n\n";

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
    echo "❌ Error al crear cliente en Alegra. Código: " . $httpCode . "\n";
    
    // Intentar decodificar la respuesta para ver el error específico
    $errorData = json_decode($response, true);
    if ($errorData && isset($errorData['message'])) {
        echo "Mensaje de error: " . $errorData['message'] . "\n";
        
        if (isset($errorData['errors'])) {
            echo "Errores detallados:\n";
            foreach ($errorData['errors'] as $field => $errors) {
                echo "- $field: " . implode(', ', $errors) . "\n";
            }
        }
    }
}

echo "\nProceso completado.\n";
