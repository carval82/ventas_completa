<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PlanCuenta;
use App\Models\MovimientoContable;
use App\Models\ConfiguracionContable;
use Illuminate\Support\Facades\DB;

echo "=== LIMPIEZA DE CUENTAS DUPLICADAS ===\n\n";

// 1. Identificar cuentas duplicadas
echo "🔍 1. IDENTIFICANDO CUENTAS DUPLICADAS...\n";

$cuentasDuplicadas = [
    'caja' => ['110101', '1105'],
    'ventas' => ['4101', '4135']
];

foreach ($cuentasDuplicadas as $tipo => $codigos) {
    echo "\n📊 Analizando cuentas de {$tipo}:\n";
    
    foreach ($codigos as $codigo) {
        $cuenta = PlanCuenta::where('codigo', $codigo)->first();
        if ($cuenta) {
            $movimientos = $cuenta->movimientos()->count();
            $saldo = $cuenta->getSaldo();
            echo "  - {$codigo} - {$cuenta->nombre}: {$movimientos} movimientos, saldo \$" . number_format($saldo, 0, ',', '.') . "\n";
        }
    }
}

// 2. Consolidar cuentas de caja
echo "\n💰 2. CONSOLIDANDO CUENTAS DE CAJA...\n";

try {
    DB::transaction(function () {
        $cuentaCajaPrincipal = PlanCuenta::where('codigo', '110101')->first(); // CAJA principal
        $cuentaCajaSecundaria = PlanCuenta::where('codigo', '1105')->first(); // CAJA secundaria
        
        if ($cuentaCajaPrincipal && $cuentaCajaSecundaria) {
            echo "  Moviendo movimientos de {$cuentaCajaSecundaria->codigo} a {$cuentaCajaPrincipal->codigo}...\n";
            
            // Mover todos los movimientos de la cuenta secundaria a la principal
            $movimientosMovidos = MovimientoContable::where('cuenta_id', $cuentaCajaSecundaria->id)
                                                  ->update(['cuenta_id' => $cuentaCajaPrincipal->id]);
            
            echo "  ✅ {$movimientosMovidos} movimientos transferidos\n";
            
            // Desactivar la cuenta secundaria
            $cuentaCajaSecundaria->update(['estado' => false]);
            echo "  ✅ Cuenta {$cuentaCajaSecundaria->codigo} desactivada\n";
            
            // Actualizar configuración contable si es necesario
            $config = ConfiguracionContable::where('cuenta_id', $cuentaCajaSecundaria->id)->first();
            if ($config) {
                $config->update(['cuenta_id' => $cuentaCajaPrincipal->id]);
                echo "  ✅ Configuración contable actualizada\n";
            }
        }
    });
} catch (\Exception $e) {
    echo "  ❌ Error consolidando caja: " . $e->getMessage() . "\n";
}

// 3. Consolidar cuentas de ventas
echo "\n📈 3. CONSOLIDANDO CUENTAS DE VENTAS...\n";

try {
    DB::transaction(function () {
        $cuentaVentasPrincipal = PlanCuenta::where('codigo', '4101')->first(); // VENTAS principal
        $cuentaVentasSecundaria = PlanCuenta::where('codigo', '4135')->first(); // VENTAS secundaria
        
        if ($cuentaVentasPrincipal && $cuentaVentasSecundaria) {
            echo "  Moviendo movimientos de {$cuentaVentasSecundaria->codigo} a {$cuentaVentasPrincipal->codigo}...\n";
            
            // Mover todos los movimientos de la cuenta secundaria a la principal
            $movimientosMovidos = MovimientoContable::where('cuenta_id', $cuentaVentasSecundaria->id)
                                                  ->update(['cuenta_id' => $cuentaVentasPrincipal->id]);
            
            echo "  ✅ {$movimientosMovidos} movimientos transferidos\n";
            
            // Desactivar la cuenta secundaria
            $cuentaVentasSecundaria->update(['estado' => false]);
            echo "  ✅ Cuenta {$cuentaVentasSecundaria->codigo} desactivada\n";
            
            // Actualizar configuración contable si es necesario
            $config = ConfiguracionContable::where('cuenta_id', $cuentaVentasSecundaria->id)->first();
            if ($config) {
                $config->update(['cuenta_id' => $cuentaVentasPrincipal->id]);
                echo "  ✅ Configuración contable actualizada\n";
            }
        }
    });
} catch (\Exception $e) {
    echo "  ❌ Error consolidando ventas: " . $e->getMessage() . "\n";
}

// 4. Verificar resultado
echo "\n✅ 4. VERIFICANDO RESULTADO...\n";

foreach ($cuentasDuplicadas as $tipo => $codigos) {
    echo "\n📊 Estado final de cuentas de {$tipo}:\n";
    
    foreach ($codigos as $codigo) {
        $cuenta = PlanCuenta::where('codigo', $codigo)->first();
        if ($cuenta) {
            $movimientos = $cuenta->movimientos()->count();
            $saldo = $cuenta->getSaldo();
            $estado = $cuenta->estado ? 'ACTIVA' : 'INACTIVA';
            echo "  - {$codigo} - {$cuenta->nombre}: {$movimientos} movimientos, saldo \$" . number_format($saldo, 0, ',', '.') . " ({$estado})\n";
        }
    }
}

// 5. Verificar configuración contable
echo "\n⚙️ 5. VERIFICANDO CONFIGURACIÓN CONTABLE...\n";

$configuraciones = ['caja', 'ventas'];
foreach ($configuraciones as $concepto) {
    try {
        $cuenta = ConfiguracionContable::getCuentaPorConcepto($concepto);
        if ($cuenta) {
            echo "  ✅ {$concepto}: {$cuenta->codigo} - {$cuenta->nombre}\n";
        } else {
            echo "  ❌ {$concepto}: NO CONFIGURADO\n";
        }
    } catch (\Exception $e) {
        echo "  ❌ {$concepto}: ERROR - {$e->getMessage()}\n";
    }
}

echo "\n🎉 ¡LIMPIEZA COMPLETADA!\n";
echo "📋 RESUMEN:\n";
echo "  - Cuentas de caja consolidadas en 110101\n";
echo "  - Cuentas de ventas consolidadas en 4101\n";
echo "  - Movimientos preservados\n";
echo "  - Configuración contable actualizada\n";
