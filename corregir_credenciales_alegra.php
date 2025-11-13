<?php
/**
 * Corrige las credenciales de Alegra con las correctas
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "CORRECCIÓN DE CREDENCIALES ALEGRA\n";
echo "==================================\n\n";

// Leer backup con las credenciales correctas
$backupFile = 'backup_config_2025-11-13_00-23-31.json';
if (file_exists($backupFile)) {
    $backup = json_decode(file_get_contents($backupFile), true);
    
    echo "Credenciales CORRECTAS del backup:\n";
    echo "  Email: {$backup['empresa']['alegra_email']}\n";
    echo "  Token: " . substr($backup['empresa']['alegra_token'], 0, 20) . "...\n\n";
} else {
    echo "❌ No se encontró el archivo de backup\n";
    echo "Ingresa manualmente:\n\n";
    
    $backup['empresa']['alegra_email'] = 'pcapacho24@hotmail.com';
    $backup['empresa']['alegra_token'] = '4398994d2a44f8153123';
}

// Mostrar credenciales actuales (INCORRECTAS)
$empresaActual = DB::table('empresas')->first();
echo "Credenciales INCORRECTAS actuales:\n";
echo "  Email: {$empresaActual->alegra_email}\n";
echo "  Token: " . substr($empresaActual->alegra_token ?? '', 0, 20) . "...\n\n";

echo "¿Actualizar con las credenciales correctas? (s/n): ";
$respuesta = trim(fgets(STDIN));

if (strtolower($respuesta) === 's') {
    DB::table('empresas')
        ->where('id', $empresaActual->id)
        ->update([
            'alegra_email' => $backup['empresa']['alegra_email'],
            'alegra_token' => $backup['empresa']['alegra_token'],
            'updated_at' => now()
        ]);
    
    echo "\n✅ Credenciales actualizadas correctamente\n\n";
    
    echo "Credenciales actualizadas:\n";
    echo "  Email: {$backup['empresa']['alegra_email']}\n";
    echo "  Token: " . substr($backup['empresa']['alegra_token'], 0, 20) . "...\n\n";
    
    echo "⚠️ IMPORTANTE: Reinicia el servidor web (Apache/php artisan serve)\n";
} else {
    echo "\n❌ No se actualizaron las credenciales\n";
}
