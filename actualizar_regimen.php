<?php
// Cargar el entorno de Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Actualizar el régimen tributario de la empresa
$empresa = \App\Models\Empresa::first();
if ($empresa) {
    echo "Régimen tributario actual: " . $empresa->regimen_tributario . "\n";
    
    $empresa->regimen_tributario = 'responsable_iva';
    $empresa->save();
    
    echo "Régimen tributario actualizado a: " . $empresa->regimen_tributario . "\n";
} else {
    echo "No se encontró ninguna empresa en la base de datos.\n";
}
