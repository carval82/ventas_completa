<?php
/**
 * Busca TODOS los detalles huérfanos (no solo de septiembre)
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Buscando TODOS los detalles potencialmente huérfanos:\n";
echo "=====================================================\n\n";

// Buscar detalles cuya fecha de creación es MUY anterior a la fecha de la venta
$huerfanos = DB::select("
    SELECT 
        dv.id as detalle_id,
        dv.venta_id,
        dv.producto_id,
        p.nombre as producto_nombre,
        dv.created_at as fecha_detalle,
        v.created_at as fecha_venta,
        TIMESTAMPDIFF(DAY, dv.created_at, v.created_at) as dias_diferencia
    FROM detalle_ventas dv
    INNER JOIN ventas v ON dv.venta_id = v.id
    LEFT JOIN productos p ON dv.producto_id = p.id
    WHERE TIMESTAMPDIFF(DAY, dv.created_at, v.created_at) > 1
    ORDER BY dv.id
");

if (count($huerfanos) > 0) {
    echo "⚠️ Encontrados " . count($huerfanos) . " detalles sospechosos:\n\n";
    
    foreach ($huerfanos as $h) {
        echo "Detalle ID:{$h->detalle_id} | Venta:{$h->venta_id}\n";
        echo "  Producto: {$h->producto_nombre}\n";
        echo "  Fecha detalle: {$h->fecha_detalle}\n";
        echo "  Fecha venta: {$h->fecha_venta}\n";
        echo "  Diferencia: {$h->dias_diferencia} días\n\n";
    }
    
    echo "¿Eliminar estos detalles huérfanos? (s/n): ";
    $respuesta = trim(fgets(STDIN));
    
    if (strtolower($respuesta) === 's') {
        $ids = array_map(function($h) { return $h->detalle_id; }, $huerfanos);
        $eliminados = DB::table('detalle_ventas')->whereIn('id', $ids)->delete();
        echo "\n✅ {$eliminados} detalles eliminados\n";
    } else {
        echo "\nNo se eliminó nada\n";
    }
} else {
    echo "✓ No se encontraron detalles huérfanos\n";
}
