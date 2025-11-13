<?php
/**
 * Backup de configuración importante antes de migrate:fresh
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$backupData = [];

echo "CREANDO BACKUP DE CONFIGURACIÓN\n";
echo "================================\n\n";

// 1. Configuración de Empresa
$empresa = DB::table('empresas')->first();
if ($empresa) {
    $backupData['empresa'] = [
        'nombre_comercial' => $empresa->nombre_comercial,
        'razon_social' => $empresa->razon_social,
        'nit' => $empresa->nit,
        'direccion' => $empresa->direccion,
        'telefono' => $empresa->telefono,
        'email' => $empresa->email,
        'regimen_tributario' => $empresa->regimen_tributario,
        'alegra_email' => $empresa->alegra_email,
        'alegra_token' => $empresa->alegra_token,
        'id_resolucion_alegra' => $empresa->id_resolucion_alegra,
        'factura_electronica_habilitada' => $empresa->factura_electronica_habilitada
    ];
    echo "✓ Empresa guardada\n";
}

// 2. Usuario Admin
$user = DB::table('users')->where('email', 'admin@example.com')->orWhere('id', 1)->first();
if ($user) {
    $backupData['user'] = [
        'name' => $user->name,
        'email' => $user->email,
        'password' => $user->password // Hash, no contraseña real
    ];
    echo "✓ Usuario guardado\n";
}

// 3. Clientes importantes
$clientes = DB::table('clientes')->get();
$backupData['clientes'] = $clientes->toArray();
echo "✓ " . count($clientes) . " clientes guardados\n";

// 4. Productos
$productos = DB::table('productos')->get();
$backupData['productos'] = $productos->toArray();
echo "✓ " . count($productos) . " productos guardados\n";

// Guardar en archivo JSON
$filename = 'backup_config_' . date('Y-m-d_H-i-s') . '.json';
file_put_contents($filename, json_encode($backupData, JSON_PRETTY_PRINT));

echo "\n✅ Backup guardado en: {$filename}\n";
echo "\nDatos respaldados:\n";
echo "  - Empresa: " . ($backupData['empresa']['nombre_comercial'] ?? 'N/A') . "\n";
echo "  - Token Alegra: " . (isset($backupData['empresa']['alegra_token']) ? 'SÍ' : 'NO') . "\n";
echo "  - Clientes: " . count($backupData['clientes']) . "\n";
echo "  - Productos: " . count($backupData['productos']) . "\n";
echo "\n⚠️ IMPORTANTE: Guarda este archivo en un lugar seguro\n";
