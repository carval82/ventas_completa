<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Probando generación de QR...\n\n";

// Texto de ejemplo
$texto = "NumFac: FEVP132\nFecFac: 2025-11-13\nHorFac: 01:18:44-05:00\nNitFac: 8437347\nDocAdq: 9999999\nValFac: 280.00";

try {
    // Método 1: SimpleSoftwareIO\QrCode
    if (class_exists('\SimpleSoftwareIO\QrCode\Facades\QrCode')) {
        echo "✅ Librería SimpleSoftwareIO\QrCode encontrada\n";
        
        $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
            ->size(200)
            ->generate($texto);
        
        $base64 = base64_encode($qrCode);
        echo "✅ QR generado exitosamente\n";
        echo "Longitud base64: " . strlen($base64) . " caracteres\n";
        echo "Primeros 50 chars: " . substr($base64, 0, 50) . "...\n";
    } else {
        echo "❌ SimpleSoftwareIO\QrCode NO encontrada\n";
        echo "Instalando con composer...\n";
    }
    
    // Método 2: BaconQrCode (alternativo)
    if (class_exists('\BaconQrCode\Writer')) {
        echo "\n✅ Librería BaconQrCode encontrada\n";
        
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $writer = new \BaconQrCode\Writer($renderer);
        $qrCodeSvg = $writer->writeString($texto);
        
        echo "✅ QR generado con BaconQrCode\n";
    } else {
        echo "\n❌ BaconQrCode NO encontrada\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}
