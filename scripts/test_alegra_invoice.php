<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Log;
use App\Services\AlegraService;
use App\Models\Cliente;
use App\Models\Producto;

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Inicializar el servicio de Alegra
$alegraService = new AlegraService();

// Función para mostrar resultados de manera formateada
function printResult($title, $result) {
    echo "\n===== $title =====\n";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "\n\n";
}

// Probar conexión
$testConnection = $alegraService->testConnection();
printResult("Test de Conexión", $testConnection);

// Obtener cliente para prueba
$cliente = Cliente::first();
if (!$cliente) {
    die("No se encontró ningún cliente para la prueba\n");
}

// Asegurar que el cliente esté sincronizado con Alegra
if (!$cliente->id_alegra) {
    echo "Sincronizando cliente con Alegra...\n";
    $syncResult = $cliente->syncToAlegra();
    printResult("Sincronización de Cliente", $syncResult);
    
    // Recargar cliente después de sincronizar
    $cliente = Cliente::find($cliente->id);
}

// Obtener producto para prueba
$producto = Producto::first();
if (!$producto) {
    die("No se encontró ningún producto para la prueba\n");
}

// Verificar unidad de medida del producto
echo "Unidad de medida del producto: " . ($producto->unidad_medida ?: "No definida") . "\n";

// Asegurar que el producto esté sincronizado con Alegra
if (!$producto->id_alegra) {
    echo "Sincronizando producto con Alegra...\n";
    $syncResult = $producto->syncToAlegra();
    printResult("Sincronización de Producto", $syncResult);
    
    // Recargar producto después de sincronizar
    $producto = Producto::find($producto->id);
}

// Preparar datos para la factura
$invoiceData = [
    'client' => [
        'id' => (int)$cliente->id_alegra
    ],
    'items' => [
        [
            'id' => (int)$producto->id_alegra,
            'price' => (float)$producto->precio_venta,
            'quantity' => 1.0
        ]
    ],
    'date' => date('Y-m-d'),
    'dueDate' => date('Y-m-d'),
    'paymentForm' => 'CASH',
    'paymentMethod' => 'CASH',
    'payment' => [
        'paymentMethod' => ['id' => 10],  // 10 = Efectivo según DIAN
        'account' => ['id' => 1]          // Cuenta por defecto
    ],
    'numberTemplate' => [
        'id' => 19  // Usar la plantilla por defecto
    ]
];

// Mostrar datos de la factura
printResult("Datos de la Factura", $invoiceData);

// Crear factura usando el método directo de AlegraService
echo "Creando factura en Alegra...\n";
$createResult = $alegraService->crearFactura($invoiceData);
printResult("Resultado de Creación de Factura", $createResult);

// Usar Python para crear la factura (similar a como lo hace el controlador)
echo "Creando factura usando Python...\n";

// Guardar los datos de la factura en un archivo temporal
$tempJsonFile = __DIR__ . '/temp_invoice_data.json';
file_put_contents($tempJsonFile, json_encode($invoiceData));

// Crear un script Python temporal para la creación de la factura
$pythonScript = <<<'PYTHON'
#!/usr/bin/env python
# -*- coding: utf-8 -*-

import json
import sys
import os
import traceback
from scripts.alegra_integration import create_invoice

def main():
    """Crea una factura en Alegra desde un archivo JSON."""
    try:
        with open(sys.argv[1], 'r') as f:
            invoice_data = json.load(f)
        
        success, response = create_invoice(invoice_data)
        print(json.dumps({
            "success": success,
            "data": response
        }))
    except Exception as e:
        print(json.dumps({
            "success": False,
            "error": str(e),
            "traceback": traceback.format_exc()
        }))

if __name__ == "__main__":
    main()
PYTHON;

$pythonScriptFile = __DIR__ . '/temp_create_invoice.py';
file_put_contents($pythonScriptFile, $pythonScript);

// Ejecutar el script Python
$command = 'python ' . escapeshellarg($pythonScriptFile) . ' ' . escapeshellarg($tempJsonFile);
$output = [];
$returnCode = 0;

echo "Ejecutando comando: $command\n";
exec($command, $output, $returnCode);

// Mostrar resultado
echo "Código de retorno: $returnCode\n";
$outputString = implode("\n", $output);
echo "Salida:\n$outputString\n";

// Eliminar archivos temporales
unlink($tempJsonFile);
unlink($pythonScriptFile);

echo "\nPrueba completada.\n";
