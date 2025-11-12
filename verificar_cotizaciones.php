<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Cotizacion;

try {
    echo "=== VERIFICACIÓN DE COTIZACIONES ===\n\n";
    
    $cotizaciones = Cotizacion::with(['cliente', 'detalles'])->get();
    
    echo "📋 Total de cotizaciones: " . $cotizaciones->count() . "\n\n";
    
    if ($cotizaciones->count() > 0) {
        echo "LISTADO DE COTIZACIONES:\n";
        echo str_repeat("-", 80) . "\n";
        
        foreach ($cotizaciones as $cotizacion) {
            echo "ID: {$cotizacion->id}\n";
            echo "Número: {$cotizacion->numero_cotizacion}\n";
            echo "Cliente: {$cotizacion->cliente->nombres} {$cotizacion->cliente->apellidos}\n";
            echo "Estado: {$cotizacion->estado}\n";
            echo "Fecha: {$cotizacion->fecha_cotizacion}\n";
            echo "Total: $" . number_format($cotizacion->total, 0, ',', '.') . "\n";
            echo "Detalles: " . $cotizacion->detalles->count() . " productos\n";
            echo str_repeat("-", 80) . "\n";
        }
        
        // Probar acceso a cotización específica
        echo "\n🔍 PROBANDO ACCESO A COTIZACIÓN ID 5:\n";
        $cotizacion5 = Cotizacion::find(5);
        
        if ($cotizacion5) {
            echo "   ✅ Cotización ID 5 encontrada: {$cotizacion5->numero_cotizacion}\n";
        } else {
            echo "   ❌ Cotización ID 5 NO encontrada\n";
            
            // Mostrar IDs disponibles
            $ids = $cotizaciones->pluck('id')->toArray();
            echo "   📍 IDs disponibles: " . implode(', ', $ids) . "\n";
        }
        
    } else {
        echo "❌ No hay cotizaciones en la base de datos\n";
        echo "💡 Crea algunas cotizaciones primero usando la interfaz web\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 Archivo: " . $e->getFile() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
}
