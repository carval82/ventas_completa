<?php

// Script para listar facturas por estado
// Cargar el framework Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Obtener credenciales
$empresa = \App\Models\Empresa::first();
if ($empresa && $empresa->alegra_email && $empresa->alegra_token) {
    $email = $empresa->alegra_email;
    $token = $empresa->alegra_token;
    echo "Usando credenciales de la empresa\n";
} else {
    $email = config('alegra.user');
    $token = config('alegra.token');
    echo "Usando credenciales del archivo .env\n";
}

// Estado a buscar (opcional)
$estado = isset($argv[1]) ? $argv[1] : null;

// Obtener facturas
echo "Obteniendo facturas de Alegra...\n";
$ch = curl_init();
$url = "https://api.alegra.com/api/v1/invoices?start=0&limit=20";
if ($estado) {
    $url .= "&status=" . urlencode($estado);
}

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Basic ' . base64_encode($email . ':' . $token)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    $facturas = json_decode($response, true);
    
    echo "\n=== FACTURAS ENCONTRADAS ===\n";
    echo "Total: " . count($facturas) . "\n\n";
    
    if (count($facturas) > 0) {
        echo str_pad("ID", 6) . " | " . 
             str_pad("Número", 15) . " | " . 
             str_pad("Estado", 10) . " | " . 
             str_pad("Fecha", 12) . " | " . 
             str_pad("Cliente", 30) . " | " . 
             str_pad("Total", 12) . " | " . 
             "Electrónica\n";
        
        echo str_repeat("-", 100) . "\n";
        
        foreach ($facturas as $factura) {
            $esElectronica = isset($factura['numberTemplate']['isElectronic']) && $factura['numberTemplate']['isElectronic'] ? "Sí" : "No";
            $tieneCufe = isset($factura['stamp']) && isset($factura['stamp']['cufe']) ? "✅" : "";
            
            echo str_pad($factura['id'], 6) . " | " . 
                 str_pad($factura['numberTemplate']['fullNumber'] ?? 'N/A', 15) . " | " . 
                 str_pad($factura['status'], 10) . " | " . 
                 str_pad($factura['date'], 12) . " | " . 
                 str_pad(substr($factura['client']['name'], 0, 30), 30) . " | " . 
                 str_pad(number_format($factura['total'], 0, ',', '.'), 12) . " | " . 
                 $esElectronica . " " . $tieneCufe . "\n";
        }
    } else {
        echo "No se encontraron facturas" . ($estado ? " con estado '$estado'" : "") . "\n";
    }
    
} else {
    echo "Error al obtener facturas: HTTP {$httpCode}\n";
    echo "Respuesta: {$response}\n";
}
