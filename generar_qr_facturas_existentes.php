<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Venta;
use App\Models\Empresa;
use App\Services\QRLocalService;

echo "=== GENERAR QR LOCAL PARA FACTURAS EXISTENTES ===\n\n";

// Verificar si está activado
$empresa = Empresa::first();

if (!$empresa->generar_qr_local) {
    echo "⚠️  QR Local NO está activado en empresa.\n";
    echo "   Ve a Configuración → Empresa → Editar y actívalo.\n";
    exit(1);
}

echo "✅ QR Local ACTIVADO en empresa\n\n";

// Buscar facturas locales sin QR
$ventas = Venta::whereNull('alegra_id')  // Solo facturas locales (no electrónicas)
               ->whereNull('qr_local')   // Que no tengan QR
               ->get();

echo "📋 Facturas encontradas sin QR: " . $ventas->count() . "\n\n";

if ($ventas->count() === 0) {
    echo "✅ Todas las facturas locales ya tienen QR.\n";
    exit(0);
}

$qrService = new QRLocalService();
$procesadas = 0;
$errores = 0;

foreach ($ventas as $venta) {
    try {
        echo "Procesando Factura #{$venta->id} - {$venta->numero_factura}...";
        
        // Generar CUFE y QR
        $qrData = $qrService->generarCUFEyQR($venta, $empresa);
        
        // Actualizar factura directamente en BD
        if ($qrData['qr']) {
            \Illuminate\Support\Facades\DB::table('ventas')
                ->where('id', $venta->id)
                ->update([
                    'cufe_local' => $qrData['cufe'],
                    'qr_local' => $qrData['qr']
                ]);
            
            // Verificar que se guardó
            $verificar = \Illuminate\Support\Facades\DB::table('ventas')
                ->where('id', $venta->id)
                ->first();
                
            if ($verificar->qr_local) {
                echo " ✅ OK (" . strlen($qrData['qr']) . " bytes) - Guardado\n";
                $procesadas++;
            } else {
                echo " ❌ No se guardó en BD\n";
                $errores++;
            }
        } else {
            echo " ⚠️  QR vacío\n";
            $errores++;
        }
        
    } catch (\Exception $e) {
        echo " ❌ ERROR: " . $e->getMessage() . "\n";
        $errores++;
    }
}

echo "\n=== RESUMEN ===\n";
echo "Total facturas: " . $ventas->count() . "\n";
echo "✅ Procesadas: {$procesadas}\n";
echo "❌ Errores: {$errores}\n";
echo "\n";

if ($procesadas > 0) {
    echo "🎉 {$procesadas} facturas ahora tienen QR local!\n";
    echo "   Prueba imprimiendo una factura para verlo.\n";
}
