<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════\n";
echo "  LIMPIEZA TOTAL DE CACHÉ Y VERIFICACIÓN\n";
echo "═══════════════════════════════════════════\n\n";

// 1. Verificar BD
echo "1️⃣  Verificando BD...\n";
$empresa = DB::table('empresas')->first();
echo "   Email BD: {$empresa->alegra_email}\n";
echo "   Token BD: " . substr($empresa->alegra_token, 0, 20) . "...\n\n";

// 2. Limpiar OPcache
echo "2️⃣  Limpiando OPcache...\n";
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "   ✅ OPcache limpiado\n\n";
} else {
    echo "   ⚠️  OPcache no disponible\n\n";
}

// 3. Limpiar caché de Laravel
echo "3️⃣  Limpiando caché de Laravel...\n";
Artisan::call('cache:clear');
echo "   ✅ Cache cleared\n";

Artisan::call('config:clear');
echo "   ✅ Config cleared\n";

Artisan::call('view:clear');
echo "   ✅ Views cleared\n\n";

// 4. Verificar token correcto
echo "4️⃣  Verificación final:\n";
if ($empresa->alegra_token === '4398994d2a44f8153123') {
    echo "   ✅ Token CORRECTO en BD\n";
} else {
    echo "   ❌ Token INCORRECTO en BD\n";
    echo "   Corrigiendo...\n";
    
    DB::table('empresas')
        ->where('id', $empresa->id)
        ->update([
            'alegra_email' => 'pcapacho24@hotmail.com',
            'alegra_token' => '4398994d2a44f8153123',
            'updated_at' => now()
        ]);
    
    echo "   ✅ Token actualizado\n";
}

echo "\n═══════════════════════════════════════════\n";
echo "  ⚠️  IMPORTANTE: REINICIA php artisan serve\n";
echo "  (Ctrl+C y volver a ejecutar)\n";
echo "═══════════════════════════════════════════\n";
