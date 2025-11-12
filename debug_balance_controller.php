<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PlanCuenta;
use Carbon\Carbon;

echo "=== DEBUG BALANCE CONTROLLER ===\n";

$fechaCorte = Carbon::now();
$nivelDetalle = 4;
$mostrarCeros = false;

// Simular la lógica del controlador
$cuentas = PlanCuenta::where(function($query) use ($nivelDetalle) {
                $query->where('nivel', '<=', $nivelDetalle)
                      ->orWhereHas('movimientos');
            })
            ->where('estado', true)
            ->where(function($query) {
                $query->whereIn('clase', ['1', '2', '3'])
                      ->orWhere('codigo', 'LIKE', '1%')
                      ->orWhere('codigo', 'LIKE', '2%')
                      ->orWhere('codigo', 'LIKE', '3%');
            })
            ->orderBy('codigo')
            ->get();

echo "Cuentas encontradas: " . $cuentas->count() . "\n\n";

$balance = [
    'activos' => [],
    'pasivos' => [],
    'patrimonio' => [],
    'totales' => [
        'total_activos' => 0,
        'total_pasivos' => 0,
        'total_patrimonio' => 0,
        'total_pasivo_patrimonio' => 0
    ]
];

foreach ($cuentas as $cuenta) {
    $saldo = $cuenta->getSaldo(null, $fechaCorte->format('Y-m-d'));
    
    // Filtrar cuentas con saldo cero si no se deben mostrar
    if (!$mostrarCeros && $saldo == 0) {
        continue;
    }

    $cuentaData = [
        'codigo' => $cuenta->codigo,
        'nombre' => $cuenta->nombre,
        'nivel' => $cuenta->nivel,
        'saldo' => $saldo,
        'saldo_formateado' => number_format(abs($saldo), 2, ',', '.')
    ];

    // Clasificar según la clase de cuenta o el código si no tiene clase
    $clase = $cuenta->clase ?: substr($cuenta->codigo, 0, 1);
    
    echo "Procesando cuenta: {$cuenta->codigo} - {$cuenta->nombre}\n";
    echo "Clase: {$clase} - Saldo: $" . number_format($saldo, 0, ',', '.') . "\n";
    
    switch ($clase) {
        case '1': // Activos
            $balance['activos'][] = $cuentaData;
            $balance['totales']['total_activos'] += $saldo;
            echo "→ Agregada a ACTIVOS\n";
            break;
            
        case '2': // Pasivos
            $balance['pasivos'][] = $cuentaData;
            $balance['totales']['total_pasivos'] += abs($saldo);
            echo "→ Agregada a PASIVOS\n";
            break;
            
        case '3': // Patrimonio
            $balance['patrimonio'][] = $cuentaData;
            $balance['totales']['total_patrimonio'] += abs($saldo);
            echo "→ Agregada a PATRIMONIO\n";
            break;
            
        default:
            echo "→ NO CLASIFICADA (clase: {$clase})\n";
    }
    echo "\n";
}

echo "=== RESUMEN FINAL ===\n";
echo "Activos: " . count($balance['activos']) . " cuentas\n";
echo "Pasivos: " . count($balance['pasivos']) . " cuentas\n";
echo "Patrimonio: " . count($balance['patrimonio']) . " cuentas\n";

echo "\n=== TOTALES ===\n";
echo "Total Activos: $" . number_format($balance['totales']['total_activos'], 0, ',', '.') . "\n";
echo "Total Pasivos: $" . number_format($balance['totales']['total_pasivos'], 0, ',', '.') . "\n";
echo "Total Patrimonio: $" . number_format($balance['totales']['total_patrimonio'], 0, ',', '.') . "\n";

// Mostrar arrays para debug
echo "\n=== ARRAYS DE CUENTAS ===\n";
echo "ACTIVOS:\n";
foreach ($balance['activos'] as $cuenta) {
    echo "  {$cuenta['codigo']} - {$cuenta['nombre']}: \${$cuenta['saldo_formateado']}\n";
}

echo "\nPASIVOS:\n";
foreach ($balance['pasivos'] as $cuenta) {
    echo "  {$cuenta['codigo']} - {$cuenta['nombre']}: \${$cuenta['saldo_formateado']}\n";
}

echo "\nPATRIMONIO:\n";
foreach ($balance['patrimonio'] as $cuenta) {
    echo "  {$cuenta['codigo']} - {$cuenta['nombre']}: \${$cuenta['saldo_formateado']}\n";
}
