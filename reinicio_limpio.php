<?php
/**
 * Reinicio limpio - Migrate Fresh + Restaurar configuración básica
 */

echo "╔════════════════════════════════════════╗\n";
echo "║   REINICIO LIMPIO DE BASE DE DATOS    ║\n";
echo "╚════════════════════════════════════════╝\n\n";

echo "⚠️  ADVERTENCIA: Esto eliminará TODOS los datos\n";
echo "    Solo se restaurarán:\n";
echo "    - Configuración de empresa\n";
echo "    - Token de Alegra\n";
echo "    - Usuario admin\n\n";

echo "¿Estás seguro? Escribe 'SI' para continuar: ";
$confirmacion = trim(fgets(STDIN));

if (strtoupper($confirmacion) !== 'SI') {
    echo "\n❌ Operación cancelada\n";
    exit(0);
}

echo "\n🔄 Iniciando reinicio limpio...\n\n";

// 1. Migrate Fresh
echo "Paso 1/4: Ejecutando migrate:fresh...\n";
passthru('php artisan migrate:fresh --force');

echo "\n";

// 2. Leer backup
echo "Paso 2/4: Cargando backup de configuración...\n";

$backupFiles = glob('backup_config_*.json');
if (empty($backupFiles)) {
    echo "❌ No se encontró archivo de backup\n";
    exit(1);
}

// Usar el backup más reciente
$backupFile = end($backupFiles);
echo "  Usando: {$backupFile}\n";

$backup = json_decode(file_get_contents($backupFile), true);

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// 3. Restaurar configuración esencial
echo "\nPaso 3/4: Restaurando configuración...\n";

// Restaurar empresa
if (isset($backup['empresa'])) {
    DB::table('empresas')->insert($backup['empresa']);
    echo "  ✓ Empresa restaurada\n";
}

// Restaurar usuario
if (isset($backup['user'])) {
    DB::table('users')->insert(array_merge($backup['user'], [
        'created_at' => now(),
        'updated_at' => now()
    ]));
    echo "  ✓ Usuario restaurado\n";
}

echo "\nPaso 4/4: Limpieza final...\n";
passthru('php artisan cache:clear');
passthru('php artisan config:clear');

echo "\n";
echo "╔════════════════════════════════════════╗\n";
echo "║         ✅ REINICIO COMPLETADO         ║\n";
echo "╚════════════════════════════════════════╝\n\n";

echo "Estado de la base de datos:\n";
echo "  - Empresas: " . DB::table('empresas')->count() . "\n";
echo "  - Usuarios: " . DB::table('users')->count() . "\n";
echo "  - Clientes: " . DB::table('clientes')->count() . "\n";
echo "  - Productos: " . DB::table('productos')->count() . "\n";
echo "  - Ventas: " . DB::table('ventas')->count() . "\n";
echo "  - Detalles de venta: " . DB::table('detalle_ventas')->count() . "\n\n";

echo "Próximos pasos:\n";
echo "  1. Reinicia Apache/servidor web\n";
echo "  2. Inicia sesión con el usuario admin\n";
echo "  3. Verifica configuración de Alegra\n";
echo "  4. Crea productos nuevos según necesites\n";
echo "  5. Prueba crear una venta con factura electrónica\n\n";

echo "Token de Alegra guardado: " . (isset($backup['empresa']['alegra_token']) ? '✓' : '✗') . "\n";
