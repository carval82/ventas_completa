<?php
/**
 * Diagnóstico completo de factura electrónica
 * Simula el proceso de preparación de datos para Alegra
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$ventaId = 4; // La última venta

echo "DIAGNÓSTICO DE FACTURA ELECTRÓNICA\n";
echo "===================================\n\n";

// 1. Ver la venta
$venta = DB::table('ventas')->where('id', $ventaId)->first();
if (!$venta) {
    echo "❌ No se encontró la venta #{$ventaId}\n";
    exit(1);
}

echo "VENTA #{$venta->id}\n";
echo "Número: {$venta->numero_factura}\n";
echo "Total: \${$venta->total}\n";
echo "Fecha: {$venta->created_at}\n\n";

// 2. Ver los detalles DIRECTAMENTE de la BD
echo "DETALLES EN BASE DE DATOS:\n";
echo "--------------------------\n";
$detalles = DB::table('detalle_ventas')
    ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
    ->where('detalle_ventas.venta_id', $ventaId)
    ->select(
        'detalle_ventas.id as detalle_id',
        'detalle_ventas.producto_id',
        'productos.nombre as producto_nombre',
        'productos.id_alegra',
        'detalle_ventas.cantidad',
        'detalle_ventas.precio_unitario',
        'detalle_ventas.created_at as fecha_detalle'
    )
    ->get();

echo "Total detalles: " . count($detalles) . "\n\n";

foreach ($detalles as $i => $d) {
    echo "Detalle #" . ($i+1) . ":\n";
    echo "  ID detalle: {$d->detalle_id}\n";
    echo "  Producto ID: {$d->producto_id}\n";
    echo "  Nombre: {$d->producto_nombre}\n";
    echo "  ID Alegra: " . ($d->id_alegra ?? 'NO SINCRONIZADO') . "\n";
    echo "  Cantidad: {$d->cantidad}\n";
    echo "  Precio: \${$d->precio_unitario}\n";
    echo "  Fecha creación: {$d->fecha_detalle}\n\n";
}

// 3. Simular el agrupamiento que hace el código
echo "AGRUPAMIENTO (como lo hace el código):\n";
echo "--------------------------------------\n";

$detallesAgrupados = [];

foreach ($detalles as $detalle) {
    $productoId = $detalle->producto_id;
    $precioUnitario = (float)$detalle->precio_unitario;
    
    $precioFormateado = number_format($precioUnitario, 2, '.', '');
    $claveUnica = $productoId . '_' . $precioFormateado;
    
    if (!isset($detallesAgrupados[$claveUnica])) {
        $detallesAgrupados[$claveUnica] = [
            'producto_id' => $productoId,
            'producto_nombre' => $detalle->producto_nombre,
            'id_alegra' => $detalle->id_alegra,
            'cantidad' => $detalle->cantidad,
            'precio_unitario' => $precioUnitario
        ];
        echo "  ✓ Clave: {$claveUnica} → {$detalle->producto_nombre}\n";
    } else {
        $detallesAgrupados[$claveUnica]['cantidad'] += $detalle->cantidad;
        echo "  + Clave: {$claveUnica} → Sumando cantidad (ahora: {$detallesAgrupados[$claveUnica]['cantidad']})\n";
    }
}

echo "\nTotal agrupados: " . count($detallesAgrupados) . "\n\n";

// 4. Mostrar lo que se enviaría a Alegra
echo "ITEMS PARA ENVIAR A ALEGRA:\n";
echo "---------------------------\n";

$items = [];
foreach ($detallesAgrupados as $clave => $detalle) {
    if (!$detalle['id_alegra']) {
        echo "  ⚠ Producto {$detalle['producto_nombre']} NO tiene ID Alegra - Se saltaría\n";
        continue;
    }
    
    $itemData = [
        'id' => (int)$detalle['id_alegra'],
        'price' => $detalle['precio_unitario'],
        'quantity' => (float)$detalle['cantidad'],
        'taxes' => [],
        'tax' => [],
        'taxRate' => 0
    ];
    
    $items[] = $itemData;
    
    echo "  Item #" . count($items) . ":\n";
    echo "    ID Alegra: {$itemData['id']}\n";
    echo "    Producto: {$detalle['producto_nombre']}\n";
    echo "    Precio: \${$itemData['price']}\n";
    echo "    Cantidad: {$itemData['quantity']}\n\n";
}

echo "Total items a enviar: " . count($items) . "\n\n";

echo "RESULTADO:\n";
echo "==========\n";
if (count($items) == 1) {
    echo "✅ CORRECTO: Solo 1 producto se enviará a Alegra\n";
} else {
    echo "❌ ERROR: Se enviarían " . count($items) . " productos\n";
    echo "   Esto es incorrecto si solo seleccionaste 1 producto en la venta\n";
}
