<?php
/**
 * Script para diagnosticar problemas con la impresión de facturas
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

echo "╔═══════════════════════════════════════════╗\n";
echo "║  DIAGNÓSTICO: IMPRESIÓN DE FACTURAS       ║\n";
echo "╚═══════════════════════════════════════════╝\n\n";

// 1. Verificar rutas de impresión
echo "🔍 VERIFICANDO RUTAS DE IMPRESIÓN:\n";
echo "─────────────────────────────────────────\n";

$rutasImportantes = [
    'ventas.print',
    'ventas.print-58mm',
    'ventas.print-80mm',
    'ventas.print-media-carta'
];

foreach ($rutasImportantes as $nombreRuta) {
    if (Route::has($nombreRuta)) {
        $ruta = Route::getRoutes()->getByName($nombreRuta);
        echo "✅ {$nombreRuta}\n";
        echo "   URI: " . $ruta->uri() . "\n";
        echo "   Método: " . implode(', ', $ruta->methods()) . "\n";
    } else {
        echo "❌ {$nombreRuta} - NO ENCONTRADA\n";
    }
}

// 2. Verificar vistas de impresión
echo "\n🔍 VERIFICANDO VISTAS DE IMPRESIÓN:\n";
echo "─────────────────────────────────────────\n";

$vistas = [
    'ventas.print' => 'resources/views/ventas/print.blade.php',
    'ventas.print_58mm' => 'resources/views/ventas/print_58mm.blade.php',
    'ventas.print_factura_electronica' => 'resources/views/ventas/print_factura_electronica.blade.php',
    'ventas.print_media_carta' => 'resources/views/ventas/print_media_carta.blade.php'
];

foreach ($vistas as $nombre => $ruta) {
    $rutaCompleta = base_path($ruta);
    if (File::exists($rutaCompleta)) {
        $tamaño = File::size($rutaCompleta);
        echo "✅ {$nombre}\n";
        echo "   Ubicación: {$ruta}\n";
        echo "   Tamaño: " . number_format($tamaño / 1024, 2) . " KB\n";
    } else {
        echo "❌ {$nombre} - ARCHIVO NO ENCONTRADO\n";
        echo "   Buscado en: {$ruta}\n";
    }
}

// 3. Verificar controlador
echo "\n🔍 VERIFICANDO CONTROLADOR:\n";
echo "─────────────────────────────────────────\n";

$controlador = app_path('Http/Controllers/VentaController.php');
if (File::exists($controlador)) {
    echo "✅ VentaController.php existe\n";
    
    $contenido = File::get($controlador);
    
    $metodos = [
        'print',
        'print58mm',
        'print80mm',
        'printMediaCarta',
        'generarQRImagen'
    ];
    
    echo "\n   Métodos disponibles:\n";
    foreach ($metodos as $metodo) {
        if (strpos($contenido, "function {$metodo}") !== false) {
            echo "   ✅ {$metodo}()\n";
        } else {
            echo "   ❌ {$metodo}() - NO ENCONTRADO\n";
        }
    }
} else {
    echo "❌ VentaController.php NO ENCONTRADO\n";
}

// 4. Verificar empresa y configuración
echo "\n🔍 VERIFICANDO CONFIGURACIÓN:\n";
echo "─────────────────────────────────────────\n";

$empresa = \App\Models\Empresa::first();
if ($empresa) {
    echo "✅ Empresa configurada\n";
    echo "   Nombre: {$empresa->nombre_comercial}\n";
    echo "   Formato impresión: " . ($empresa->formato_impresion ?? 'No definido') . "\n";
    echo "   Usar formato electrónico: " . ($empresa->usar_formato_electronico ? 'SÍ' : 'NO') . "\n";
} else {
    echo "⚠️  No hay empresa configurada\n";
}

// 5. Verificar última venta
echo "\n🔍 VERIFICANDO VENTAS:\n";
echo "─────────────────────────────────────────\n";

$venta = \App\Models\Venta::latest()->first();
if ($venta) {
    echo "✅ Última venta: #{$venta->id}\n";
    echo "   Total: \$" . number_format($venta->total, 2) . "\n";
    echo "   Fecha: {$venta->created_at}\n";
    
    // Probar URL de impresión
    $urlPrint = route('ventas.print', $venta->id);
    echo "   URL impresión: {$urlPrint}\n";
    
    // Verificar si es electrónica
    if (method_exists($venta, 'esFacturaElectronica')) {
        $esElectronica = $venta->esFacturaElectronica();
        echo "   Es electrónica: " . ($esElectronica ? 'SÍ' : 'NO') . "\n";
    }
} else {
    echo "⚠️  No hay ventas registradas\n";
}

// 6. Verificar cache
echo "\n🔍 VERIFICANDO CACHÉ:\n";
echo "─────────────────────────────────────────\n";

$archivosCache = [
    'bootstrap/cache/routes-v7.php' => 'Cache de rutas',
    'bootstrap/cache/config.php' => 'Cache de config',
    'storage/framework/views' => 'Views compiladas'
];

foreach ($archivosCache as $archivo => $descripcion) {
    $ruta = base_path($archivo);
    if (File::exists($ruta)) {
        if (File::isDirectory($ruta)) {
            $count = count(File::allFiles($ruta));
            echo "⚠️  {$descripcion}: {$count} archivos\n";
        } else {
            echo "⚠️  {$descripcion}: existe\n";
        }
    } else {
        echo "✅ {$descripcion}: limpio\n";
    }
}

// RECOMENDACIONES
echo "\n╔═══════════════════════════════════════════╗\n";
echo "║  SOLUCIONES RECOMENDADAS                  ║\n";
echo "╚═══════════════════════════════════════════╝\n\n";

echo "🔧 EJECUTAR LOS SIGUIENTES COMANDOS:\n\n";

echo "1️⃣ Limpiar todas las cachés:\n";
echo "   php artisan optimize:clear\n\n";

echo "2️⃣ Limpiar cache específica:\n";
echo "   php artisan route:clear\n";
echo "   php artisan view:clear\n";
echo "   php artisan config:clear\n\n";

echo "3️⃣ Recargar las rutas:\n";
echo "   php artisan route:cache\n\n";

echo "4️⃣ Si persiste, reiniciar el servidor:\n";
echo "   (En XAMPP: Detener Apache y reiniciar)\n\n";

echo "5️⃣ Probar en navegador incógnito:\n";
echo "   Ctrl + Shift + N (Chrome)\n";
echo "   Ctrl + Shift + P (Firefox)\n\n";

echo "═══════════════════════════════════════════\n\n";
