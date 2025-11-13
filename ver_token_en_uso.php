<?php
/**
 * Ver exactamente qué token está usando el servidor
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$empresa = DB::table('empresas')->first();

echo "TOKEN EN USO POR EL SERVIDOR:\n";
echo "=============================\n\n";

echo "Email: {$empresa->alegra_email}\n";
echo "Token completo: {$empresa->alegra_token}\n";
echo "Token hash: " . md5($empresa->alegra_token) . "\n";
echo "\n";

// Verificar .env
$envToken = env('ALEGRA_TOKEN');
if ($envToken) {
    echo ".ENV Token: {$envToken}\n";
    echo ".ENV hash: " . md5($envToken) . "\n";
    echo "\n";
    
    if ($empresa->alegra_token === $envToken) {
        echo "✓ BD y .ENV tienen el MISMO token\n";
    } else {
        echo "✗ BD y .ENV tienen tokens DIFERENTES\n";
    }
} else {
    echo ".ENV: No tiene token configurado\n";
}

echo "\n";
echo "Para que funcione, el token debe ser:\n";
echo "4398994d2a...53123\n";
