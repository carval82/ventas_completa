<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Empresa;

echo "🔧 ACTUALIZACIÓN DE RESOLUCIÓN CORRECTA\n";
echo "======================================\n\n";

try {
    // Obtener la empresa
    $empresa = Empresa::first();
    
    if (!$empresa) {
        echo "❌ No hay empresa registrada\n";
        exit(1);
    }

    echo "📋 Empresa actual: {$empresa->nombre_comercial}\n";
    echo "   - Resolución actual: " . ($empresa->resolucion_facturacion ?? 'No configurada') . "\n";
    echo "   - Prefijo actual: " . ($empresa->prefijo_factura ?? 'No configurado') . "\n";
    echo "   - ID Alegra actual: " . ($empresa->id_resolucion_alegra ?? 'No configurado') . "\n\n";

    // Datos de la resolución correcta
    $datosCorrectos = [
        'resolucion_facturacion' => 'Autorización de numeración de facturación N° 18764098256287 de 2025-09-05 Modalidad Factura Electrónica Desde N° FEVP83 hasta FEVP1000 con vigencia hasta 2026-03-05',
        'prefijo_factura' => 'FEVP',
        'id_resolucion_alegra' => '19',
        'fecha_resolucion' => '2025-09-05',
        'fecha_vencimiento_resolucion' => '2026-03-05'
    ];

    echo "🎯 Actualizando con datos correctos:\n";
    foreach ($datosCorrectos as $key => $value) {
        echo "   - {$key}: {$value}\n";
    }
    echo "\n";

    // Actualizar la empresa
    $empresa->update($datosCorrectos);

    echo "✅ EMPRESA ACTUALIZADA EXITOSAMENTE!\n\n";

    // Verificar la actualización
    $empresa->refresh();
    echo "📊 VERIFICACIÓN:\n";
    echo "   - Resolución: " . substr($empresa->resolucion_facturacion, 0, 80) . "...\n";
    echo "   - Prefijo: {$empresa->prefijo_factura}\n";
    echo "   - ID Alegra: {$empresa->id_resolucion_alegra}\n";
    echo "   - Fecha resolución: {$empresa->fecha_resolucion}\n";
    echo "   - Fecha vencimiento: {$empresa->fecha_vencimiento_resolucion}\n\n";

    echo "🎉 RESOLUCIÓN ACTUALIZADA CORRECTAMENTE\n";
    echo "Ahora el sistema usará:\n";
    echo "   - Autorización: 18764098256287\n";
    echo "   - Prefijo: FEVP (en lugar de FEV)\n";
    echo "   - Rango: FEVP83 hasta FEVP1000\n";
    echo "   - Vigencia: hasta 2026-03-05\n";

} catch (Exception $e) {
    echo "❌ ERROR:\n";
    echo "   Mensaje: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n🎯 Actualización completada\n";
