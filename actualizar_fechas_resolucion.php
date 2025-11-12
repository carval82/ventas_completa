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
    
    if (!$resolucion) {
        echo "Los datos de resolución no tienen el formato esperado.\n";
        print_r($resolucion);
        exit;
    }
    
    // Extraer fechas del texto de la resolución
    $texto = $resolucion['texto'];
    
    // Buscar la fecha de resolución (formato: dd/mm/yyyy)
    preg_match('/No\.\s+\d+\s+del\s+(\d{2}\/\d{2}\/\d{4})/', $texto, $matches);
    $fechaResolucion = isset($matches[1]) ? $matches[1] : null;
    
    // Buscar la fecha de vencimiento (formato: dd/mm/yyyy)
    preg_match('/Vigencia hasta:\s+(\d{2}\/\d{2}\/\d{4})/', $texto, $matches);
    $fechaVencimiento = isset($matches[1]) ? $matches[1] : null;
    
    echo "Fechas extraídas del texto:\n";
    echo "Fecha Resolución: " . ($fechaResolucion ?: 'No encontrada') . "\n";
    echo "Fecha Vencimiento: " . ($fechaVencimiento ?: 'No encontrada') . "\n";
    
    // Actualizar las fechas en la base de datos
    if ($fechaResolucion) {
        $fechaObj = \DateTime::createFromFormat('d/m/Y', $fechaResolucion);
        if ($fechaObj) {
            $empresa->fecha_resolucion = $fechaObj->format('Y-m-d');
            echo "Fecha de resolución actualizada a: " . $fechaObj->format('Y-m-d') . "\n";
        }
    }
    
    if ($fechaVencimiento) {
        $fechaObj = \DateTime::createFromFormat('d/m/Y', $fechaVencimiento);
        if ($fechaObj) {
            $empresa->fecha_vencimiento_resolucion = $fechaObj->format('Y-m-d');
            echo "Fecha de vencimiento actualizada a: " . $fechaObj->format('Y-m-d') . "\n";
        }
    }
    
    // Guardar los cambios
    $empresa->save();
    
    echo "\nDatos de resolución actualizados correctamente.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    Log::error('Error al actualizar fechas de resolución', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
