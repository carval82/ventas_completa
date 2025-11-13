<?php
/**
 * Restaura solo la configuración de Alegra en la empresa existente
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Restaurando configuración de Alegra...\n";
echo "======================================\n\n";

// Leer backup
$backupFile = 'backup_config_2025-11-13_00-23-31.json';
if (!file_exists($backupFile)) {
    echo "❌ No se encontró el archivo de backup\n";
    exit(1);
}

$backup = json_decode(file_get_contents($backupFile), true);

// Actualizar la empresa que creó el seeder
$empresa = DB::table('empresas')->first();

if ($empresa) {
    DB::table('empresas')->where('id', $empresa->id)->update([
        'nombre_comercial' => $backup['empresa']['nombre_comercial'],
        'razon_social' => $backup['empresa']['razon_social'],
        'nit' => $backup['empresa']['nit'],
        'direccion' => $backup['empresa']['direccion'],
        'telefono' => $backup['empresa']['telefono'],
        'email' => $backup['empresa']['email'],
        'regimen_tributario' => $backup['empresa']['regimen_tributario'],
        'alegra_email' => $backup['empresa']['alegra_email'],
        'alegra_token' => $backup['empresa']['alegra_token'],
        'id_resolucion_alegra' => $backup['empresa']['id_resolucion_alegra'],
        'factura_electronica_habilitada' => $backup['empresa']['factura_electronica_habilitada'],
        'updated_at' => now()
    ]);
    
    echo "✅ Configuración de Alegra restaurada\n\n";
    
    echo "Datos de la empresa:\n";
    echo "  Nombre: {$backup['empresa']['nombre_comercial']}\n";
    echo "  Email Alegra: {$backup['empresa']['alegra_email']}\n";
    echo "  Token Alegra: " . substr($backup['empresa']['alegra_token'], 0, 10) . "...\n";
    echo "  Facturación electrónica: " . ($backup['empresa']['factura_electronica_habilitada'] ? 'SI' : 'NO') . "\n";
} else {
    echo "❌ No se encontró empresa en la base de datos\n";
}
