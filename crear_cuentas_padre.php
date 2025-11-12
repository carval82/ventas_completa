<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PlanCuenta;

echo "=== CREACIÓN DE CUENTAS PADRE PARA BALANCE GENERAL ===\n\n";

// Cuentas padre necesarias para la estructura jerárquica
$cuentasPadre = [
    // Nivel 1 - Clases
    ['codigo' => '1', 'nombre' => 'ACTIVO', 'clase' => '1', 'nivel' => 1, 'naturaleza' => 'debito'],
    ['codigo' => '2', 'nombre' => 'PASIVO', 'clase' => '2', 'nivel' => 1, 'naturaleza' => 'credito'],
    ['codigo' => '3', 'nombre' => 'PATRIMONIO', 'clase' => '3', 'nivel' => 1, 'naturaleza' => 'credito'],
    
    // Nivel 2 - Grupos
    ['codigo' => '11', 'nombre' => 'DISPONIBLE', 'clase' => '1', 'nivel' => 2, 'naturaleza' => 'debito'],
    ['codigo' => '31', 'nombre' => 'CAPITAL SOCIAL', 'clase' => '3', 'nivel' => 2, 'naturaleza' => 'credito'],
    
    // Nivel 3 - Subgrupos
    ['codigo' => '1101', 'nombre' => 'CAJA', 'clase' => '1', 'nivel' => 3, 'naturaleza' => 'debito'],
];

$creadas = 0;
$yaExistentes = 0;

foreach ($cuentasPadre as $cuentaData) {
    echo "🔍 Verificando cuenta {$cuentaData['codigo']} - {$cuentaData['nombre']}...\n";
    
    $cuentaExistente = PlanCuenta::where('codigo', $cuentaData['codigo'])->first();
    
    if ($cuentaExistente) {
        echo "  ✅ Ya existe\n";
        $yaExistentes++;
        continue;
    }
    
    try {
        PlanCuenta::create([
            'codigo' => $cuentaData['codigo'],
            'nombre' => $cuentaData['nombre'],
            'clase' => $cuentaData['clase'],
            'nivel' => $cuentaData['nivel'],
            'naturaleza' => $cuentaData['naturaleza'],
            'estado' => true
        ]);
        
        echo "  ✅ Creada: {$cuentaData['codigo']} - {$cuentaData['nombre']} (Nivel {$cuentaData['nivel']})\n";
        $creadas++;
        
    } catch (\Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
    }
}

echo "\n📊 RESUMEN:\n";
echo "  - Cuentas creadas: {$creadas}\n";
echo "  - Ya existentes: {$yaExistentes}\n";
echo "  - Total procesadas: " . count($cuentasPadre) . "\n";

// Verificar estructura jerárquica
echo "\n🌳 ESTRUCTURA JERÁRQUICA CREADA:\n";

$niveles = [1, 2, 3];
foreach ($niveles as $nivel) {
    $cuentasNivel = PlanCuenta::where('nivel', $nivel)
                             ->where('estado', true)
                             ->whereIn('clase', ['1', '2', '3'])
                             ->orderBy('codigo')
                             ->get();
    
    echo "\nNivel {$nivel}:\n";
    foreach ($cuentasNivel as $cuenta) {
        $indentacion = str_repeat("  ", $nivel - 1);
        echo "  {$indentacion}{$cuenta->codigo} - {$cuenta->nombre}\n";
    }
}

echo "\n🎉 ¡Estructura jerárquica completada!\n";
echo "💡 Ahora el Balance General mostrará diferentes niveles de detalle correctamente.\n";
