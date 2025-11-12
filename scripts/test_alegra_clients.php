<?php

// Script para obtener clientes de Alegra y probar la creación de facturas
require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Cargar el entorno de Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Paso 1: Obtener clientes de Alegra
echo "=== Obteniendo clientes de Alegra ===\n";
try {
    $response = Http::get('http://localhost:8001/clients');
    
    if ($response->successful()) {
        $clients = $response->json()['data'] ?? [];
        echo "Se encontraron " . count($clients) . " clientes en Alegra:\n";
        
        foreach ($clients as $index => $client) {
            echo "{$index}. ID: {$client['id']} - Nombre: {$client['name']} - Identificación: {$client['identification']}\n";
        }
        
        // Seleccionar el primer cliente para la prueba
        if (count($clients) > 0) {
            $selectedClient = $clients[0];
            echo "\nSeleccionando cliente para prueba: ID {$selectedClient['id']} - {$selectedClient['name']}\n";
            
            // Paso 2: Obtener productos de Alegra
            echo "\n=== Obteniendo productos de Alegra ===\n";
            $productsResponse = Http::get('http://localhost:8001/products');
            
            if ($productsResponse->successful()) {
                $products = $productsResponse->json()['data'] ?? [];
                echo "Se encontraron " . count($products) . " productos en Alegra:\n";
                
                foreach ($products as $index => $product) {
                    echo "{$index}. ID: {$product['id']} - Nombre: {$product['name']} - Precio: {$product['price']}\n";
                }
                
                // Seleccionar el primer producto para la prueba
                if (count($products) > 0) {
                    $selectedProduct = $products[0];
                    echo "\nSeleccionando producto para prueba: ID {$selectedProduct['id']} - {$selectedProduct['name']}\n";
                    
                    // Paso 3: Obtener plantillas de factura
                    echo "\n=== Obteniendo plantillas de factura de Alegra ===\n";
                    $templatesResponse = Http::get('http://localhost:8001/templates');
                    
                    if ($templatesResponse->successful()) {
                        $template = $templatesResponse->json()['data'] ?? null;
                        
                        if (is_array($template) && isset($template['id'])) {
                            echo "Plantilla de factura electrónica encontrada:\n";
                            $isElectronic = $template['isElectronic'] ? 'Sí' : 'No';
                            $status = $template['status'] ?? 'Desconocido';
                            echo "ID: {$template['id']} - Nombre: {$template['name']} - Electrónica: {$isElectronic} - Estado: {$status}\n";
                            
                            $electronicTemplate = $template;
                        } else {
                            echo "No se encontró una plantilla de factura válida.\n";
                            $electronicTemplate = null;
                        }
                        
                        if ($electronicTemplate) {
                            echo "\nSeleccionando plantilla electrónica: ID {$electronicTemplate['id']} - {$electronicTemplate['name']}\n";
                            
                            // Paso 4: Crear factura con los datos obtenidos
                            echo "\n=== Creando factura en Alegra ===\n";
                            
                            $invoiceData = [
                                'client' => [
                                    'id' => intval($selectedClient['id'])
                                ],
                                'items' => [
                                    [
                                        'id' => intval($selectedProduct['id']),
                                        'price' => floatval($selectedProduct['price']),
                                        'quantity' => 1
                                    ]
                                ],
                                'date' => date('Y-m-d'),
                                'dueDate' => date('Y-m-d'),
                                'paymentForm' => 'CASH',
                                'paymentMethod' => 'CASH',
                                'payment' => [
                                    'paymentMethod' => ['id' => 10],
                                    'account' => ['id' => 1]
                                ],
                                'numberTemplate' => [
                                    'id' => intval($electronicTemplate['id'])
                                ]
                            ];
                            
                            echo "Datos de factura a enviar:\n";
                            echo json_encode($invoiceData, JSON_PRETTY_PRINT) . "\n\n";
                            
                            $invoiceResponse = Http::post('http://localhost:8001/invoices', $invoiceData);
                            
                            echo "Código de respuesta: " . $invoiceResponse->status() . "\n";
                            echo "Respuesta:\n";
                            echo json_encode($invoiceResponse->json(), JSON_PRETTY_PRINT) . "\n";
                            
                            if ($invoiceResponse->successful()) {
                                echo "\n¡Éxito! La factura se creó correctamente.\n";
                            } else {
                                echo "\nError al crear la factura. Revisa la respuesta para más detalles.\n";
                            }
                        } else {
                            echo "\nNo se encontró una plantilla electrónica activa. No se puede crear la factura.\n";
                        }
                    } else {
                        echo "Error al obtener plantillas de factura: " . $templatesResponse->status() . "\n";
                        echo json_encode($templatesResponse->json(), JSON_PRETTY_PRINT) . "\n";
                    }
                } else {
                    echo "No se encontraron productos en Alegra. No se puede crear la factura.\n";
                }
            } else {
                echo "Error al obtener productos: " . $productsResponse->status() . "\n";
                echo json_encode($productsResponse->json(), JSON_PRETTY_PRINT) . "\n";
            }
        } else {
            echo "No se encontraron clientes en Alegra. No se puede crear la factura.\n";
        }
    } else {
        echo "Error al obtener clientes: " . $response->status() . "\n";
        echo json_encode($response->json(), JSON_PRETTY_PRINT) . "\n";
    }
} catch (\Exception $e) {
    echo "Error al conectar con el servicio Python: " . $e->getMessage() . "\n";
}
