<?php

// Script para probar la integración directa con Alegra utilizando Python
require __DIR__ . '/../vendor/autoload.php';

// Cargar el entorno de Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

/**
 * Ejecuta un comando Python y devuelve la salida
 * @param string $script Nombre del script Python
 * @param array $args Argumentos para el script
 * @return array [success, output]
 */
function runPythonScript($script, $args = []) {
    $command = 'python ' . escapeshellarg($script);
    
    if (!empty($args)) {
        $command .= ' ' . implode(' ', array_map('escapeshellarg', $args));
    }
    
    $output = [];
    $returnCode = 0;
    
    exec($command, $output, $returnCode);
    
    return [
        'success' => $returnCode === 0,
        'output' => implode("\n", $output)
    ];
}

// Crear un script Python temporal para la prueba
$pythonScript = <<<'PYTHON'
#!/usr/bin/env python
# -*- coding: utf-8 -*-

import json
import sys
from alegra_integration import (
    test_connection,
    get_clients,
    get_products,
    get_invoice_templates,
    prepare_invoice,
    create_invoice
)

def main():
    """Prueba directa de integración con Alegra."""
    action = sys.argv[1] if len(sys.argv) > 1 else "test"
    
    if action == "test":
        success, response = test_connection()
        print(json.dumps({
            "success": success,
            "data": response
        }))
    
    elif action == "clients":
        success, clients = get_clients()
        print(json.dumps({
            "success": success,
            "data": clients
        }))
    
    elif action == "products":
        success, products = get_products()
        print(json.dumps({
            "success": success,
            "data": products
        }))
    
    elif action == "templates":
        success, template = get_invoice_templates()
        print(json.dumps({
            "success": success,
            "data": template
        }))
    
    elif action == "create_invoice":
        if len(sys.argv) < 3:
            print(json.dumps({
                "success": False,
                "error": "No se proporcionaron datos para la factura"
            }))
            return
        
        invoice_data = json.loads(sys.argv[2])
        success, response = create_invoice(invoice_data)
        print(json.dumps({
            "success": success,
            "data": response
        }))

if __name__ == "__main__":
    main()
PYTHON;

file_put_contents('temp_alegra_script.py', $pythonScript);

// Paso 1: Probar la conexión con Alegra
echo "=== Probando conexión con Alegra ===\n";
$result = runPythonScript('temp_alegra_script.py', ['test']);
$testData = json_decode($result['output'], true);

if (!$result['success'] || !isset($testData['success']) || !$testData['success']) {
    echo "Error al conectar con Alegra: " . (isset($testData['error']) ? $testData['error'] : "Error desconocido") . "\n";
    exit(1);
}

echo "Conexión exitosa con Alegra\n";

// Paso 2: Obtener clientes de Alegra
echo "\n=== Obteniendo clientes de Alegra ===\n";
$result = runPythonScript('temp_alegra_script.py', ['clients']);
$clientsData = json_decode($result['output'], true);

if (!$result['success'] || !isset($clientsData['success']) || !$clientsData['success']) {
    echo "Error al obtener clientes: " . (isset($clientsData['error']) ? $clientsData['error'] : "Error desconocido") . "\n";
    exit(1);
}

$clients = isset($clientsData['data']) ? $clientsData['data'] : [];
echo "Se encontraron " . count($clients) . " clientes en Alegra:\n";

foreach (array_slice($clients, 0, 5) as $index => $client) {
    $identification = isset($client['identification']) ? $client['identification'] : 'N/A';
    echo "{$index}. ID: {$client['id']} - Nombre: {$client['name']} - Identificación: {$identification}\n";
}

