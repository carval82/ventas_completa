<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PlanCuenta;
use App\Models\ConfiguracionContable;

echo "=== CREACIÓN DE CUENTAS FALTANTES PARA INTEGRACIÓN NIF ===\n\n";

// Cuentas necesarias que faltan
$cuentasFaltantes = [
    [
        'codigo' => '1435',
        'nombre' => 'MERCANCÍAS NO FABRICADAS POR LA EMPRESA',
        'clase' => '1',
        'nivel' => 4,
        'naturaleza' => 'debito',
        'concepto' => 'inventario',
        'descripcion' => 'Inventario de mercancías para la venta'
    ],
    [
        'codigo' => '1305',
        'nombre' => 'CLIENTES',
        'clase' => '1',
        'nivel' => 4,
        'naturaleza' => 'debito',
        'concepto' => 'clientes',
        'descripcion' => 'Cuentas por cobrar a clientes'
    ]
];

$creadas = 0;
$yaExistentes = 0;

foreach ($cuentasFaltantes as $cuentaData) {
    echo "🔍 Verificando cuenta {$cuentaData['codigo']} - {$cuentaData['nombre']}...\n";
    
    // Verificar si ya existe
    $cuentaExistente = PlanCuenta::where('codigo', $cuentaData['codigo'])->first();
    
    if ($cuentaExistente) {
        echo "  ✅ La cuenta ya existe\n";
        $yaExistentes++;
        
        // Configurar si no está configurada
        $config = ConfiguracionContable::where('concepto', $cuentaData['concepto'])->first();
        if (!$config) {
            ConfiguracionContable::create([
                'concepto' => $cuentaData['concepto'],
                'cuenta_id' => $cuentaExistente->id,
                'descripcion' => $cuentaData['descripcion'],
                'estado' => true
            ]);
            echo "  🔧 Configuración creada para {$cuentaData['concepto']}\n";
        }
        continue;
    }
    
    try {
        // Crear la cuenta
        $nuevaCuenta = PlanCuenta::create([
            'codigo' => $cuentaData['codigo'],
            'nombre' => $cuentaData['nombre'],
            'clase' => $cuentaData['clase'],
            'nivel' => $cuentaData['nivel'],
            'naturaleza' => $cuentaData['naturaleza'],
            'estado' => true
        ]);
        
        echo "  ✅ Cuenta creada: {$cuentaData['codigo']} - {$cuentaData['nombre']}\n";
        
        // Crear la configuración contable
        ConfiguracionContable::create([
            'concepto' => $cuentaData['concepto'],
            'cuenta_id' => $nuevaCuenta->id,
            'descripcion' => $cuentaData['descripcion'],
            'estado' => true
        ]);
        
        echo "  🔧 Configuración creada para {$cuentaData['concepto']}\n";
        $creadas++;
        
    } catch (\Exception $e) {
        echo "  ❌ Error creando cuenta: " . $e->getMessage() . "\n";
    }
}

echo "\n=== RESUMEN ===\n";
echo "📊 Total cuentas procesadas: " . count($cuentasFaltantes) . "\n";
echo "✅ Ya existentes: {$yaExistentes}\n";
echo "🆕 Creadas: {$creadas}\n";

// Verificar configuración final
echo "\n=== VERIFICACIÓN FINAL DE CONFIGURACIÓN ===\n";
$conceptos = ['caja', 'ventas', 'iva_ventas', 'costo_ventas', 'inventario', 'bancos', 'clientes', 'proveedores'];

foreach ($conceptos as $concepto) {
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

echo "\n🎉 ¡Configuración de cuentas completada!\n";
