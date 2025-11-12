<?php

// Cargar el framework Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Empresa;
use Illuminate\Support\Facades\Log;

try {
    // Obtener la empresa
    $empresa = Empresa::first();
    
    if (!$empresa) {
        echo "No se encontró ninguna empresa en la base de datos.\n";
        exit;
    }
    
    // Verificar si hay datos de resolución
    if (empty($empresa->resolucion_facturacion)) {
        echo "No hay datos de resolución para actualizar.\n";
        exit;
    }
    
    // Decodificar el JSON de resolución
    $resolucion = json_decode($empresa->resolucion_facturacion, true);
    
    if (!$resolucion || !isset($resolucion['prefijo']) || !isset($resolucion['id'])) {
        echo "Los datos de resolución no tienen el formato esperado.\n";
        print_r($resolucion);
        exit;
    }
    
    // Actualizar los campos individuales
    $empresa->prefijo_factura = $resolucion['prefijo'];
    $empresa->id_resolucion_alegra = $resolucion['id'];
    
    // Si hay fecha de vencimiento en la resolución, actualizarla también
    if (isset($resolucion['fecha_fin']) && $resolucion['fecha_fin'] !== 'No disponible') {
        $fechaFin = \DateTime::createFromFormat('d/m/Y', $resolucion['fecha_fin']);
        if ($fechaFin) {
            $empresa->fecha_vencimiento_resolucion = $fechaFin->format('Y-m-d');
        }
    }
    
    // Guardar los cambios
    $empresa->save();
    
    echo "Datos de resolución actualizados correctamente:\n";
    echo "Prefijo Factura: " . $empresa->prefijo_factura . "\n";
    echo "ID Resolución Alegra: " . $empresa->id_resolucion_alegra . "\n";
    echo "Fecha Vencimiento: " . ($empresa->fecha_vencimiento_resolucion ? $empresa->fecha_vencimiento_resolucion->format('d/m/Y') : 'No registrada') . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    Log::error('Error al actualizar datos de resolución', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
