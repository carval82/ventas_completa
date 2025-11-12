<?php

// Importar las clases necesarias
require_once __DIR__ . '/../vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Producto;
use App\Models\Cliente;
use App\Models\Venta;
use App\Services\AlegraService;
use Illuminate\Support\Facades\Log;

// Aumentar el tiempo máximo de ejecución y memoria
set_time_limit(300);
ini_set('memory_limit', '512M');

// Función para imprimir mensajes con formato
function printSection($title) {
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "  " . $title . "\n";
    echo str_repeat("=", 50) . "\n";
}

printSection("VERIFICACIÓN DE INTEGRACIÓN CON ALEGRA");

// Crear una instancia del servicio Alegra
$alegraService = new AlegraService();

// Probar la conexión
echo "Probando conexión con Alegra...\n";
$resultado = $alegraService->probarConexion();

if ($resultado['success']) {
    echo "✅ Conexión exitosa con Alegra.\n";
} else {
    echo "❌ Error al conectar con Alegra: " . ($resultado['message'] ?? 'Error desconocido') . "\n";
    exit(1);
}

// 1. Verificar numeración electrónica
printSection("VERIFICACIÓN DE NUMERACIÓN ELECTRÓNICA");

echo "Nota: La verificación de numeración electrónica requiere que exista una plantilla con isElectronic=true y status=active.\n";
echo "Esto debe configurarse en la interfaz de Alegra.\n";
echo "Recordatorio: El error 3061 ('La numeración debe estar activa') indica que no hay numeración electrónica activa.\n";

// No podemos acceder directamente a las plantillas de numeración porque el método obtenerCredencialesAlegra() es protegido
echo "Para verificar manualmente en Alegra, ir a Configuración > Facturación electrónica > Numeración\n";

// 2. Verificar sincronización de clientes
printSection("VERIFICACIÓN DE SINCRONIZACIÓN DE CLIENTES");

try {
    // Obtener un cliente local
    $cliente = Cliente::first();
    
    if (!$cliente) {
        echo "❌ No se encontraron clientes en la base de datos local.\n";
    } else {
        echo "Cliente local encontrado: " . $cliente->nombre . " (ID: " . $cliente->id . ")\n";
        
        if ($cliente->id_alegra) {
            echo "✅ Cliente ya sincronizado con Alegra (ID Alegra: " . $cliente->id_alegra . ")\n";
            
            // Verificar formato correcto para envío a Alegra
            echo "Formato correcto para envío a Alegra: { \"client\": { \"id\": " . intval($cliente->id_alegra) . " } }\n";
        } else {
            echo "❌ Cliente no sincronizado con Alegra. Intentando sincronizar...\n";
            
            $resultado = $cliente->syncToAlegra();
            
            if ($resultado['success']) {
                echo "✅ Cliente sincronizado correctamente. ID Alegra: " . $resultado['id_alegra'] . "\n";
            } else {
                echo "❌ Error al sincronizar cliente: " . ($resultado['message'] ?? 'Error desconocido') . "\n";
                if (isset($resultado['error'])) {
                    echo "  Detalles: " . $resultado['error'] . "\n";
                }
            }
        }
    }
} catch (\Exception $e) {
    echo "❌ Excepción al verificar sincronización de clientes: " . $e->getMessage() . "\n";
}

// 3. Verificar sincronización de productos
printSection("VERIFICACIÓN DE SINCRONIZACIÓN DE PRODUCTOS");

try {
    // Obtener un producto local
    $producto = Producto::first();
    
    if (!$producto) {
        echo "❌ No se encontraron productos en la base de datos local.\n";
    } else {
        echo "Producto local encontrado: " . $producto->nombre . " (ID: " . $producto->id . ")\n";
        
        if ($producto->id_alegra) {
            echo "✅ Producto ya sincronizado con Alegra (ID Alegra: " . $producto->id_alegra . ")\n";
        } else {
            echo "❌ Producto no sincronizado con Alegra. Intentando sincronizar...\n";
            
            $resultado = $producto->syncToAlegra();
            
            if ($resultado['success']) {
                echo "✅ Producto sincronizado correctamente. ID Alegra: " . $resultado['id_alegra'] . "\n";
            } else {
                echo "❌ Error al sincronizar producto: " . ($resultado['message'] ?? 'Error desconocido') . "\n";
                if (isset($resultado['error'])) {
                    echo "  Detalles: " . $resultado['error'] . "\n";
                }
            }
        }
    }
} catch (\Exception $e) {
    echo "❌ Excepción al verificar sincronización de productos: " . $e->getMessage() . "\n";
}

// 4. Verificar formato de método de pago
printSection("VERIFICACIÓN DE FORMATO DE MÉTODO DE PAGO");

echo "Formato correcto para método de pago:\n";
echo json_encode([
    "payment" => [
        "paymentMethod" => ["id" => 10],
        "account" => ["id" => 1]
    ]
], JSON_PRETTY_PRINT) . "\n";

// 5. Verificar formato de IVA
printSection("VERIFICACIÓN DE FORMATO DE IVA");

echo "Formatos para envío de IVA a Alegra:\n\n";

echo "1. Usando taxRate a nivel de ítem (formato simple):\n";
echo json_encode([
    "items" => [
        [
            "id" => 123,
            "price" => 10000,
            "quantity" => 1,
            "taxRate" => 19
        ]
    ]
], JSON_PRETTY_PRINT) . "\n\n";

echo "2. Usando tax a nivel de ítem (formato completo):\n";
echo json_encode([
    "items" => [
        [
            "id" => 123,
            "price" => 10000,
            "quantity" => 1,
            "tax" => [
                "id" => 1,
                "name" => "IVA",
                "percentage" => 19,
                "value" => 1900
            ]
        ]
    ]
], JSON_PRETTY_PRINT) . "\n\n";

echo "3. Usando taxes como array a nivel de ítem:\n";
echo json_encode([
    "items" => [
        [
            "id" => 123,
            "price" => 10000,
            "quantity" => 1,
            "taxes" => [
                [
                    "id" => 1,
                    "name" => "IVA",
                    "percentage" => 19,
                    "value" => 1900
                ]
            ]
        ]
    ]
], JSON_PRETTY_PRINT) . "\n\n";

echo "4. Usando totalTaxes a nivel de factura:\n";
echo json_encode([
    "items" => [
        [
            "id" => 123,
            "price" => 10000,
            "quantity" => 1
        ]
    ],
    "totalTaxes" => [
        [
            "id" => 1,
            "name" => "IVA",
            "percentage" => 19,
            "value" => 1900
        ]
    ]
], JSON_PRETTY_PRINT) . "\n";

printSection("RESUMEN DE VERIFICACIÓN");

echo "La integración con Alegra requiere:\n";
echo "1. Clientes y productos sincronizados con sus respectivos id_alegra\n";
echo "2. Formato correcto para cliente: client: { id: intval(id_alegra) }\n";
echo "3. Formato correcto para método de pago: payment: { paymentMethod: { id: 10 }, account: { id: 1 } }\n";
echo "4. Numeración electrónica activa\n";
echo "5. Formato correcto para IVA (se han implementado múltiples opciones)\n\n";

echo "Proceso de verificación completado.\n";
