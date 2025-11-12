<?php

// Script para crear facturas en Alegra
require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Cargar el entorno de Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Configurar log para mostrar en consola
Log::listen(function ($log) {
    echo "[" . $log->level . "] " . $log->message . PHP_EOL;
    if (!empty($log->context)) {
        echo "Contexto: " . json_encode($log->context, JSON_PRETTY_PRINT) . PHP_EOL;
    }
    echo "-------------------------------------------" . PHP_EOL;
});

// Configuración de Alegra
$alegraEmail = config('alegra.user');
$alegraToken = config('alegra.token');
$alegraApiUrl = config('alegra.base_url');

// Autenticación
$auth = base64_encode($alegraEmail . ':' . $alegraToken);

// Cliente HTTP para Alegra
$http = Http::withHeaders([
    'Authorization' => 'Basic ' . $auth,
    'Content-Type' => 'application/json',
    'Accept' => 'application/json'
])->baseUrl($alegraApiUrl);

// Función para crear una factura
function createInvoice($invoiceData, $http) {
    try {
        echo "Datos de factura recibidos: " . json_encode($invoiceData, JSON_PRETTY_PRINT) . PHP_EOL;
        
        // Asegurarse de que el cliente tenga el formato correcto
        if (isset($invoiceData['client']) && isset($invoiceData['client']['id'])) {
            $invoiceData['client']['id'] = intval($invoiceData['client']['id']);
        }
        
        // Asegurarse de que los items tengan el formato correcto
        if (isset($invoiceData['items']) && is_array($invoiceData['items'])) {
            foreach ($invoiceData['items'] as &$item) {
                $item['id'] = intval($item['id']);
                $item['price'] = floatval($item['price']);
                $item['quantity'] = intval($item['quantity']);
            }
        }
        
        // Asegurarse de que el pago tenga el formato correcto
        if (!isset($invoiceData['paymentForm'])) {
            $invoiceData['paymentForm'] = 'CASH';
        }
        
        if (!isset($invoiceData['paymentMethod'])) {
            $invoiceData['paymentMethod'] = 'CASH';
        }
        
        // Formato correcto para el pago según la memoria
        if (!isset($invoiceData['payment']) || !isset($invoiceData['payment']['paymentMethod']) || !isset($invoiceData['payment']['account'])) {
            $invoiceData['payment'] = [
                'paymentMethod' => ['id' => 10],
                'account' => ['id' => 1]
            ];
        }
        
        // Si es factura electrónica, asegurarse de que tenga la numeración correcta
        if (isset($invoiceData['useElectronicInvoice']) && $invoiceData['useElectronicInvoice']) {
            // Obtener numeraciones disponibles
            $numberTemplatesResponse = $http->get('number-templates');
            
            if ($numberTemplatesResponse->successful()) {
                $numberTemplates = $numberTemplatesResponse->json();
                
                // Buscar una numeración electrónica activa
                $electronicTemplate = null;
                foreach ($numberTemplates as $template) {
                    if (isset($template['isElectronic']) && $template['isElectronic'] && isset($template['status']) && $template['status'] === 'active') {
                        $electronicTemplate = $template;
                        break;
                    }
                }
                
                if ($electronicTemplate) {
                    $invoiceData['numberTemplate'] = [
                        'id' => $electronicTemplate['id']
                    ];
                    echo "Usando numeración electrónica: " . $electronicTemplate['id'] . PHP_EOL;
                } else {
                    echo "No se encontró una numeración electrónica activa" . PHP_EOL;
                }
            }
        }
        
        echo "Datos de factura procesados: " . json_encode($invoiceData, JSON_PRETTY_PRINT) . PHP_EOL;
        
        // Crear la factura en Alegra
        $response = $http->post('invoices', $invoiceData);
        
        if ($response->successful()) {
            echo "Factura creada exitosamente: " . $response->json()['id'] . PHP_EOL;
            return [
                'success' => true,
                'data' => $response->json()
            ];
        }
        
        echo "Error al crear factura: " . $response->body() . PHP_EOL;
        return [
            'success' => false,
            'error' => $response->json()['message'] ?? 'Error al crear factura',
            'details' => $response->json()
        ];
    } catch (\Exception $e) {
        echo "Error en el script: " . $e->getMessage() . PHP_EOL;
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Función principal
function main() {
    global $http;
    
    try {
        // Leer datos de factura desde un archivo JSON o argumentos
        $invoiceData = null;
        
        if ($argc > 1) {
            // Leer desde archivo
            $filePath = $argv[1];
            $fileContent = file_get_contents($filePath);
            $invoiceData = json_decode($fileContent, true);
        } else {
            // Leer desde stdin
            $stdin = file_get_contents('php://stdin');
            $invoiceData = json_decode($stdin, true);
        }
        
        if (!$invoiceData) {
            echo "No se proporcionaron datos de factura válidos" . PHP_EOL;
            exit(1);
        }
        
        $result = createInvoice($invoiceData, $http);
        echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
        
        // Salir con código de estado según el resultado
        exit($result['success'] ? 0 : 1);
    } catch (\Exception $e) {
        echo "Error en el script: " . $e->getMessage() . PHP_EOL;
        exit(1);
    }
}

// Ejecutar la función principal si se ejecuta directamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    main();
}