// Seleccionar el primer cliente para la prueba
if (count($clients) > 0) {
    $selectedClient = $clients[0];
    echo "\nSeleccionando cliente para prueba: ID {$selectedClient['id']} - {$selectedClient['name']}\n";
    
    // Paso 3: Obtener productos de Alegra
    echo "\n=== Obteniendo productos de Alegra ===\n";
    $result = runPythonScript('temp_alegra_script.py', ['products']);
    $productsData = json_decode($result['output'], true);
    
    if (!$result['success'] || !isset($productsData['success']) || !$productsData['success']) {
        echo "Error al obtener productos: " . (isset($productsData['error']) ? $productsData['error'] : "Error desconocido") . "\n";
        exit(1);
    }
    
    $products = isset($productsData['data']) ? $productsData['data'] : [];
    echo "Se encontraron " . count($products) . " productos en Alegra:\n";
    
    foreach (array_slice($products, 0, 5) as $index => $product) {
        $price = "N/A";
        if (isset($product['price']) && is_array($product['price']) && !empty($product['price'])) {
            $priceObj = $product['price'][0];
            if (isset($priceObj['price'])) {
                $price = $priceObj['price'];
            }
        }
        echo "{$index}. ID: {$product['id']} - Nombre: {$product['name']} - Precio: {$price}\n";
    }
    
    // Seleccionar el primer producto para la prueba
    if (count($products) > 0) {
        $selectedProduct = $products[0];
        echo "\nSeleccionando producto para prueba: ID {$selectedProduct['id']} - {$selectedProduct['name']}\n";
        
        // Extraer el precio del producto correctamente
        $productPrice = 1000; // Valor por defecto
        if (isset($selectedProduct['price']) && is_array($selectedProduct['price']) && !empty($selectedProduct['price'])) {
            $priceObj = $selectedProduct['price'][0];
            if (isset($priceObj['price'])) {
                $productPrice = floatval($priceObj['price']);
            }
        }
        
        // Paso 4: Obtener plantillas de factura
        echo "\n=== Obteniendo plantillas de factura ===\n";
        $result = runPythonScript('temp_alegra_script.py', ['templates']);
        $templateData = json_decode($result['output'], true);
        
        if (!$result['success'] || !isset($templateData['success']) || !$templateData['success']) {
            echo "Error al obtener plantillas: " . (isset($templateData['error']) ? $templateData['error'] : "Error desconocido") . "\n";
            exit(1);
        }
        
        $template = isset($templateData['data']) ? $templateData['data'] : null;
        
        if (is_array($template) && isset($template['id'])) {
            $isElectronic = $template['isElectronic'] ? 'Sí' : 'No';
            $status = isset($template['status']) ? $template['status'] : 'Desconocido';
            echo "Plantilla de factura electrónica encontrada:\n";
            echo "ID: {$template['id']} - Nombre: {$template['name']} - Electrónica: {$isElectronic} - Estado: {$status}\n";
            
            // Paso 5: Crear factura con los datos obtenidos
            echo "\n=== Creando factura en Alegra ===\n";
            
            $invoiceData = [
                'client' => [
                    'id' => intval($selectedClient['id'])
                ],
                'items' => [
                    [
                        'id' => intval($selectedProduct['id']),
                        'price' => $productPrice,
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
                    'id' => intval($template['id'])
                ]
            ];
            
            echo "Datos de factura a enviar:\n";
            echo json_encode($invoiceData, JSON_PRETTY_PRINT) . "\n\n";
            
            // Guardar los datos de la factura en un archivo temporal
            $tempJsonFile = 'temp_invoice_data.json';
            file_put_contents($tempJsonFile, json_encode($invoiceData));
            
            // Modificar el script Python para leer desde un archivo
            $pythonInvoiceScript = <<<'PYTHON'
#!/usr/bin/env python
# -*- coding: utf-8 -*-

import json
import sys
from alegra_integration import create_invoice

def main():
    """Crea una factura en Alegra desde un archivo JSON."""
    try:
        with open('temp_invoice_data.json', 'r') as f:
            invoice_data = json.load(f)
        
        success, response = create_invoice(invoice_data)
        print(json.dumps({
            "success": success,
            "data": response
        }))
    except Exception as e:
        print(json.dumps({
            "success": False,
            "error": str(e)
        }))

if __name__ == "__main__":
    main()
PYTHON;

            file_put_contents('temp_create_invoice.py', $pythonInvoiceScript);
            
            // Ejecutar el script Python para crear la factura
            $result = runPythonScript('temp_create_invoice.py');
            $invoiceResult = json_decode($result['output'], true);
            
            // Eliminar archivos temporales
            unlink($tempJsonFile);
            unlink('temp_create_invoice.py');
            
            if (!$result['success'] || !isset($invoiceResult['success']) || !$invoiceResult['success']) {
                echo "Error al crear factura: " . (isset($invoiceResult['error']) ? $invoiceResult['error'] : "Error desconocido") . "\n";
                exit(1);
            }
            
            echo "¡Éxito! Factura creada correctamente:\n";
            echo json_encode($invoiceResult['data'], JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "No se encontró una plantilla de factura válida.\n";
        }
    } else {
        echo "No se encontraron productos en Alegra. No se puede crear la factura.\n";
    }
} else {
    echo "No se encontraron clientes en Alegra. No se puede crear la factura.\n";
}

// Eliminar el script temporal
unlink('temp_alegra_script.py');
