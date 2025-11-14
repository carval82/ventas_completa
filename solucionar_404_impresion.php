<?php
/**
 * Script automático para solucionar error 404 en impresión de facturas
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

echo "╔═══════════════════════════════════════════╗\n";
echo "║  SOLUCIÓN AUTOMÁTICA: ERROR 404 IMPRESIÓN║\n";
echo "╚═══════════════════════════════════════════╝\n\n";

$pasoActual = 1;
$erroresEncontrados = [];
$solucionesAplicadas = [];

// ============================================
// PASO 1: Limpiar todas las cachés
// ============================================
echo "🔧 PASO {$pasoActual}: Limpiando cachés...\n";
echo "─────────────────────────────────────────\n";

try {
    Artisan::call('optimize:clear');
    echo "✅ Cache general limpiada\n";
    $solucionesAplicadas[] = "Cache general limpiada";
    
    Artisan::call('route:clear');
    echo "✅ Cache de rutas limpiada\n";
    
    Artisan::call('view:clear');
    echo "✅ Cache de vistas limpiada\n";
    
    Artisan::call('config:clear');
    echo "✅ Cache de configuración limpiada\n\n";
} catch (\Exception $e) {
    echo "⚠️  Error al limpiar cachés: " . $e->getMessage() . "\n\n";
    $erroresEncontrados[] = "Error al limpiar cachés";
}

$pasoActual++;

// ============================================
// PASO 2: Verificar archivo .htaccess
// ============================================
echo "🔧 PASO {$pasoActual}: Verificando archivo .htaccess...\n";
echo "─────────────────────────────────────────\n";

$htaccessPath = public_path('.htaccess');

if (File::exists($htaccessPath)) {
    echo "✅ Archivo .htaccess existe\n";
    
    $contenido = File::get($htaccessPath);
    
    if (strpos($contenido, 'RewriteEngine On') !== false) {
        echo "✅ RewriteEngine está activado\n";
    } else {
        echo "⚠️  RewriteEngine NO encontrado en .htaccess\n";
        $erroresEncontrados[] = ".htaccess sin RewriteEngine";
    }
    
    if (strpos($contenido, 'mod_rewrite.c') !== false) {
        echo "✅ Módulo mod_rewrite configurado\n";
    } else {
        echo "⚠️  Módulo mod_rewrite NO configurado\n";
        $erroresEncontrados[] = "mod_rewrite no configurado";
    }
} else {
    echo "❌ Archivo .htaccess NO EXISTE\n";
    echo "🔧 Creando archivo .htaccess...\n";
    
    $htaccessContent = <<<'HTACCESS'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
HTACCESS;
    
    try {
        File::put($htaccessPath, $htaccessContent);
        echo "✅ Archivo .htaccess creado correctamente\n";
        $solucionesAplicadas[] = ".htaccess creado";
    } catch (\Exception $e) {
        echo "❌ Error al crear .htaccess: " . $e->getMessage() . "\n";
        $erroresEncontrados[] = "No se pudo crear .htaccess";
    }
}

echo "\n";
$pasoActual++;

// ============================================
// PASO 3: Verificar permisos
// ============================================
echo "🔧 PASO {$pasoActual}: Verificando permisos...\n";
echo "─────────────────────────────────────────\n";

$directoriosImportantes = [
    'storage',
    'bootstrap/cache',
    'public'
];

foreach ($directoriosImportantes as $dir) {
    $ruta = base_path($dir);
    if (File::exists($ruta)) {
        if (is_writable($ruta)) {
            echo "✅ {$dir} - Permisos OK\n";
        } else {
            echo "⚠️  {$dir} - SIN PERMISOS DE ESCRITURA\n";
            $erroresEncontrados[] = "{$dir} sin permisos";
        }
    } else {
        echo "❌ {$dir} - NO EXISTE\n";
    }
}

echo "\n";
$pasoActual++;

// ============================================
// PASO 4: Regenerar cache de rutas
// ============================================
echo "🔧 PASO {$pasoActual}: Regenerando cache de rutas...\n";
echo "─────────────────────────────────────────\n";

try {
    Artisan::call('route:cache');
    echo "✅ Cache de rutas regenerada\n";
    $solucionesAplicadas[] = "Cache de rutas regenerada";
} catch (\Exception $e) {
    echo "⚠️  Error al regenerar rutas: " . $e->getMessage() . "\n";
    $erroresEncontrados[] = "Error al regenerar rutas";
}

try {
    Artisan::call('config:cache');
    echo "✅ Cache de configuración regenerada\n\n";
} catch (\Exception $e) {
    echo "⚠️  Error al regenerar config: " . $e->getMessage() . "\n\n";
}

$pasoActual++;

// ============================================
// PASO 5: Verificar rutas de impresión
// ============================================
echo "🔧 PASO {$pasoActual}: Verificando rutas de impresión...\n";
echo "─────────────────────────────────────────\n";

use Illuminate\Support\Facades\Route;

$rutasImportantes = [
    'ventas.print',
    'ventas.print-58mm',
    'ventas.print-80mm',
    'ventas.print-media-carta'
];

$rutasOK = 0;
foreach ($rutasImportantes as $nombreRuta) {
    if (Route::has($nombreRuta)) {
        echo "✅ {$nombreRuta}\n";
        $rutasOK++;
    } else {
        echo "❌ {$nombreRuta} - NO ENCONTRADA\n";
        $erroresEncontrados[] = "Ruta {$nombreRuta} no encontrada";
    }
}

echo "\n";

// ============================================
// RESUMEN
// ============================================
echo "╔═══════════════════════════════════════════╗\n";
echo "║  RESUMEN DE LA SOLUCIÓN                   ║\n";
echo "╚═══════════════════════════════════════════╝\n\n";

if (count($solucionesAplicadas) > 0) {
    echo "✅ SOLUCIONES APLICADAS:\n";
    foreach ($solucionesAplicadas as $solucion) {
        echo "   • {$solucion}\n";
    }
    echo "\n";
}

if (count($erroresEncontrados) > 0) {
    echo "⚠️  ADVERTENCIAS/ERRORES ENCONTRADOS:\n";
    foreach ($erroresEncontrados as $error) {
        echo "   • {$error}\n";
    }
    echo "\n";
}

echo "📊 ESTADO FINAL:\n";
echo "   • Rutas funcionando: {$rutasOK}/" . count($rutasImportantes) . "\n";
echo "   • Errores encontrados: " . count($erroresEncontrados) . "\n\n";

// ============================================
// INSTRUCCIONES FINALES
// ============================================
echo "╔═══════════════════════════════════════════╗\n";
echo "║  PRÓXIMOS PASOS                           ║\n";
echo "╚═══════════════════════════════════════════╝\n\n";

if (count($erroresEncontrados) === 0 && $rutasOK === count($rutasImportantes)) {
    echo "🎉 ¡TODO SOLUCIONADO!\n\n";
    echo "✅ Ahora puedes probar:\n";
    echo "   1. Abre el navegador en modo INCÓGNITO\n";
    echo "      (Ctrl + Shift + N en Chrome)\n";
    echo "   2. Ve a: " . url('/ventas') . "\n";
    echo "   3. Haz clic en Imprimir\n";
    echo "   4. ¡Debería funcionar! 🚀\n\n";
} else {
    echo "⚠️  REQUIERE ATENCIÓN ADICIONAL:\n\n";
    
    if (in_array(".htaccess sin RewriteEngine", $erroresEncontrados)) {
        echo "1️⃣ Verificar que mod_rewrite esté habilitado en Apache:\n";
        echo "   • Linux: sudo a2enmod rewrite && sudo systemctl restart apache2\n";
        echo "   • Windows/XAMPP: Editar httpd.conf y descomentar mod_rewrite\n\n";
    }
    
    if (strpos(implode(',', $erroresEncontrados), 'permisos') !== false) {
        echo "2️⃣ Corregir permisos (Linux):\n";
        echo "   sudo chown -R www-data:www-data " . base_path() . "\n";
        echo "   sudo chmod -R 755 " . base_path() . "\n";
        echo "   sudo chmod -R 775 " . base_path('storage') . "\n";
        echo "   sudo chmod -R 775 " . base_path('bootstrap/cache') . "\n\n";
    }
    
    echo "3️⃣ Reiniciar el servidor web:\n";
    echo "   • Linux: sudo systemctl restart apache2\n";
    echo "   • XAMPP: Detener y reiniciar Apache desde el panel\n\n";
    
    echo "4️⃣ Luego ejecutar nuevamente:\n";
    echo "   php solucionar_404_impresion.php\n\n";
}

echo "═══════════════════════════════════════════\n\n";

// URL de prueba
$venta = \App\Models\Venta::latest()->first();
if ($venta) {
    echo "🧪 URL DE PRUEBA:\n";
    echo "   " . route('ventas.print', $venta->id) . "\n\n";
}

echo "💡 RECUERDA: Siempre probar en modo incógnito para evitar cachés del navegador.\n\n";
