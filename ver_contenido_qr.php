<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$venta = DB::table('ventas')->orderBy('id', 'desc')->first();

echo "Contenido del QR (primeros 300 caracteres):\n";
echo "============================================\n";
echo substr($venta->qr_code, 0, 300) . "\n\n";

echo "Longitud total: " . strlen($venta->qr_code) . " caracteres\n";

// Verificar si es base64
$decoded = base64_decode($venta->qr_code, true);
if ($decoded !== false) {
    echo "Parece ser base64\n";
    echo "Decodificado (primeros 100 chars): " . substr($decoded, 0, 100) . "\n";
} else {
    echo "NO es base64 - Es texto plano\n";
}
