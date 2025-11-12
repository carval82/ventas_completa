<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PlanCuenta;
use App\Models\MovimientoContable;

echo "=== CUENTAS DISPONIBLES ===\n";
$cuentas = PlanCuenta::orderBy('codigo')->get(['codigo', 'nombre', 'naturaleza', 'clase']);
foreach ($cuentas as $cuenta) {
    echo "{$cuenta->codigo} - {$cuenta->nombre} (Clase: {$cuenta->clase}, Naturaleza: {$cuenta->naturaleza})\n";
}

echo "\n=== MOVIMIENTOS CONTABLES ===\n";
$movimientos = MovimientoContable::with('cuenta')->get();
echo "Total movimientos: " . $movimientos->count() . "\n";

if ($movimientos->count() > 0) {
    foreach ($movimientos as $mov) {
        echo "Cuenta: {$mov->cuenta->codigo} - Débito: {$mov->debito} - Crédito: {$mov->credito}\n";
    }
}

echo "\n=== SALDOS POR CUENTA ===\n";
foreach ($cuentas as $cuenta) {
    $saldo = $cuenta->getSaldo();
    if ($saldo != 0) {
        echo "{$cuenta->codigo} - {$cuenta->nombre}: $" . number_format($saldo, 0, ',', '.') . "\n";
    }
}
