<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "CONFIGURACIÓN DE FACTURACIÓN\n";
echo "============================\n\n";

// Ver columnas de la tabla
$columns = Schema::getColumnListing('configuracion_facturacion');
echo "Columnas disponibles:\n";
foreach ($columns as $col) {
    echo "  - {$col}\n";
}
echo "\n";

// Ver datos
$configs = DB::table('configuracion_facturacion')->get();
foreach ($configs as $config) {
    echo "Configuración #{$config->id}:\n";
    echo "  Proveedor: {$config->proveedor}\n";
    
    foreach ($columns as $col) {
        if (in_array($col, ['id', 'proveedor'])) continue;
        
        $value = $config->$col ?? 'NULL';
        
        // Ocultar tokens/passwords parcialmente
        if (stripos($col, 'token') !== false || stripos($col, 'password') !== false || stripos($col, 'secret') !== false || stripos($col, 'key') !== false) {
            if ($value && $value != 'NULL' && strlen($value) > 10) {
                $value = substr($value, 0, 15) . '...[' . strlen($value) . ' chars]';
            }
        }
        
        echo "  {$col}: {$value}\n";
    }
    echo "\n";
}
