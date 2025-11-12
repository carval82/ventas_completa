<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PlanCuenta;
use Carbon\Carbon;

echo "=== PRUEBA DE BALANCE GENERAL ===\n";

$fechaCorte = Carbon::now();
$nivelDetalle = 4;
$mostrarCeros = false;

// Obtener cuentas con movimientos
$cuentas = PlanCuenta::whereHas('movimientos')
                    ->where('estado', true)
                    ->orderBy('codigo')
                    ->get();

echo "Cuentas con movimientos encontradas: " . $cuentas->count() . "\n\n";

$totalActivos = 0;
$totalPasivos = 0;
$totalPatrimonio = 0;

foreach ($cuentas as $cuenta) {
    $saldo = $cuenta->getSaldo(null, $fechaCorte->format('Y-m-d'));
    
    if ($saldo != 0) {
        $clase = $cuenta->clase ?: substr($cuenta->codigo, 0, 1);
        
        echo "Cuenta: {$cuenta->codigo} - {$cuenta->nombre}\n";
        echo "Clase: {$clase} - Saldo: $" . number_format($saldo, 0, ',', '.') . "\n";
        
        switch ($clase) {
            case '1': // Activos
                $totalActivos += $saldo;
                echo "→ ACTIVO\n";
                break;
            case '2': // Pasivos
                $totalPasivos += abs($saldo);
                echo "→ PASIVO\n";
                break;
            case '3': // Patrimonio
                $totalPatrimonio += abs($saldo);
                echo "→ PATRIMONIO\n";
                break;
            default:
                echo "→ OTRA CLASE\n";
        }
        echo "\n";
    }
}

echo "=== TOTALES ===\n";
echo "Total Activos: $" . number_format($totalActivos, 0, ',', '.') . "\n";
echo "Total Pasivos: $" . number_format($totalPasivos, 0, ',', '.') . "\n";
echo "Total Patrimonio: $" . number_format($totalPatrimonio, 0, ',', '.') . "\n";
echo "Total Pasivo + Patrimonio: $" . number_format($totalPasivos + $totalPatrimonio, 0, ',', '.') . "\n";

echo "\n=== VERIFICACIÓN DE CUADRE ===\n";
$diferencia = $totalActivos - ($totalPasivos + $totalPatrimonio);
echo "Diferencia (debe ser 0): $" . number_format($diferencia, 0, ',', '.') . "\n";

if ($diferencia == 0) {
    echo "✅ BALANCE CUADRADO\n";
} else {
    echo "❌ BALANCE DESCUADRADO\n";
}
