<?php
/**
 * Limpia detalles de venta huérfanos (detalles antiguos que no deberían existir)
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Limpiando detalles de venta huérfanos...\n";
echo "=========================================\n\n";

// Encontrar detalles que son de septiembre pero están en ventas de noviembre
$detallesHuerfanos = DB::table('detalle_ventas')
    ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
    ->where('detalle_ventas.created_at', '<', '2025-11-01')  // Detalles creados antes de noviembre
    ->where('ventas.created_at', '>=', '2025-11-01')         // Pero en ventas de noviembre
    ->select('detalle_ventas.*')
    ->get();

echo "Detalles huérfanos encontrados: " . count($detallesHuerfanos) . "\n\n";

if (count($detallesHuerfanos) > 0) {
    foreach ($detallesHuerfanos as $detalle) {
        echo "  - Detalle ID:{$detalle->id} | Venta:{$detalle->venta_id} | Producto:{$detalle->producto_id}\n";
        echo "    Fecha detalle: {$detalle->created_at}\n";
    }
    
    echo "\n";
    
    // Eliminar detalles huérfanos
    $ids = array_map(function($d) { return $d->id; }, $detallesHuerfanos->toArray());
    $eliminados = DB::table('detalle_ventas')->whereIn('id', $ids)->delete();
    
    echo "✅ {$eliminados} detalles huérfanos eliminados\n\n";
    
    // Verificar las ventas afectadas
    echo "Ventas afectadas:\n";
    $ventasAfectadas = DB::table('ventas')
        ->whereIn('id', array_unique(array_map(function($d) { return $d->venta_id; }, $detallesHuerfanos->toArray())))
        ->get();
    
    foreach ($ventasAfectadas as $venta) {
        $detallesRestantes = DB::table('detalle_ventas')
            ->where('venta_id', $venta->id)
            ->count();
        
        echo "  - Venta #{$venta->id} ({$venta->numero_factura}): {$detallesRestantes} detalle(s) restantes\n";
    }
} else {
    echo "✓ No se encontraron detalles huérfanos\n";
}
