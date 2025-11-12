<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Cargar el entorno de Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\AlegraService;
use Illuminate\Support\Facades\Log;

// Configurar logging para ver resultados detallados
Log::info('Iniciando prueba de envío de IVA a Alegra');

// Obtener la empresa para verificar el régimen tributario y porcentaje de IVA
$empresa = \App\Models\Empresa::first();

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

// Obtener un cliente para la prueba
$cliente = \App\Models\Cliente::first();

if (!$cliente) {
    echo "Error: No hay clientes en el sistema.\n";
    exit(1);
}

// Asegurar que el cliente tenga ID de Alegra
if (!$cliente->id_alegra) {
    echo "Sincronizando cliente con Alegra...\n";
    $cliente->syncToAlegra();
    $cliente = \App\Models\Cliente::find($cliente->id); // Recargar para obtener el id_alegra actualizado
}

echo "Cliente: {$cliente->nombres} {$cliente->apellidos} (ID Alegra: {$cliente->id_alegra})\n";

// Obtener un producto para la prueba
$producto = \App\Models\Producto::first();

if (!$producto) {
    echo "Error: No hay productos en el sistema.\n";
    exit(1);
}

// Asegurar que el producto tenga ID de Alegra
if (!$producto->id_alegra) {
    echo "Sincronizando producto con Alegra...\n";
    $producto->syncToAlegra();
    $producto = \App\Models\Producto::find($producto->id); // Recargar para obtener el id_alegra actualizado
}

echo "Producto: {$producto->nombre} (ID Alegra: {$producto->id_alegra})\n";

// Crear datos de prueba para la factura
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

// Datos básicos del ítem
$itemData = [
    'id' => intval($producto->id_alegra),
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
        'id' => intval($cliente->id_alegra)
    ],
    'items' => $items,
    'payment' => $payment,
    'useElectronicInvoice' => true
];

// Si es responsable de IVA, agregar el campo totalTaxes a nivel de factura
if ($esResponsableIVA && !empty($totalImpuestos)) {
    $datos['totalTaxes'] = array_values($totalImpuestos);
}

// Mostrar los datos que se enviarán a Alegra
echo "\nDatos que se enviarán a Alegra:\n";
echo json_encode($datos, JSON_PRETTY_PRINT) . "\n";

// Preguntar si desea continuar con la creación de la factura
echo "\n¿Desea continuar con la creación de la factura en Alegra? (s/n): ";
$respuesta = trim(fgets(STDIN));

if (strtolower($respuesta) !== 's') {
    echo "Operación cancelada.\n";
    exit(0);
}

// Crear la factura en Alegra
echo "Creando factura en Alegra...\n";
$resultado = $alegraService->crearFactura($datos);

// Mostrar el resultado
echo "\nResultado de la creación de la factura:\n";
echo json_encode($resultado, JSON_PRETTY_PRINT) . "\n";

if ($resultado['success']) {
    echo "\nFactura creada correctamente en Alegra con ID: " . $resultado['data']['id'] . "\n";
    echo "Estado: " . ($resultado['data']['status'] ?? 'Desconocido') . "\n";
    
    // Verificar si se incluyó el IVA correctamente
    if (isset($resultado['data']['items'][0]['tax'])) {
        echo "IVA incluido correctamente en la factura.\n";
        echo "Valor del IVA: " . $resultado['data']['items'][0]['tax']['value'] . "\n";
    } else {
        echo "ADVERTENCIA: No se pudo verificar si el IVA se incluyó correctamente.\n";
    }
} else {
    echo "\nError al crear la factura en Alegra: " . ($resultado['message'] ?? 'Error desconocido') . "\n";
}

echo "\nPrueba finalizada.\n";
