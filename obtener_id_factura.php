<?php
/**
 * Script para obtener IDs de facturas de Alegra
 */

// Cargar el entorno de Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Venta;
use App\Services\AlegraService;
use App\Models\Empresa;
use Illuminate\Support\Facades\Log;

// Obtener las últimas 5 ventas con ID de factura de Alegra
$ventas = Venta::whereNotNull('alegra_id')
    ->orderBy('created_at', 'desc')
    ->take(5)
    ->get(['id', 'alegra_id', 'created_at']);

echo "Últimas 5 ventas con ID de factura en Alegra:\n";
echo "---------------------------------------------\n";

if ($ventas->isEmpty()) {
    echo "No se encontraron ventas con ID de factura de Alegra.\n";
} else {
    foreach ($ventas as $venta) {
        echo "ID Venta: {$venta->id}, ID Factura Alegra: {$venta->alegra_id}, Fecha: {$venta->created_at}\n";
    }
}

// Inicializar el servicio de Alegra
$alegraService = new AlegraService();

// Verificar el estado de cada factura
echo "\nVerificando estado de las facturas:\n";
echo "-------------------------------------\n";

// Función auxiliar para obtener credenciales de Alegra
function obtenerCredencialesAlegra() {
    $empresa = Empresa::first();
    
    if ($empresa && !empty($empresa->alegra_email) && !empty($empresa->alegra_token)) {
        return [
            'success' => true,
            'email' => $empresa->alegra_email,
            'token' => $empresa->alegra_token
        ];
    }
    
    return [
        'success' => false,
        'mensaje' => 'No se encontraron credenciales de Alegra'
    ];
}

// Función auxiliar para consultar el estado de una factura usando cURL
function consultarEstadoFactura($idFactura) {
    try {
        // Obtener credenciales
        $credenciales = obtenerCredencialesAlegra();
        if (!$credenciales['success']) {
            return $credenciales;
        }
        
        $email = $credenciales['email'];
        $token = $credenciales['token'];
        
        // Configurar cURL
        $ch = curl_init();
        $url = "https://api.alegra.com/api/v1/invoices/{$idFactura}";
        
        // Configurar opciones de cURL
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
        
        // Procesar la respuesta
        if ($httpCode >= 200 && $httpCode < 300) {
            $data = json_decode($response, true);
            return [
                'success' => true,
                'data' => $data
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Error al consultar la factura',
            'error' => $response,
            'http_code' => $httpCode
        ];
    } catch (\Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

foreach ($ventas as $venta) {
    try {
        // Consultar el estado de la factura usando cURL
        $facturaResponse = consultarEstadoFactura($venta->alegra_id);
        
        if ($facturaResponse['success']) {
            $estado = $facturaResponse['data']['status'] ?? 'desconocido';
            echo "ID Factura Alegra: {$venta->alegra_id}, Estado: {$estado}\n";
        } else {
            echo "ID Factura Alegra: {$venta->alegra_id}, Error al obtener estado: " . 
                 ($facturaResponse['error'] ?? $facturaResponse['message'] ?? 'Error desconocido') . "\n";
        }
    } catch (Exception $e) {
        echo "ID Factura Alegra: {$venta->alegra_id}, Excepción: " . $e->getMessage() . "\n";
    }
}

echo "\nSugerencia: Use el ID de una factura en estado 'draft' para probar la solución.\n";
echo "Comando: php test_emitir_factura_dian.php [ID_FACTURA_ALEGRA]\n";
