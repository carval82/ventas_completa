<?php
/**
 * Script para actualizar rápidamente el token de Alegra
 * USO: php actualizar_token_alegra.php "TU_NUEVO_TOKEN_AQUI"
 */

if ($argc < 2) {
    echo "❌ ERROR: Falta el token\n";
    echo "\nUso:\n";
    echo "  php actualizar_token_alegra.php \"TU_NUEVO_TOKEN_AQUI\"\n";
    echo "\nEjemplo:\n";
    echo "  php actualizar_token_alegra.php \"a1b2c3d4e5f6g7h8i9j0\"\n";
    exit(1);
}

$nuevoToken = $argv[1];

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Actualizando token de Alegra...\n";
echo "================================\n\n";

$updated = DB::table('empresas')
    ->update([
        'alegra_token' => $nuevoToken,
        'updated_at' => now()
    ]);

if ($updated) {
    echo "✅ Token actualizado exitosamente\n\n";
    
    $empresa = DB::table('empresas')->first();
    echo "Email: {$empresa->alegra_email}\n";
    echo "Token: " . substr($nuevoToken, 0, 10) . "..." . substr($nuevoToken, -5) . "\n";
    echo "\n";
    echo "Próximo paso:\n";
    echo "=============\n";
    echo "1. Ir a la interfaz web\n";
    echo "2. Configuración → Empresa\n";
    echo "3. Click en 'Probar Conexión'\n";
    echo "4. Verificar que dice: ✅ Conexión exitosa\n";
} else {
    echo "❌ Error al actualizar\n";
}
