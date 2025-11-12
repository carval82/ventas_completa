<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICACIÓN DIRECTA EN BD ===\n\n";

// Consulta SQL directa
$ventas = DB::select("
    SELECT 
        id,
        numero_factura,
        CASE WHEN cufe_local IS NOT NULL THEN 'SÍ' ELSE 'NO' END as tiene_cufe,
        CASE WHEN qr_local IS NOT NULL THEN 'SÍ' ELSE 'NO' END as tiene_qr,
        LENGTH(cufe_local) as cufe_length,
        LENGTH(qr_local) as qr_length,
        alegra_id
    FROM ventas 
    WHERE alegra_id IS NULL
    ORDER BY id DESC
    LIMIT 10
");

echo "Últimas 10 facturas locales:\n\n";

foreach ($ventas as $venta) {
    echo "Factura #{$venta->id} - {$venta->numero_factura}\n";
    echo "  Alegra ID: " . ($venta->alegra_id ?? 'NULL') . "\n";
    echo "  CUFE: {$venta->tiene_cufe} (" . ($venta->cufe_length ?? 0) . " chars)\n";
    echo "  QR: {$venta->tiene_qr} (" . ($venta->qr_length ?? 0) . " bytes)\n\n";
}
