<?php
/**
 * Diagnostica TODAS las fuentes de credenciales de Alegra
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "═══════════════════════════════════════════════════════════\n";
echo "  DIAGNÓSTICO COMPLETO DE CREDENCIALES ALEGRA\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// 1. Tabla empresas
echo "1️⃣  TABLA: empresas\n";
echo "─────────────────────────────────────────────────\n";
$empresas = DB::table('empresas')->get();
foreach ($empresas as $empresa) {
    echo "  ID: {$empresa->id}\n";
    echo "  Nombre: {$empresa->nombre_comercial}\n";
    echo "  Email Alegra: " . ($empresa->alegra_email ?? 'NULL') . "\n";
    echo "  Token Alegra: " . (isset($empresa->alegra_token) ? substr($empresa->alegra_token, 0, 15) . '...' : 'NULL') . "\n";
    echo "  Facturación electrónica habilitada: " . ($empresa->factura_electronica_habilitada ? 'SÍ' : 'NO') . "\n\n";
}

// 2. Archivo .env
echo "2️⃣  ARCHIVO: .env\n";
echo "─────────────────────────────────────────────────\n";
$envAlegraUser = env('ALEGRA_USER');
$envAlegraToken = env('ALEGRA_TOKEN');
echo "  ALEGRA_USER: " . ($envAlegraUser ?? 'NO DEFINIDO') . "\n";
echo "  ALEGRA_TOKEN: " . ($envAlegraToken ? substr($envAlegraToken, 0, 15) . '...' : 'NO DEFINIDO') . "\n\n";

// 3. Config alegra.php
echo "3️⃣  CONFIG: config/alegra.php\n";
echo "─────────────────────────────────────────────────\n";
$configUser = config('alegra.user');
$configToken = config('alegra.token');
echo "  user: " . ($configUser ?? 'NO DEFINIDO') . "\n";
echo "  token: " . ($configToken ? substr($configToken, 0, 15) . '...' : 'NO DEFINIDO') . "\n\n";

// 4. Buscar tabla configuracion_facturacion
if (Schema::hasTable('configuracion_facturacion')) {
    echo "4️⃣  TABLA: configuracion_facturacion\n";
    echo "─────────────────────────────────────────────────\n";
    $configs = DB::table('configuracion_facturacion')->get();
    
    if (count($configs) > 0) {
        foreach ($configs as $config) {
            echo "  ID: {$config->id}\n";
            echo "  Proveedor: {$config->proveedor}\n";
            
            // Listar todas las columnas
            foreach ((array)$config as $key => $value) {
                if (in_array($key, ['id', 'proveedor'])) continue;
                
                if (stripos($key, 'token') !== false || stripos($key, 'password') !== false || stripos($key, 'secret') !== false) {
                    echo "  {$key}: " . ($value ? substr($value, 0, 15) . '...' : 'NULL') . "\n";
                } else {
                    echo "  {$key}: " . ($value ?? 'NULL') . "\n";
                }
            }
            echo "\n";
        }
    } else {
        echo "  ⚠️  Tabla vacía\n\n";
    }
} else {
    echo "4️⃣  TABLA: configuracion_facturacion\n";
    echo "─────────────────────────────────────────────────\n";
    echo "  ❌ No existe\n\n";
}

// 5. Buscar tabla settings o configuraciones
if (Schema::hasTable('settings')) {
    echo "5️⃣  TABLA: settings\n";
    echo "─────────────────────────────────────────────────\n";
    $settings = DB::table('settings')
        ->where('key', 'LIKE', '%alegra%')
        ->orWhere('key', 'LIKE', '%factura%')
        ->get();
    
    if (count($settings) > 0) {
        foreach ($settings as $setting) {
            echo "  {$setting->key}: {$setting->value}\n";
        }
    } else {
        echo "  ℹ️  Sin configuraciones de Alegra\n";
    }
    echo "\n";
}

// 6. Buscar en configuracion_dian
if (Schema::hasTable('configuracion_dian')) {
    echo "6️⃣  TABLA: configuracion_dian\n";
    echo "─────────────────────────────────────────────────\n";
    $dian = DB::table('configuracion_dian')->get();
    
    if (count($dian) > 0) {
        foreach ($dian as $config) {
            echo "  ID: {$config->id}\n";
            foreach ((array)$config as $key => $value) {
                if ($key === 'id') continue;
                echo "  {$key}: " . ($value ?? 'NULL') . "\n";
            }
            echo "\n";
        }
    } else {
        echo "  ⚠️  Tabla vacía\n\n";
    }
}

// 7. Análisis de inconsistencias
echo "═══════════════════════════════════════════════════════════\n";
echo "  🔍 ANÁLISIS DE INCONSISTENCIAS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$empresa = $empresas->first();
$inconsistencias = [];

// Comparar empresa vs .env
if ($empresa->alegra_email != $envAlegraUser && $envAlegraUser) {
    $inconsistencias[] = "❌ Email en empresa ({$empresa->alegra_email}) ≠ .env ({$envAlegraUser})";
}

if ($empresa->alegra_token != $envAlegraToken && $envAlegraToken) {
    $inconsistencias[] = "❌ Token en empresa ≠ .env";
}

// Comparar empresa vs config
if ($empresa->alegra_email != $configUser && $configUser) {
    $inconsistencias[] = "❌ Email en empresa ({$empresa->alegra_email}) ≠ config ({$configUser})";
}

if ($empresa->alegra_token != $configToken && $configToken) {
    $inconsistencias[] = "❌ Token en empresa ≠ config";
}

if (empty($inconsistencias)) {
    echo "✅ No se encontraron inconsistencias obvias\n";
    echo "   Todas las fuentes apuntan a: {$empresa->alegra_email}\n\n";
} else {
    echo "⚠️  INCONSISTENCIAS ENCONTRADAS:\n\n";
    foreach ($inconsistencias as $inc) {
        echo "   {$inc}\n";
    }
    echo "\n";
}

// 8. Recomendación
echo "═══════════════════════════════════════════════════════════\n";
echo "  💡 RECOMENDACIÓN\n";
echo "═══════════════════════════════════════════════════════════\n\n";
echo "La ÚNICA fuente de verdad debe ser:\n";
echo "  📍 Tabla: empresas\n";
echo "  📍 Email: {$empresa->alegra_email}\n";
echo "  📍 Token: " . substr($empresa->alegra_token, 0, 20) . "...\n\n";
echo "Todos los servicios deben consultar esta tabla.\n";
