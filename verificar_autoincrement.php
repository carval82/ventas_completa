<?php
/**
 * Verifica el estado del auto_increment de las tablas
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Verificando AUTO_INCREMENT:\n";
echo "===========================\n\n";

$tablas = ['ventas', 'detalle_ventas', 'productos', 'clientes'];

foreach ($tablas as $tabla) {
    // Obtener el próximo auto_increment
    $status = DB::select("SHOW TABLE STATUS LIKE '{$tabla}'");
    
    if (!empty($status)) {
        $autoIncrement = $status[0]->Auto_increment;
        
        // Obtener el ID máximo actual
        $maxId = DB::table($tabla)->max('id');
        
        echo "Tabla: {$tabla}\n";
        echo "  Próximo auto_increment: {$autoIncrement}\n";
        echo "  ID máximo actual: " . ($maxId ?? 'NULL') . "\n";
        
        if ($maxId && $autoIncrement <= $maxId) {
            echo "  ⚠️ PROBLEMA: Auto-increment <= ID máximo (puede causar reutilización de IDs)\n";
            echo "  Solución: Ejecutar ALTER TABLE `{$tabla}` AUTO_INCREMENT = " . ($maxId + 1) . ";\n";
        } else {
            echo "  ✓ OK\n";
        }
        echo "\n";
    }
}

// Ver últimos detalles
echo "Últimos 5 detalles de venta:\n";
echo "============================\n";
$ultimos = DB::table('detalle_ventas')
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get(['id', 'venta_id', 'producto_id', 'created_at']);

foreach ($ultimos as $d) {
    echo "  ID:{$d->id} | Venta:{$d->venta_id} | Producto:{$d->producto_id} | Fecha:{$d->created_at}\n";
}
