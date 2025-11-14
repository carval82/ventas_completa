<?php
/**
 * Script para verificar y corregir la configuración de URL base
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "╔═══════════════════════════════════════════╗\n";
echo "║  VERIFICACIÓN: URL BASE Y RUTAS           ║\n";
echo "╚═══════════════════════════════════════════╝\n\n";

// 1. Verificar URL configurada
echo "🔍 CONFIGURACIÓN ACTUAL:\n";
echo "─────────────────────────────────────────\n";

$appUrl = config('app.url');
$appEnv = config('app.env');

echo "App URL (config): {$appUrl}\n";
echo "Entorno: {$appEnv}\n";
echo "URL actual: " . url('/') . "\n";
echo "Request URL: " . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'N/A') . "\n\n";

// 2. Verificar archivo .env
echo "🔍 ARCHIVO .ENV:\n";
echo "─────────────────────────────────────────\n";

$envPath = base_path('.env');
if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);
    
    if (preg_match('/^APP_URL=(.+)$/m', $envContent, $matches)) {
        $envUrl = trim($matches[1]);
        echo "✅ APP_URL encontrado: {$envUrl}\n";
        
        // Verificar si está correctamente configurado
        if (strpos($envUrl, 'localhost') !== false) {
            if (strpos($envUrl, '/public') !== false) {
                echo "⚠️  PROBLEMA: APP_URL incluye '/public'\n";
                echo "   Debería ser: http://localhost (sin /public)\n";
            } else {
                echo "✅ APP_URL parece correcto\n";
            }
        }
    } else {
        echo "❌ APP_URL no encontrado en .env\n";
    }
} else {
    echo "❌ Archivo .env no encontrado\n";
}

echo "\n";

// 3. Probar generación de rutas
echo "🔍 PRUEBA DE GENERACIÓN DE RUTAS:\n";
echo "─────────────────────────────────────────\n";

$venta = \App\Models\Venta::latest()->first();

if ($venta) {
    echo "Usando venta ID: {$venta->id}\n\n";
    
    // Método 1: route() helper
    try {
        $routeUrl = route('ventas.print', $venta->id);
        echo "✅ route('ventas.print'): {$routeUrl}\n";
    } catch (\Exception $e) {
        echo "❌ route('ventas.print'): Error - " . $e->getMessage() . "\n";
    }
    
    // Método 2: url() helper
    $urlHelper = url("/ventas/{$venta->id}/print");
    echo "✅ url() helper: {$urlHelper}\n";
    
    // Método 3: URL relativa
    $relativeUrl = "/ventas/{$venta->id}/print";
    echo "ℹ️  URL relativa: {$relativeUrl}\n";
    
    // Verificar cuál es la correcta
    echo "\n📊 ANÁLISIS:\n";
    
    $currentHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $expectedUrl = "http://{$currentHost}/ventas/{$venta->id}/print";
    
    echo "URL esperada: {$expectedUrl}\n";
    
    if (isset($routeUrl) && $routeUrl === $expectedUrl) {
        echo "✅ route() genera la URL correcta\n";
    } else {
        echo "⚠️  route() NO coincide con la esperada\n";
        if (isset($routeUrl)) {
            echo "   Generada: {$routeUrl}\n";
            echo "   Esperada: {$expectedUrl}\n";
        }
    }
} else {
    echo "⚠️  No hay ventas para probar\n";
}

echo "\n";

// 4. Verificar .htaccess
echo "🔍 VERIFICACIÓN .HTACCESS:\n";
echo "─────────────────────────────────────────\n";

$htaccessPath = public_path('.htaccess');
if (file_exists($htaccessPath)) {
    $htaccess = file_get_contents($htaccessPath);
    
    if (strpos($htaccess, 'RewriteEngine On') !== false) {
        echo "✅ RewriteEngine está activado\n";
    } else {
        echo "❌ RewriteEngine NO encontrado\n";
    }
    
    if (strpos($htaccess, 'RewriteRule ^ index.php') !== false) {
        echo "✅ RewriteRule a index.php configurada\n";
    } else {
        echo "❌ RewriteRule a index.php NO encontrada\n";
    }
} else {
    echo "❌ Archivo .htaccess NO existe en /public\n";
}

echo "\n";

// 5. Recomendaciones
echo "╔═══════════════════════════════════════════╗\n";
echo "║  RECOMENDACIONES                          ║\n";
echo "╚═══════════════════════════════════════════╝\n\n";

$problemas = [];
$soluciones = [];

// Verificar URL en .env
if (isset($envUrl)) {
    if (strpos($envUrl, '/public') !== false) {
        $problemas[] = "APP_URL incluye '/public'";
        $soluciones[] = "Editar .env y cambiar APP_URL a http://localhost (sin /public)";
    }
    
    if ($envUrl !== config('app.url')) {
        $problemas[] = "APP_URL en .env no coincide con config";
        $soluciones[] = "Ejecutar: php artisan config:clear";
    }
}

// Verificar .htaccess
if (!file_exists($htaccessPath)) {
    $problemas[] = "Archivo .htaccess faltante";
    $soluciones[] = "Ejecutar: php solucionar_404_impresion.php";
}

if (count($problemas) > 0) {
    echo "⚠️  PROBLEMAS ENCONTRADOS:\n";
    foreach ($problemas as $i => $problema) {
        echo "   " . ($i + 1) . ". {$problema}\n";
    }
    echo "\n";
    
    echo "🔧 SOLUCIONES:\n";
    foreach ($soluciones as $i => $solucion) {
        echo "   " . ($i + 1) . ". {$solucion}\n";
    }
} else {
    echo "✅ No se encontraron problemas obvios\n\n";
    echo "Si aún tienes error 404, verifica:\n";
    echo "1. Que Apache tenga mod_rewrite habilitado\n";
    echo "2. Que el DocumentRoot apunte a /public\n";
    echo "3. Que AllowOverride esté en 'All'\n";
}

echo "\n";

// 6. Comando de corrección rápida
echo "╔═══════════════════════════════════════════╗\n";
echo "║  CORRECCIÓN RÁPIDA                        ║\n";
echo "╚═══════════════════════════════════════════╝\n\n";

echo "Ejecutar estos comandos:\n\n";
echo "1. Limpiar caché de configuración:\n";
echo "   php artisan config:clear\n";
echo "   php artisan route:clear\n";
echo "   php artisan cache:clear\n\n";

echo "2. Verificar .env:\n";
echo "   APP_URL=http://localhost\n";
echo "   (SIN /public al final)\n\n";

echo "3. Regenerar caché:\n";
echo "   php artisan config:cache\n";
echo "   php artisan route:cache\n\n";

echo "4. Probar en navegador incógnito:\n";
echo "   Ctrl + Shift + N\n\n";

echo "═══════════════════════════════════════════\n\n";
