<?php
/**
 * Limpia la venta duplicada ID 3
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Limpiando venta duplicada...\n";
echo "============================\n\n";

// Verificar la venta
$venta = DB::table('ventas')->where('id', 3)->first();

if ($venta) {
    echo "Venta encontrada:\n";
    echo "  ID: {$venta->id}\n";
    echo "  Total: {$venta->total}\n";
    echo "  Fecha: {$venta->created_at}\n\n";
    
    // Ver detalles
    $detalles = DB::table('detalle_ventas')
        ->where('venta_id', 3)
        ->get();
    
    echo "Productos en esta venta: " . count($detalles) . "\n\n";
    
    // Eliminar
    DB::table('detalle_ventas')->where('venta_id', 3)->delete();
    DB::table('movimientos_contables')->where('descripcion', 'LIKE', '%No. FE3%')->delete();
    DB::table('ventas')->where('id', 3)->delete();
    
    echo "✅ Venta 3 eliminada completamente\n";
    echo "✅ Detalles eliminados\n";
    echo "✅ Movimientos contables eliminados\n\n";
    echo "Ahora puedes crear una nueva venta limpia.\n";
} else {
    echo "❌ No se encontró la venta ID 3\n";
}
