<?php

// Importar las clases necesarias
require_once __DIR__ . '/../vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Empresa;

echo "=== VERIFICACIÓN DE CREDENCIALES DE ALEGRA ===\n\n";

// Obtener credenciales de Alegra
$empresa = Empresa::first();

if (!$empresa) {
    echo "❌ No se encontró ninguna empresa en la base de datos.\n";
    exit(1);
}

echo "Empresa encontrada: " . $empresa->nombre . "\n";

if (empty($empresa->alegra_email) || empty($empresa->alegra_token)) {
    echo "❌ La empresa no tiene configuradas las credenciales de Alegra.\n";
    exit(1);
}

$email = $empresa->alegra_email;
$token = $empresa->alegra_token;

echo "Credenciales de Alegra encontradas:\n";
echo "Email: " . $email . "\n";
echo "Token: " . substr($token, 0, 3) . '...' . substr($token, -3) . "\n\n";

// Probar la conexión con Alegra
echo "Probando conexión con Alegra...\n";

// Configurar cURL
$ch = curl_init();
$url = 'https://api.alegra.com/api/v1/company';

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
    echo "✅ Conexión exitosa con Alegra.\n";
    $data = json_decode($response, true);
    
    echo "\nInformación de la empresa en Alegra:\n";
    echo "Nombre: " . ($data['name'] ?? 'No disponible') . "\n";
    echo "Identificación: " . ($data['identification'] ?? 'No disponible') . "\n";
    echo "Teléfono: " . ($data['phone'] ?? 'No disponible') . "\n";
    echo "Dirección: " . ($data['address']['address'] ?? 'No disponible') . "\n";
    
    // Verificar si la empresa tiene numeración electrónica activa
    echo "\nVerificando numeración electrónica...\n";
    
    $ch = curl_init();
    $url = 'https://api.alegra.com/api/v1/number-templates';
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($email . ':' . $token)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        $templates = json_decode($response, true);
        
        $electronicTemplates = array_filter($templates, function($template) {
            return isset($template['isElectronic']) && $template['isElectronic'] === true;
        });
        
        $activeElectronicTemplates = array_filter($electronicTemplates, function($template) {
            return isset($template['status']) && $template['status'] === 'active';
        });
        
        echo "Total de plantillas de numeración: " . count($templates) . "\n";
        echo "Plantillas electrónicas: " . count($electronicTemplates) . "\n";
        echo "Plantillas electrónicas activas: " . count($activeElectronicTemplates) . "\n";
        
        if (count($activeElectronicTemplates) > 0) {
            echo "✅ La empresa tiene numeración electrónica activa.\n";
            
            echo "\nPlantillas electrónicas activas:\n";
            foreach ($activeElectronicTemplates as $template) {
                echo "- ID: " . $template['id'] . ", Nombre: " . $template['name'] . "\n";
            }
        } else {
            echo "❌ La empresa no tiene numeración electrónica activa.\n";
            echo "⚠️ Esto causará el error 3061: 'La numeración debe estar activa'.\n";
        }
    } else {
        echo "❌ Error al obtener plantillas de numeración: " . $httpCode . "\n";
    }
} else {
    echo "❌ Error al conectar con Alegra. Código: " . $httpCode . "\n";
    echo "Respuesta: " . $response . "\n";
}

echo "\nVerificación de credenciales completada.\n";
