<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ConfiguracionContable;
use App\Models\PlanCuenta;

echo "=== CONFIGURACIÓN AUTOMÁTICA DE CUENTAS CONTABLES ===\n\n";

// Configuraciones necesarias para integración completa
$configuraciones = [
    'caja' => ['codigo' => '110101', 'descripcion' => 'Cuenta de caja para ingresos por ventas'],
    'ventas' => ['codigo' => '4101', 'descripcion' => 'Cuenta de ingresos por ventas'],
    'iva_ventas' => ['codigo' => '2408', 'descripcion' => 'IVA por pagar en ventas'],
    'costo_ventas' => ['codigo' => '6135', 'descripcion' => 'Costo de mercancías vendidas'],
    'inventario' => ['codigo' => '1435', 'descripcion' => 'Inventario de mercancías'],
    'bancos' => ['codigo' => '1110', 'descripcion' => 'Cuentas bancarias'],
    'clientes' => ['codigo' => '1305', 'descripcion' => 'Cuentas por cobrar clientes'],
    'proveedores' => ['codigo' => '2205', 'descripcion' => 'Cuentas por pagar proveedores']
];

$configuradas = 0;
$yaExistentes = 0;
$errores = 0;

foreach ($configuraciones as $concepto => $config) {
    echo "🔍 Configurando '{$concepto}'...\n";
    
    // Verificar si ya existe la configuración
    $configExistente = ConfiguracionContable::where('concepto', $concepto)->first();
    
    if ($configExistente) {
        echo "  ✅ Ya existe configuración para '{$concepto}'\n";
        $yaExistentes++;
        continue;
    }
    
    // Buscar la cuenta por código
    $cuenta = PlanCuenta::where('codigo', $config['codigo'])->where('estado', true)->first();
    
    if (!$cuenta) {
        echo "  ❌ No se encontró la cuenta {$config['codigo']} para '{$concepto}'\n";
        $errores++;
        continue;
    }
    
    try {
        // Crear la configuración
        ConfiguracionContable::create([
            'concepto' => $concepto,
            'cuenta_id' => $cuenta->id,
            'descripcion' => $config['descripcion'],
            'estado' => true
        ]);
        
        echo "  ✅ Configurado '{$concepto}' → {$cuenta->codigo} - {$cuenta->nombre}\n";
        $configuradas++;
        
    } catch (\Exception $e) {
        echo "  ❌ Error configurando '{$concepto}': " . $e->getMessage() . "\n";
        $errores++;
    }
}

echo "\n=== RESUMEN DE CONFIGURACIÓN ===\n";
echo "📊 Total conceptos: " . count($configuraciones) . "\n";
echo "✅ Ya existentes: {$yaExistentes}\n";
echo "🔧 Configuradas: {$configuradas}\n";
echo "❌ Errores: {$errores}\n";

// Verificar configuración final
echo "\n=== VERIFICACIÓN FINAL ===\n";
foreach ($configuraciones as $concepto => $config) {
    try {
        $cuenta = ConfiguracionContable::getCuentaPorConcepto($concepto);
        if ($cuenta) {
            echo "✅ {$concepto}: {$cuenta->codigo} - {$cuenta->nombre}\n";
        } else {
            echo "❌ {$concepto}: NO CONFIGURADO\n";
        }
    } catch (\Exception $e) {
        echo "❌ {$concepto}: ERROR - {$e->getMessage()}\n";
    }
}

if ($configuradas > 0) {
    echo "\n🎉 ¡Configuración automática completada!\n";
    echo "💡 Ahora las ventas generarán asientos contables completos automáticamente\n";
} else {
    echo "\n✅ Todas las configuraciones ya estaban establecidas\n";
}
