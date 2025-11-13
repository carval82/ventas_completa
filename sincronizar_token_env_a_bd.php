<?php
/**
 * Sincroniza el token de Alegra del .env a la base de datos
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$tokenEnv = env('ALEGRA_TOKEN');
$emailEnv = env('ALEGRA_EMAIL');

if (empty($tokenEnv)) {
    echo "❌ No hay token en el .env\n";
    exit(1);
}

echo "Sincronizando credenciales de Alegra\n";
echo "=====================================\n\n";

$empresa = DB::table('empresas')->first();

echo "ANTES:\n";
echo "  BD Email: " . ($empresa->alegra_email ?? 'VACÍO') . "\n";
echo "  BD Token: " . ($empresa->alegra_token ? substr($empresa->alegra_token, 0, 10) . '...' . substr($empresa->alegra_token, -5) : 'VACÍO') . "\n";
echo "\n";

echo "NUEVO (del .env):\n";
echo "  Email: " . ($emailEnv ?? 'NO CONFIGURADO') . "\n";
echo "  Token: " . substr($tokenEnv, 0, 10) . '...' . substr($tokenEnv, -5) . "\n";
echo "\n";

$datos = ['alegra_token' => $tokenEnv];
if (!empty($emailEnv)) {
    $datos['alegra_email'] = $emailEnv;
}
$datos['updated_at'] = now();

$updated = DB::table('empresas')->update($datos);

if ($updated) {
    echo "✅ Token actualizado en la base de datos\n\n";
    
    $empresaActualizada = DB::table('empresas')->first();
    echo "DESPUÉS:\n";
    echo "  BD Email: " . $empresaActualizada->alegra_email . "\n";
    echo "  BD Token: " . substr($empresaActualizada->alegra_token, 0, 10) . '...' . substr($empresaActualizada->alegra_token, -5) . "\n";
    echo "\n";
    echo "✓ Ahora el sistema usará el token correcto\n";
    echo "✓ Ya puedes probar la conexión desde la interfaz web\n";
} else {
    echo "❌ Error al actualizar\n";
}
