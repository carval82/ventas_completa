<?php
/**
 * Ver detalles de ventas recientes
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Últimos 15 detalles de venta:\n";
echo "==============================\n\n";

$detalles = DB::table('detalle_ventas')
    ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
    ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
    ->orderBy('detalle_ventas.id', 'desc')
    ->limit(15)
    ->select(
        'detalle_ventas.id as detalle_id',
        'detalle_ventas.venta_id',
        'ventas.numero_factura',
        'ventas.created_at as fecha_venta',
        'detalle_ventas.producto_id',
        'productos.nombre as producto_nombre',
        'detalle_ventas.cantidad',
        'detalle_ventas.precio_unitario',
        'detalle_ventas.created_at as fecha_detalle'
    )
    ->get();

foreach ($detalles as $d) {
    echo sprintf(
        "Detalle #%d | Venta #%d (%s) | Producto: %s (ID:%d) | Cant: %s | Precio: $%s\n",
        $d->detalle_id,
        $d->venta_id,
        $d->numero_factura ?? 'Sin número',
        $d->producto_nombre,
        $d->producto_id,
        $d->cantidad,
        number_format($d->precio_unitario, 2)
    );
    echo "  Fecha venta: {$d->fecha_venta} | Fecha detalle: {$d->fecha_detalle}\n\n";
}

echo "\n";
echo "Agrupar por venta:\n";
echo "==================\n\n";

$ventasConDetalles = DB::table('ventas')
    ->where('ventas.id', '>=', 1)
    ->orderBy('ventas.id', 'desc')
    ->limit(5)
    ->get();

foreach ($ventasConDetalles as $venta) {
    $detalles = DB::table('detalle_ventas')
        ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
        ->where('detalle_ventas.venta_id', $venta->id)
        ->select('productos.nombre', 'detalle_ventas.cantidad', 'detalle_ventas.precio_unitario')
        ->get();
    
    echo "Venta #{$venta->id} ({$venta->numero_factura}) - Total: \${$venta->total}\n";
    echo "  Productos en esta venta: " . count($detalles) . "\n";
    foreach ($detalles as $det) {
        echo "    - {$det->nombre}: {$det->cantidad} x \${$det->precio_unitario}\n";
    }
    echo "\n";
}
