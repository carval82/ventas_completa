<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "DIAGNÓSTICO DE CONFIGURACIÓN DE ALEGRA\n";
echo "========================================\n\n";

// 1. Verificar BD
$empresa = DB::table('empresas')->first();

echo "1. CONFIGURACIÓN EN BASE DE DATOS (tabla empresas):\n";
echo "   ------------------------------------------------\n";
if ($empresa) {
    $emailBD = $empresa->alegra_email ?? '';
    $tokenBD = $empresa->alegra_token ?? '';
    
    echo "   Email BD: " . ($emailBD ?: 'NO CONFIGURADO') . "\n";
    echo "   Token BD: " . ($tokenBD ? 'SI (' . strlen($tokenBD) . ' chars): ' . substr($tokenBD, 0, 10) . '...' . substr($tokenBD, -5) : 'NO CONFIGURADO') . "\n";
} else {
    echo "   ⚠ No hay empresa configurada\n";
}

echo "\n";

// 2. Verificar .env
echo "2. CONFIGURACIÓN EN .ENV:\n";
echo "   ---------------------\n";
$emailEnv = env('ALEGRA_EMAIL', null);
$tokenEnv = env('ALEGRA_TOKEN', null);

echo "   Email ENV: " . ($emailEnv ?: 'NO CONFIGURADO') . "\n";
echo "   Token ENV: " . ($tokenEnv ? 'SI (' . strlen($tokenEnv) . ' chars): ' . substr($tokenEnv, 0, 10) . '...' . substr($tokenEnv, -5) : 'NO CONFIGURADO') . "\n";

echo "\n";

// 3. Análisis
echo "3. ANÁLISIS Y RECOMENDACIÓN:\n";
echo "   -------------------------\n";

$usaBD = !empty($emailBD) && !empty($tokenBD);
$usaEnv = !empty($emailEnv) && !empty($tokenEnv);

if ($usaBD && $usaEnv) {
    echo "   ⚠ DUPLICACIÓN DETECTADA\n";
    echo "   El sistema tiene credenciales en AMBOS lugares\n\n";
    
    if ($tokenBD === $tokenEnv) {
        echo "   ✓ Los tokens son IGUALES\n";
        echo "   Recomendación: Eliminar del .env para evitar confusión\n";
    } else {
        echo "   ✗ Los tokens son DIFERENTES\n";
        echo "   Recomendación: Decidir cuál usar y eliminar el otro\n\n";
        
        echo "   El sistema usa esta prioridad:\n";
        echo "   1º → Base de Datos (tabla empresas)\n";
        echo "   2º → .env (solo si BD está vacía)\n\n";
        
        echo "   Actualmente usando: BASE DE DATOS\n";
    }
} elseif ($usaBD && !$usaEnv) {
    echo "   ✓ CONFIGURACIÓN LIMPIA\n";
    echo "   Solo usa Base de Datos (CORRECTO)\n";
} elseif (!$usaBD && $usaEnv) {
    echo "   ⚠ USANDO .ENV\n";
    echo "   No hay credenciales en BD, usando .env como respaldo\n";
    echo "   Recomendación: Configurar en la interfaz web (Configuración → Empresa)\n";
} else {
    echo "   ✗ SIN CONFIGURACIÓN\n";
    echo "   No hay credenciales en ningún lugar\n";
}

echo "\n";
echo "4. RECOMENDACIÓN FINAL:\n";
echo "   --------------------\n";
echo "   ✓ Usar SOLO Base de Datos (tabla empresas)\n";
echo "   ✓ Configurar desde: Configuración → Empresa\n";
echo "   ✓ Eliminar ALEGRA_EMAIL y ALEGRA_TOKEN del .env\n";
echo "   ✓ Esto evita confusiones y duplicaciones\n";
