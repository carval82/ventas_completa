<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Cargar el entorno de Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Empresa;
use App\Services\AlegraService;
use Illuminate\Support\Facades\Log;

// Configurar logging para ver resultados detallados
Log::info('Iniciando verificación de formato IVA para Alegra');

// Obtener la empresa para verificar el régimen tributario y porcentaje de IVA
$empresa = Empresa::first();

if (!$empresa) {
    echo "Error: No hay empresa configurada en el sistema.\n";
    exit(1);
}

echo "Empresa: {$empresa->nombre}\n";
echo "Régimen tributario: {$empresa->regimen_tributario}\n";
echo "Porcentaje IVA: {$empresa->porcentaje_iva}%\n";

// Verificar si es responsable de IVA
$esResponsableIVA = $empresa->regimen_tributario === 'responsable_iva';
$porcentajeIVA = floatval($empresa->porcentaje_iva ?? 19);

echo "Es responsable de IVA: " . ($esResponsableIVA ? 'Sí' : 'No') . "\n";

// Crear un servicio de Alegra
$alegraService = new AlegraService();

// Probar la conexión con Alegra
echo "Probando conexión con Alegra...\n";
$conexion = $alegraService->probarConexion();

if (!$conexion['success']) {
    echo "Error de conexión con Alegra: " . ($conexion['message'] ?? 'Error desconocido') . "\n";
    exit(1);
}

echo "Conexión exitosa con Alegra.\n";

// Crear datos de prueba para simular una factura
$cantidad = 2;
$precioUnitario = 50000;
$subtotal = $cantidad * $precioUnitario;
$impuesto = $esResponsableIVA ? round($subtotal * ($porcentajeIVA / 100), 2) : 0;
$total = $subtotal + $impuesto;

echo "Datos de la factura de prueba:\n";
echo "Cantidad: {$cantidad}\n";
echo "Precio unitario: {$precioUnitario}\n";
echo "Subtotal: {$subtotal}\n";
echo "Impuesto ({$porcentajeIVA}%): {$impuesto}\n";
echo "Total: {$total}\n";

// Preparar los datos de la factura con los diferentes enfoques para el IVA
$items = [];
$totalImpuestos = [];

// Datos básicos del ítem (simulando un producto)
$itemData = [
    'id' => 1, // ID simulado para prueba
    'price' => floatval($precioUnitario),
    'quantity' => floatval($cantidad)
];

// Si es responsable de IVA, agregar la información de impuestos
if ($esResponsableIVA) {
    // 1. Usar taxRate (el enfoque más simple y directo)
    $itemData['taxRate'] = $porcentajeIVA;
    
    // 2. Usar el campo tax con el formato específico requerido por Alegra
    $itemData['tax'] = [
        'id' => 1, // ID 1 corresponde al IVA en Alegra
        'name' => 'IVA',
        'percentage' => $porcentajeIVA,
        'value' => $impuesto
    ];
    
    // 3. Usar el campo taxes (array de impuestos)
    $itemData['taxes'] = [
        [
            'id' => 1,
            'name' => 'IVA',
            'percentage' => $porcentajeIVA,
            'value' => $impuesto
        ]
    ];
    
    // 4. Acumular impuestos para el totalTaxes a nivel de factura
    $totalImpuestos[1] = [
        'id' => 1,
        'name' => 'IVA',
        'percentage' => $porcentajeIVA,
        'amount' => $impuesto
    ];
}

$items[] = $itemData;

// Obtener el método de pago formateado para Alegra
$payment = $alegraService->mapearFormaPago('efectivo');

// Preparar datos completos de la factura
$datos = [
    'date' => date('Y-m-d'),
    'dueDate' => date('Y-m-d'),
    'client' => [
        'id' => 1 // ID simulado para prueba
    ],
    'items' => $items,
    'payment' => $payment,
    'useElectronicInvoice' => true
];

// Si es responsable de IVA, agregar el campo totalTaxes a nivel de factura
if ($esResponsableIVA && !empty($totalImpuestos)) {
    $datos['totalTaxes'] = array_values($totalImpuestos);
}

// Mostrar los datos que se enviarían a Alegra
echo "\nDatos que se enviarían a Alegra:\n";
echo json_encode($datos, JSON_PRETTY_PRINT) . "\n";

// Verificar que todos los formatos de IVA estén presentes
echo "\nVerificación de formatos de IVA:\n";

if ($esResponsableIVA) {
    // Verificar taxRate a nivel de ítem
    if (isset($items[0]['taxRate']) && $items[0]['taxRate'] == $porcentajeIVA) {
        echo "✅ taxRate configurado correctamente a nivel de ítem: {$items[0]['taxRate']}%\n";
    } else {
        echo "❌ taxRate no configurado correctamente a nivel de ítem\n";
    }
    
    // Verificar tax a nivel de ítem
    if (isset($items[0]['tax']) && $items[0]['tax']['percentage'] == $porcentajeIVA) {
        echo "✅ tax configurado correctamente a nivel de ítem: {$items[0]['tax']['percentage']}%\n";
        echo "   Valor del impuesto: {$items[0]['tax']['value']}\n";
    } else {
        echo "❌ tax no configurado correctamente a nivel de ítem\n";
    }
    
    // Verificar taxes a nivel de ítem
    if (isset($items[0]['taxes']) && is_array($items[0]['taxes']) && isset($items[0]['taxes'][0]['percentage']) && $items[0]['taxes'][0]['percentage'] == $porcentajeIVA) {
        echo "✅ taxes configurado correctamente a nivel de ítem: {$items[0]['taxes'][0]['percentage']}%\n";
        echo "   Valor del impuesto: {$items[0]['taxes'][0]['value']}\n";
    } else {
        echo "❌ taxes no configurado correctamente a nivel de ítem\n";
    }
    
    // Verificar totalTaxes a nivel de factura
    if (isset($datos['totalTaxes']) && is_array($datos['totalTaxes']) && isset($datos['totalTaxes'][0]['percentage']) && $datos['totalTaxes'][0]['percentage'] == $porcentajeIVA) {
        echo "✅ totalTaxes configurado correctamente a nivel de factura: {$datos['totalTaxes'][0]['percentage']}%\n";
        echo "   Valor total de impuestos: {$datos['totalTaxes'][0]['amount']}\n";
    } else {
        echo "❌ totalTaxes no configurado correctamente a nivel de factura\n";
    }
    
    echo "\n✅ El formato de IVA está configurado correctamente en todos los niveles requeridos por Alegra.\n";
    echo "   Esto asegura que el IVA se enviará correctamente independientemente del formato que Alegra espere recibir.\n";
} else {
    echo "La empresa no es responsable de IVA, por lo que no se incluyen campos de impuestos.\n";
}

echo "\nVerificación finalizada.\n";
