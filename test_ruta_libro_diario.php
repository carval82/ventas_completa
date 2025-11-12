<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== VERIFICACIÓN DE RUTA LIBRO DIARIO ===\n\n";

// 1. Verificar que la ruta existe
echo "🔍 1. VERIFICANDO RUTA...\n";
try {
    $url = route('reportes.libro-diario');
    echo "  ✅ Ruta generada: {$url}\n";
} catch (\Exception $e) {
    echo "  ❌ Error generando ruta: " . $e->getMessage() . "\n";
}

// 2. Verificar el controlador y método
echo "\n🎯 2. VERIFICANDO CONTROLADOR...\n";
$router = app('router');
$routes = $router->getRoutes();

foreach ($routes as $route) {
    if ($route->getName() === 'reportes.libro-diario') {
        echo "  ✅ Ruta encontrada:\n";
        echo "    - URI: " . $route->uri() . "\n";
        echo "    - Métodos: " . implode(', ', $route->methods()) . "\n";
        echo "    - Acción: " . $route->getActionName() . "\n";
        echo "    - Middleware: " . implode(', ', $route->middleware()) . "\n";
        break;
    }
}

// 3. Verificar que el método existe en el controlador
echo "\n📋 3. VERIFICANDO MÉTODO DEL CONTROLADOR...\n";
$controllerClass = 'App\Http\Controllers\Contabilidad\ReporteContableController';
if (class_exists($controllerClass)) {
    echo "  ✅ Controlador existe: {$controllerClass}\n";
    
    if (method_exists($controllerClass, 'libro_diario')) {
        echo "  ✅ Método libro_diario existe\n";
    } else {
        echo "  ❌ Método libro_diario NO existe\n";
    }
} else {
    echo "  ❌ Controlador NO existe: {$controllerClass}\n";
}

// 4. Verificar la vista
echo "\n👁️ 4. VERIFICANDO VISTA...\n";
$vistaPath = resource_path('views/contabilidad/reportes/libro_diario.blade.php');
if (file_exists($vistaPath)) {
    echo "  ✅ Vista existe: {$vistaPath}\n";
    $size = filesize($vistaPath);
    echo "  📏 Tamaño: " . number_format($size) . " bytes\n";
} else {
    echo "  ❌ Vista NO existe: {$vistaPath}\n";
}

// 5. Simular una petición HTTP
echo "\n🌐 5. SIMULANDO PETICIÓN HTTP...\n";
try {
    $request = \Illuminate\Http\Request::create('/contabilidad/reportes/libro-diario', 'GET');
    $response = $app->handle($request);
    
    echo "  📊 Código de respuesta: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() === 200) {
        echo "  ✅ Respuesta exitosa\n";
    } elseif ($response->getStatusCode() === 302) {
        echo "  🔄 Redirección detectada\n";
        $location = $response->headers->get('Location');
        if ($location) {
            echo "  📍 Redirige a: {$location}\n";
        }
    } else {
        echo "  ❌ Error en respuesta\n";
    }
    
} catch (\Exception $e) {
    echo "  ❌ Error en petición: " . $e->getMessage() . "\n";
}

echo "\n🎯 RESUMEN:\n";
echo "📋 URL esperada: http://127.0.0.1:8000/contabilidad/reportes/libro-diario\n";
echo "🔗 Ruta nombrada: reportes.libro-diario\n";
echo "🎮 Controlador: ReporteContableController@libro_diario\n";
echo "👁️ Vista: contabilidad.reportes.libro_diario\n";

echo "\n✅ Verificación completada.\n";
