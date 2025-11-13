<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$empresa = DB::table('empresas')->first();

echo "═══════════════════════════════════════════\n";
echo "  EMAIL ACTUAL EN BD\n";
echo "═══════════════════════════════════════════\n\n";
echo "Email: {$empresa->alegra_email}\n";
echo "Token: " . substr($empresa->alegra_token, 0, 20) . "...\n\n";

if ($empresa->alegra_email === 'pcapacho24@hotmail.com') {
    echo "✅ Email CORRECTO\n";
} else {
    echo "❌ Email INCORRECTO\n";
    echo "   Debería ser: pcapacho24@hotmail.com\n";
    echo "   Pero es: {$empresa->alegra_email}\n";
}
