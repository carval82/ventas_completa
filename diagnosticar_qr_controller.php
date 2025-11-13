<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════\n";
echo "  DIAGNÓSTICO QR EN CONTROLADOR\n";
echo "═══════════════════════════════════════════\n\n";

// Obtener última venta
$venta = DB::table('ventas')
    ->whereNotNull('qr_code')
    ->orderBy('id', 'desc')
    ->first();

echo "Venta ID: {$venta->id}\n";
echo "Tiene qr_code: " . ($venta->qr_code ? 'SÍ' : 'NO') . "\n";
echo "Longitud qr_code: " . strlen($venta->qr_code) . " chars\n";
echo "Primeros 50 chars: " . substr($venta->qr_code, 0, 50) . "\n\n";

// Verificar si empieza con 'iVBOR' (indicador de imagen PNG)
$empiezaConIVBOR = str_starts_with($venta->qr_code, 'iVBOR');
echo "Empieza con 'iVBOR'?: " . ($empiezaConIVBOR ? 'SÍ' : 'NO') . "\n";
echo "Es texto plano?: " . (!$empiezaConIVBOR ? 'SÍ' : 'NO') . "\n\n";

// Intentar generar QR
echo "Generando QR desde texto...\n";
try {
    $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
        ->size(200)
        ->generate($venta->qr_code);
    
    $base64 = base64_encode($qrCode);
    
    echo "✅ QR generado\n";
    echo "Longitud SVG: " . strlen($qrCode) . " chars\n";
    echo "Longitud base64: " . strlen($base64) . " chars\n";
    echo "Primeros 80 chars SVG: " . substr($qrCode, 0, 80) . "...\n";
    
} catch (\Exception $e) {
    echo "❌ Error al generar QR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}
