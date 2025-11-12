<?php

// Importar las clases necesarias
require_once __DIR__ . '/../vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Empresa;

echo "=== LISTAR CLIENTES DE ALEGRA ===\n\n";

// Obtener credenciales de Alegra
$empresa = Empresa::first();

if (!$empresa || empty($empresa->alegra_email) || empty($empresa->alegra_token)) {
    echo "❌ No se encontraron credenciales de Alegra en la empresa.\n";
    exit(1);
}

$email = $empresa->alegra_email;
$token = $empresa->alegra_token;

echo "Credenciales de Alegra obtenidas correctamente.\n\n";

// Configurar cURL para obtener clientes
$ch = curl_init();
$url = 'https://api.alegra.com/api/v1/contacts?type=client&limit=10';

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
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

if ($httpCode >= 200 && $httpCode < 300) {
    $clientes = json_decode($response, true);
    
    echo "Se encontraron " . count($clientes) . " clientes en Alegra.\n\n";
    
    if (!empty($clientes)) {
        echo "Listado de clientes en Alegra:\n";
        echo str_repeat('-', 80) . "\n";
        echo sprintf("%-5s | %-30s | %-15s | %-25s\n", "ID", "Nombre", "Identificación", "Email");
        echo str_repeat('-', 80) . "\n";
        
        foreach ($clientes as $cliente) {
            echo sprintf("%-5s | %-30s | %-15s | %-25s\n", 
                $cliente['id'], 
                substr($cliente['name'], 0, 30), 
                $cliente['identification'] ?? 'N/A', 
                substr($cliente['email'] ?? 'N/A', 0, 25)
            );
        }
        
        echo str_repeat('-', 80) . "\n\n";
        
        // Buscar si existe un cliente con identificación 1234567890
        $clienteEncontrado = false;
        
        foreach ($clientes as $cliente) {
            if (isset($cliente['identification']) && $cliente['identification'] == '1234567890') {
                $clienteEncontrado = true;
                
                echo "✅ Se encontró un cliente con identificación 1234567890 en Alegra:\n";
                echo "ID: " . $cliente['id'] . "\n";
                echo "Nombre: " . $cliente['name'] . "\n";
                echo "Identificación: " . $cliente['identification'] . "\n";
                echo "Email: " . ($cliente['email'] ?? 'N/A') . "\n";
                
                // Actualizar el cliente local con este ID
                $clienteLocal = \App\Models\Cliente::where('cedula', '1234567890')->first();
                
                if ($clienteLocal) {
                    $clienteLocal->id_alegra = $cliente['id'];
                    $clienteLocal->save();
                    
                    echo "\n✅ Cliente local actualizado con ID de Alegra: " . $clienteLocal->id_alegra . "\n";
                }
                
                break;
            }
        }
        
        if (!$clienteEncontrado) {
            echo "❌ No se encontró ningún cliente con identificación 1234567890 en Alegra.\n";
        }
    } else {
        echo "No se encontraron clientes en Alegra.\n";
    }
} else {
    echo "❌ Error al obtener clientes de Alegra.\n";
    echo "Respuesta: " . $response . "\n";
}

echo "\nListado completado.\n";
