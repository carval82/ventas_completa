<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Venta;
use App\Models\Empresa;

echo "=== VERIFICACIÓN DE QR LOCAL ===\n\n";

// 1. Verificar estado en empresa
$empresa = Empresa::first();
echo "1. CONFIGURACIÓN EMPRESA:\n";
echo "   Generar QR Local: " . ($empresa->generar_qr_local ? "✅ ACTIVADO" : "❌ DESACTIVADO") . "\n\n";

// 2. Verificar últimas facturas (que NO sean electrónicas)
$ventas = Venta::whereNull('alegra_id')
               ->latest()
               ->limit(5)
               ->get();

echo "2. ÚLTIMAS 5 FACTURAS LOCALES (sin Alegra):\n";
foreach ($ventas as $venta) {
    echo "   Factura #{$venta->id} - {$venta->numero_factura}\n";
    echo "   - CUFE Local: " . ($venta->cufe_local ? "✅ SÍ (" . substr($venta->cufe_local, 0, 20) . "...)" : "❌ NO") . "\n";
    echo "   - QR Local: " . ($venta->qr_local ? "✅ SÍ (" . strlen($venta->qr_local) . " chars)" : "❌ NO") . "\n";
    echo "   - Fecha: {$venta->fecha_venta}\n\n";
}

// 3. Verificar si la librería QR está disponible
echo "3. LIBRERÍA QR:\n";
if (class_exists('SimpleSoftwareIO\QrCode\Facades\QrCode')) {
    echo "   ✅ SimpleSoftwareIO\QrCode INSTALADA\n";
} else {
    echo "   ⚠️  SimpleSoftwareIO\QrCode NO ENCONTRADA\n";
    echo "   Usando fallback a API externa\n";
}

echo "\n=== FIN DE VERIFICACIÓN ===\n";
