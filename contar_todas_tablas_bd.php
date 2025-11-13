<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Tablas del backup
$tablasBackup = [
    'cache', 'cajas_diarias', 'clientes', 'comprobantes', 'configuracion_contable',
    'configuracion_dian', 'configuracion_facturacion', 'cotizaciones', 'detalle_cotizaciones',
    'detalle_remisiones', 'detalle_ventas', 'email_buzons', 'email_configurations',
    'empresas', 'facturas_dian_procesadas', 'impuestos', 'migrations', 'model_has_roles',
    'movimientos_caja', 'movimientos_contables', 'permissions', 'plan_cuentas',
    'productos', 'proveedores_electronicos', 'remisiones', 'role_has_permissions',
    'roles', 'sessions', 'settings', 'terceros', 'ubicaciones', 'users', 'ventas'
];

// Conteo esperado del backup
$esperadoBackup = [
    'cache' => 3,
    'cajas_diarias' => 2,
    'clientes' => 45,
    'comprobantes' => 58,
    'configuracion_contable' => 9,
    'configuracion_dian' => 1,
    'configuracion_facturacion' => 1,
    'cotizaciones' => 4,
    'detalle_cotizaciones' => 3,
    'detalle_remisiones' => 2,
    'detalle_ventas' => 31,
    'email_buzons' => 18,
    'email_configurations' => 3,
    'empresas' => 1,
    'facturas_dian_procesadas' => 3,
    'impuestos' => 10,
    'migrations' => 95,
    'model_has_roles' => 1,
    'movimientos_caja' => 32,
    'movimientos_contables' => 119,
    'permissions' => 34,
    'plan_cuentas' => 25,
    'productos' => 97,
    'proveedores_electronicos' => 5,
    'remisiones' => 2,
    'role_has_permissions' => 87,
    'roles' => 6,
    'sessions' => 3,
    'settings' => 1,
    'terceros' => 1,
    'ubicaciones' => 3,
    'users' => 1,
    'ventas' => 31
];

echo "Comparación Backup vs BD Actual:\n";
echo "=================================\n\n";
echo sprintf("%-35s %10s %10s %10s\n", "Tabla", "Backup", "Actual", "Diferencia");
echo str_repeat("-", 70) . "\n";

$totalBackup = 0;
$totalActual = 0;
$problemasEncontrados = [];

foreach ($tablasBackup as $tabla) {
    if (Schema::hasTable($tabla)) {
        $countActual = DB::table($tabla)->count();
        $countBackup = $esperadoBackup[$tabla] ?? 0;
        
        $diferencia = $countActual - $countBackup;
        $totalBackup += $countBackup;
        $totalActual += $countActual;
        
        $simbolo = '';
        if ($diferencia > 0) {
            $simbolo = '✗ +' . $diferencia;
            $problemasEncontrados[] = "$tabla: tiene $diferencia registro(s) DE MÁS";
        } elseif ($diferencia < 0) {
            $simbolo = '✗ ' . $diferencia;
            $problemasEncontrados[] = "$tabla: faltan " . abs($diferencia) . " registro(s)";
        } else {
            $simbolo = '✓';
        }
        
        echo sprintf("%-35s %10d %10d %10s\n", $tabla, $countBackup, $countActual, $simbolo);
    } else {
        echo sprintf("%-35s %10d %10s %10s\n", $tabla, $esperadoBackup[$tabla] ?? 0, "N/A", "✗ FALTA");
    }
}

echo str_repeat("-", 70) . "\n";
echo sprintf("%-35s %10d %10d %10d\n", "TOTAL:", $totalBackup, $totalActual, $totalActual - $totalBackup);
echo "\n";

if (count($problemasEncontrados) > 0) {
    echo "Problemas encontrados:\n";
    echo "======================\n";
    foreach ($problemasEncontrados as $problema) {
        echo "- $problema\n";
    }
} else {
    echo "✓ Todos los registros coinciden perfectamente!\n";
}
