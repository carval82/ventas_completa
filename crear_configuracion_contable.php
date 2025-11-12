<?php
/**
 * Script para crear configuraciones contables necesarias
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ConfiguracionContable;
use App\Models\PlanCuenta;
use Illuminate\Support\Facades\DB;

echo "=== Creación de Configuraciones Contables ===\n\n";

// Definir las configuraciones a crear
$configuraciones = [
    [
        'concepto' => 'inventario',
        'cuenta_tipo' => 'Activo',
        'descripcion' => 'Cuenta para el manejo de inventario'
    ],
    [
        'concepto' => 'iva_compras',
        'cuenta_tipo' => 'Activo',
        'descripcion' => 'Cuenta para el IVA en compras'
    ]
];

// Buscar cuentas adecuadas para cada configuración
foreach ($configuraciones as $config) {
    echo "Configurando {$config['concepto']}...\n";
    
    // Verificar si ya existe
    $existente = ConfiguracionContable::where('concepto', $config['concepto'])->first();
    
    if ($existente) {
        echo "- Ya existe una configuración para {$config['concepto']}.\n";
        continue;
    }
    
    // Buscar una cuenta adecuada
    $cuenta = PlanCuenta::where('tipo', 'like', '%' . $config['cuenta_tipo'] . '%')->first();
    
    if (!$cuenta) {
        echo "- No se encontró una cuenta de tipo {$config['cuenta_tipo']} para asignar.\n";
        continue;
    }
    
    // Crear la configuración
    ConfiguracionContable::create([
        'concepto' => $config['concepto'],
        'cuenta_id' => $cuenta->id,
        'descripcion' => $config['descripcion'],
        'estado' => true
    ]);
    
    echo "- Configuración creada exitosamente con la cuenta {$cuenta->nombre} (ID: {$cuenta->id}).\n";
}

echo "\nVerificando configuraciones actuales:\n";
$configuraciones = ConfiguracionContable::with('cuenta')->get();

echo "ID | Concepto | Cuenta ID | Nombre Cuenta | Estado\n";
echo "------------------------------------------------\n";
foreach ($configuraciones as $config) {
    $cuenta = $config->cuenta ? $config->cuenta->nombre : 'NO ASIGNADA';
    $estado = $config->estado ? 'Activo' : 'Inactivo';
    echo "{$config->id} | {$config->concepto} | {$config->cuenta_id} | {$cuenta} | {$estado}\n";
}

echo "\n=== Proceso completado ===\n";
