<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Venta;
use App\Models\Comprobante;
use Illuminate\Support\Facades\Log;

echo "=== REGENERACIÓN DE COMPROBANTES FALTANTES ===\n\n";

// Obtener ventas sin comprobantes
$ventas = Venta::with(['detalles', 'cliente'])->get();
$ventasRegeneradas = 0;
$errores = 0;

echo "📊 Verificando " . $ventas->count() . " ventas...\n\n";

foreach ($ventas as $venta) {
    // Verificar si ya tiene comprobante
    $comprobante = Comprobante::where('descripcion', 'LIKE', "%{$venta->numero_factura}%")->first();
    
    if (!$comprobante) {
        echo "🔄 Regenerando comprobante para Venta #{$venta->numero_factura}...\n";
        
        try {
            // Generar comprobante contable
            $venta->generarComprobanteVenta();
            $ventasRegeneradas++;
            echo "  ✅ Comprobante generado exitosamente\n";
            
        } catch (\Exception $e) {
            $errores++;
            echo "  ❌ Error: " . $e->getMessage() . "\n";
            Log::error('Error regenerando comprobante', [
                'venta_id' => $venta->id,
                'numero_factura' => $venta->numero_factura,
                'error' => $e->getMessage()
            ]);
        }
    } else {
        echo "✅ Venta #{$venta->numero_factura} ya tiene comprobante\n";
    }
}

echo "\n=== RESUMEN DE REGENERACIÓN ===\n";
echo "📊 Total ventas procesadas: " . $ventas->count() . "\n";
echo "🔄 Comprobantes regenerados: {$ventasRegeneradas}\n";
echo "❌ Errores encontrados: {$errores}\n";

if ($ventasRegeneradas > 0) {
    echo "\n🎉 ¡Regeneración completada exitosamente!\n";
} else {
    echo "\n✅ Todos los comprobantes ya estaban generados\n";
}

// Verificar integración final
echo "\n=== VERIFICACIÓN FINAL ===\n";
$totalVentas = Venta::count();
$totalComprobantes = Comprobante::where('tipo', 'Ingreso')->where('prefijo', 'V')->count();
$porcentajeIntegracion = $totalVentas > 0 ? ($totalComprobantes / $totalVentas) * 100 : 0;

echo "📊 Total ventas: {$totalVentas}\n";
echo "📋 Total comprobantes: {$totalComprobantes}\n";
echo "🎯 Integración: " . number_format($porcentajeIntegracion, 1) . "%\n";

if ($porcentajeIntegracion >= 100) {
    echo "\n🎉 ¡INTEGRACIÓN COMPLETA AL 100%!\n";
} else {
    echo "\n⚠️  Aún faltan algunos comprobantes por generar\n";
}
