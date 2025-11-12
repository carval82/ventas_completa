<?php
// Script para actualizar la resolución con un texto más corto
require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Configurar conexión a la base de datos
$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'database'  => 'ventas_completa',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

// Hacer que Eloquent esté disponible globalmente
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Datos de la resolución con texto más corto
$resolucion = [
    'texto' => 'Resolución DIAN N° 18764083087981 - Prefijo FEV',
    'prefijo' => 'FEV',
    'id' => '1',
    'numero_resolucion' => '18764083087981',
    'fecha_inicio' => '08/11/2024',
    'fecha_fin' => '08/11/2026'
];

// Actualizar la empresa con los datos de la resolución
try {
    Capsule::table('empresas')
        ->where('id', 1)
        ->update([
            'resolucion_facturacion' => json_encode($resolucion),
            'prefijo_factura' => 'FEV',
            'id_resolucion_alegra' => '1',
            'fecha_resolucion' => '2024-11-08',
            'fecha_vencimiento_resolucion' => '2026-11-08',
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    
    echo "Resolución de facturación electrónica actualizada correctamente con texto más corto.\n";
    echo "Prefijo: FEV\n";
    echo "Número de resolución: 18764083087981\n";
    echo "Fecha de resolución: 2024-11-08\n";
    echo "Fecha de vencimiento: 2026-11-08\n";
} catch (Exception $e) {
    echo "Error al actualizar la resolución: " . $e->getMessage() . "\n";
}

// Verificar que se haya guardado correctamente
$empresa = Capsule::table('empresas')->where('id', 1)->first();
echo "\nResolución guardada en la base de datos (raw): " . $empresa->resolucion_facturacion . "\n";

// Intentar decodificar la resolución
$resolucionGuardada = json_decode($empresa->resolucion_facturacion, true);

if ($resolucionGuardada) {
    echo "\nResolución decodificada correctamente:\n";
    foreach ($resolucionGuardada as $key => $value) {
        echo "$key: $value\n";
    }
} else {
    echo "\nNo se pudo decodificar la resolución. Error de JSON: " . json_last_error_msg() . "\n";
}
?>
