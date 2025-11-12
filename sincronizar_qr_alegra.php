<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Venta;
use App\Services\AlegraService;

echo "=== SINCRONIZACIÓN DE QR CODES DESDE ALEGRA ===\n\n";

$alegraService = new AlegraService();

// Obtener facturas sin QR pero con Alegra ID
$ventas = Venta::whereNotNull('alegra_id')
               ->whereNull('qr_code')
               ->where('estado_dian', '!=', 'draft')
               ->get();

echo "📋 Facturas encontradas sin QR: " . $ventas->count() . "\n\n";

if ($ventas->count() === 0) {
    echo "✅ Todas las facturas ya tienen QR o están en borrador.\n";
    exit;
}

$actualizadas = 0;
$errores = 0;

foreach ($ventas as $venta) {
    echo "Procesando Factura #{$venta->id} (Alegra: {$venta->alegra_id})...\n";
    
    try {
        // Obtener detalles completos de Alegra
        $resultado = $alegraService->obtenerDetalleFacturaCompleto($venta->alegra_id);
        
        if ($resultado['success']) {
            $datosActualizar = [];
            
            // Verificar si hay stamp con QR y CUFE
            if (isset($resultado['data']['stamp'])) {
                $stamp = $resultado['data']['stamp'];
                
                if (isset($stamp['barCodeContent']) && !empty($stamp['barCodeContent'])) {
                    $datosActualizar['qr_code'] = $stamp['barCodeContent'];
                    echo "  ✓ QR encontrado (" . strlen($stamp['barCodeContent']) . " chars)\n";
                }
                
                if (isset($stamp['cufe']) && !empty($stamp['cufe'])) {
                    $datosActualizar['cufe'] = $stamp['cufe'];
                    echo "  ✓ CUFE encontrado: " . substr($stamp['cufe'], 0, 20) . "...\n";
                }
                
                if (isset($stamp['legalStatus']) && !empty($stamp['legalStatus'])) {
                    $datosActualizar['estado_dian'] = $stamp['legalStatus'];
                    echo "  ✓ Estado actualizado: {$stamp['legalStatus']}\n";
                }
            }
            
            // Actualizar estado si viene en la respuesta principal
            if (isset($resultado['data']['status']) && !isset($datosActualizar['estado_dian'])) {
                $datosActualizar['estado_dian'] = $resultado['data']['status'];
                echo "  ✓ Estado actualizado: {$resultado['data']['status']}\n";
            }
            
            if (!empty($datosActualizar)) {
                $venta->update($datosActualizar);
                echo "  ✅ Factura actualizada exitosamente\n\n";
                $actualizadas++;
            } else {
                echo "  ⚠️  No se encontraron datos para actualizar (puede estar pendiente en DIAN)\n\n";
            }
            
        } else {
            echo "  ❌ Error al consultar Alegra: " . ($resultado['message'] ?? 'Error desconocido') . "\n\n";
            $errores++;
        }
        
    } catch (\Exception $e) {
        echo "  ❌ Excepción: " . $e->getMessage() . "\n\n";
        $errores++;
    }
    
    // Pequeña pausa para no saturar la API
    usleep(500000); // 0.5 segundos
}

echo "=== RESUMEN ===\n";
echo "Total procesadas: " . $ventas->count() . "\n";
echo "Actualizadas: {$actualizadas}\n";
echo "Errores: {$errores}\n";
echo "Pendientes: " . ($ventas->count() - $actualizadas - $errores) . "\n";
echo "\n✅ Sincronización completada\n";
