<?php

// Script para listar las facturas disponibles en Alegra
// Esto nos ayudará a identificar facturas válidas para procesar

// Cargar el framework Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Importar las clases necesarias
use Illuminate\Support\Facades\Log;

// Obtener credenciales de Alegra
function obtenerCredencialesAlegra() {
    try {
        // Intentar obtener las credenciales de la empresa
        $empresa = \App\Models\Empresa::first();
        
        if ($empresa && $empresa->alegra_email && $empresa->alegra_token) {
            // Usar credenciales de la empresa
            $email = $empresa->alegra_email;
            $token = $empresa->alegra_token;
            echo "Usando credenciales de Alegra configuradas en la empresa\n";
        } else {
            // Usar credenciales del archivo .env como respaldo
            $email = config('alegra.user');
            $token = config('alegra.token');
            echo "Usando credenciales de Alegra del archivo .env\n";
        }
        
        if (empty($email) || empty($token)) {
            echo "Error: Credenciales de Alegra vacías\n";
            return null;
        }
        
        return [
            'email' => $email,
            'token' => $token
        ];
    } catch (\Exception $e) {
        echo "Error al obtener credenciales de Alegra: " . $e->getMessage() . "\n";
        return null;
    }
}

// Listar facturas de Alegra
function listarFacturas($credenciales, $estado = null, $limite = 10) {
    echo "Obteniendo lista de facturas de Alegra...\n";
    
    // Configurar cURL
    $ch = curl_init();
    $url = "https://api.alegra.com/api/v1/invoices?limit={$limite}";
    
    // Añadir filtro por estado si se especifica
    if ($estado) {
        $url .= "&status={$estado}";
    }
    
    // Configurar opciones de cURL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($credenciales['email'] . ':' . $credenciales['token'])
    ]);
    
    // Ejecutar la solicitud
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    // Verificar si hubo errores
    if ($httpCode < 200 || $httpCode >= 300) {
        echo "Error al obtener facturas: HTTP {$httpCode}\n";
        echo "Respuesta: {$response}\n";
        echo "Error cURL: {$error}\n";
        return null;
    }
    
    // Procesar la respuesta
    $facturas = json_decode($response, true);
    
    return $facturas;
}

// Proceso principal
echo "=== Listado de Facturas en Alegra ===\n";

// Obtener credenciales
$credenciales = obtenerCredencialesAlegra();
if (!$credenciales) {
    exit(1);
}

// Listar facturas en estado borrador
echo "\n=== Facturas en estado BORRADOR ===\n";
$facturasBorrador = listarFacturas($credenciales, 'draft', 20);

if ($facturasBorrador) {
    echo "Se encontraron " . count($facturasBorrador) . " facturas en estado borrador:\n";
    
    echo str_pad("ID", 10) . str_pad("Número", 20) . str_pad("Fecha", 15) . str_pad("Cliente", 30) . str_pad("Total", 15) . "Estado\n";
    echo str_repeat("-", 100) . "\n";
    
    foreach ($facturasBorrador as $factura) {
        echo str_pad($factura['id'], 10) . 
             str_pad($factura['numberTemplate']['fullNumber'] ?? 'N/A', 20) . 
             str_pad($factura['date'], 15) . 
             str_pad(substr($factura['client']['name'], 0, 28), 30) . 
             str_pad(number_format($factura['total'], 2), 15) . 
             $factura['status'] . "\n";
    }
} else {
    echo "No se encontraron facturas en estado borrador o hubo un error al obtenerlas.\n";
}

// Listar facturas en estado abierto
echo "\n=== Facturas en estado ABIERTO ===\n";
$facturasAbiertas = listarFacturas($credenciales, 'open', 20);

if ($facturasAbiertas) {
    echo "Se encontraron " . count($facturasAbiertas) . " facturas en estado abierto:\n";
    
    echo str_pad("ID", 10) . str_pad("Número", 20) . str_pad("Fecha", 15) . str_pad("Cliente", 30) . str_pad("Total", 15) . "Estado\n";
    echo str_repeat("-", 100) . "\n";
    
    foreach ($facturasAbiertas as $factura) {
        echo str_pad($factura['id'], 10) . 
             str_pad($factura['numberTemplate']['fullNumber'] ?? 'N/A', 20) . 
             str_pad($factura['date'], 15) . 
             str_pad(substr($factura['client']['name'], 0, 28), 30) . 
             str_pad(number_format($factura['total'], 2), 15) . 
             $factura['status'] . "\n";
    }
} else {
    echo "No se encontraron facturas en estado abierto o hubo un error al obtenerlas.\n";
}

// Listar últimas facturas (cualquier estado)
echo "\n=== Últimas Facturas (Cualquier Estado) ===\n";
$ultimasFacturas = listarFacturas($credenciales, null, 20);

if ($ultimasFacturas) {
    echo "Se encontraron " . count($ultimasFacturas) . " facturas:\n";
    
    echo str_pad("ID", 10) . str_pad("Número", 20) . str_pad("Fecha", 15) . str_pad("Cliente", 30) . str_pad("Total", 15) . "Estado\n";
    echo str_repeat("-", 100) . "\n";
    
    foreach ($ultimasFacturas as $factura) {
        $esElectronica = isset($factura['stamp']) && isset($factura['stamp']['cufe']) ? " (Electrónica)" : "";
        
        echo str_pad($factura['id'], 10) . 
             str_pad($factura['numberTemplate']['fullNumber'] ?? 'N/A', 20) . 
             str_pad($factura['date'], 15) . 
             str_pad(substr($factura['client']['name'], 0, 28), 30) . 
             str_pad(number_format($factura['total'], 2), 15) . 
             $factura['status'] . $esElectronica . "\n";
    }
} else {
    echo "No se encontraron facturas o hubo un error al obtenerlas.\n";
}

echo "\n=== Fin del Listado ===\n";
echo "Utilice el ID de alguna factura en estado borrador para probar el script de envío a la DIAN.\n";
