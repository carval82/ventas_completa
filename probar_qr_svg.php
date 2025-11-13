<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Probando generación de QR en SVG...\n\n";

$texto = "NumFac: FEVP132\nFecFac: 2025-11-13\nHorFac: 01:18:44-05:00\nNitFac: 8437347\nDocAdq: 9999999\nValFac: 280.00";

try {
    $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
        ->size(200)
        ->generate($texto);
    
    $base64 = base64_encode($qrCode);
    
    echo "✅ QR SVG generado exitosamente\n";
    echo "Longitud SVG: " . strlen($qrCode) . " caracteres\n";
    echo "Longitud base64: " . strlen($base64) . " caracteres\n";
    echo "Primeros 100 chars del SVG: " . substr($qrCode, 0, 100) . "...\n\n";
    
    // Guardar un HTML de prueba
    $html = '<!DOCTYPE html>
<html>
<head><title>Test QR</title></head>
<body style="text-align: center;">
    <h2>QR Code DIAN</h2>
    <img src="data:image/svg+xml;base64,' . $base64 . '" style="width: 200px; height: 200px;">
    <p>CUFE: 64fcd5c322b2e6d7f7375c93fc17a4f1...</p>
</body>
</html>';
    
    file_put_contents('test_qr.html', $html);
    echo "✅ Archivo de prueba creado: test_qr.html\n";
    echo "   Ábrelo en un navegador para ver el QR\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
