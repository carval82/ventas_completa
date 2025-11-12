<?php

// Importar las clases necesarias
require_once __DIR__ . '/../vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AlegraService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

// Aumentar el tiempo máximo de ejecución a 5 minutos
set_time_limit(300);

// Aumentar el límite de memoria
ini_set('memory_limit', '512M');

// Crear una instancia del servicio Alegra
$alegraService = new AlegraService();

// Probar la conexión
echo "Probando conexión con Alegra...\n";
$resultado = $alegraService->probarConexion();

if ($resultado['success']) {
    echo "Conexión exitosa con Alegra.\n";
} else {
    echo "Error al conectar con Alegra: " . ($resultado['message'] ?? 'Error desconocido') . "\n";
    exit(1);
}

// Intentar obtener productos con límite pequeño
echo "\nObteniendo productos de Alegra (límite 10)...\n";
try {
    $resultado = $alegraService->obtenerProductos(['limit' => 10]);
    
    echo "Respuesta recibida.\n";
    echo "Éxito: " . ($resultado['success'] ? 'Sí' : 'No') . "\n";
    echo "Mensaje: " . ($resultado['message'] ?? 'No disponible') . "\n";
    
    if ($resultado['success']) {
        $productos = $resultado['data'] ?? [];
        echo "Número de productos obtenidos: " . count($productos) . "\n";
        
        // Mostrar los primeros 3 productos como ejemplo
        if (!empty($productos)) {
            echo "\nPrimeros productos (hasta 3):\n";
            $contador = 0;
            foreach ($productos as $producto) {
                echo "- " . ($producto['name'] ?? 'Sin nombre') . " (ID: " . ($producto['id'] ?? 'N/A') . ")\n";
                $contador++;
                if ($contador >= 3) break;
            }
        }
    } else {
        echo "Error al obtener productos: " . ($resultado['message'] ?? 'Error desconocido') . "\n";
        if (isset($resultado['error']) && !empty($resultado['error'])) {
            echo "Error detallado: " . $resultado['error'] . "\n";
        }
    }
} catch (\Exception $e) {
    echo "Excepción al obtener productos: " . $e->getMessage() . "\n";
    echo "Traza: " . $e->getTraceAsString() . "\n";
}

echo "\nPrueba completada.\n";
