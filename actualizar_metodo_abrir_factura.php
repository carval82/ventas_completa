<?php
/**
 * Script para implementar directamente la solución de apertura de facturas
 * que simplifica el JSON a solo el campo paymentForm
 */

// Cargar el framework Laravel para tener acceso a las clases
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Log;

// ID de la factura de Alegra (se pasa como argumento)
$idFactura = isset($argv[1]) ? $argv[1] : null;

if (!$idFactura) {
    echo "Error: Debe proporcionar el ID de la factura en Alegra\n";
    echo "Uso: php actualizar_metodo_abrir_factura.php ID_FACTURA_ALEGRA\n";
    exit(1);
}

try {
    // Obtener instancia de AlegraService
    $alegraService = app(\App\Services\AlegraService::class);
    
    // Verificar el estado actual de la factura
    echo "Verificando estado de la factura ID: $idFactura...\n";
    $resultado = $alegraService->obtenerFactura($idFactura);
    
    if (!$resultado['success']) {
        echo "❌ Error al obtener factura: " . $resultado['message'] . "\n";
        exit(1);
    }
    
    echo "Estado actual: " . $resultado['data']['status'] . "\n";
    
    if ($resultado['data']['status'] !== 'draft') {
        echo "⚠️ La factura no está en estado borrador (draft). No se puede abrir.\n";
        exit(1);
    }
    
    // Obtener credenciales
    echo "Obteniendo credenciales...\n";
    $credenciales = $alegraService->obtenerCredencialesAlegra();
    if (!$credenciales['success']) {
        echo "❌ Error al obtener credenciales: " . $credenciales['message'] . "\n";
        exit(1);
    }
    
    $email = $credenciales['email'];
    $token = $credenciales['token'];
    
    // Configurar cURL para abrir la factura
    echo "Intentando abrir la factura con formato simplificado ({paymentForm: 'CASH'})...\n";
    $ch = curl_init();
    $url = "https://api.alegra.com/api/v1/invoices/{$idFactura}/open";
    
    // Usar solo 'paymentForm' que es el campo mínimo requerido
    $datos = json_encode(['paymentForm' => 'CASH']);
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datos);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($email . ':' . $token)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    echo "Código de respuesta HTTP: $httpCode\n";
    
    if ($httpCode >= 200 && $httpCode < 300) {
        echo "✅ ¡Éxito! Factura abierta correctamente.\n";
        
        // Verificar si ahora podemos enviar la factura a DIAN
        echo "\nIntentando enviar factura a DIAN...\n";
        
        $ch = curl_init();
        $url = "https://api.alegra.com/api/v1/invoices/{$idFactura}/stamp";
        
        $datosDian = json_encode([
            'generateStamp' => true,
            'generateQrCode' => true
        ]);
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $datosDian);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($email . ':' . $token)
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        echo "Código de respuesta DIAN: $httpCode\n";
        
        if ($httpCode >= 200 && $httpCode < 300) {
            echo "✅ ¡Éxito! Factura enviada a DIAN correctamente.\n";
            $responseData = json_decode($response, true);
            echo "CUFE: " . ($responseData['cufe'] ?? 'No disponible') . "\n";
            echo "Estado: " . ($responseData['status'] ?? 'No disponible') . "\n";
        } else {
            echo "❌ Error al enviar a DIAN: $httpCode\n";
            echo "Respuesta: $response\n";
        }
    } else {
        echo "❌ Error al abrir factura: $httpCode\n";
        echo "Respuesta: $response\n";
    }
} catch (\Exception $e) {
    echo "❌ Excepción: " . $e->getMessage() . "\n";
}

/**
 * NOTA IMPORTANTE:
 * 
 * Basado en los resultados de este script, debe actualizarse el método abrirFacturaDirecto
 * en el archivo AlegraService.php para usar únicamente el campo 'paymentForm'.
 * 
 * El cambio principal debe ser:
 * 
 * $datos = json_encode([
 *     'paymentForm' => 'CASH'
 * ]);
 * 
 * En lugar de incluir también 'paymentMethod'.
 */
