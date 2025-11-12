<?php

// Importar las clases necesarias
require_once __DIR__ . '/../vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Empresa;
use App\Services\AlegraService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

// Aumentar el tiempo máximo de ejecución a 5 minutos
set_time_limit(300);

// Aumentar el límite de memoria
ini_set('memory_limit', '512M');

// Configurar logging para ver resultados detallados
Log::info('Iniciando importación de datos desde Alegra');

// Obtener la empresa
$empresa = Empresa::first();

if (!$empresa) {
    echo "Error: No hay empresa configurada en el sistema.\n";
    exit(1);
}

echo "Empresa: {$empresa->nombre}\n";

// Crear un servicio de Alegra
$alegraService = new AlegraService();

// Probar la conexión con Alegra
echo "Probando conexión con Alegra...\n";
$conexion = $alegraService->probarConexion();

if (!$conexion['success']) {
    echo "Error de conexión con Alegra: " . ($conexion['message'] ?? 'Error desconocido') . "\n";
    exit(1);
}

echo "Conexión exitosa con Alegra.\n\n";

// Función para importar clientes desde Alegra
function importarClientesDesdeAlegra($alegraService) {
    echo "=== IMPORTACIÓN DE CLIENTES DESDE ALEGRA ===\n";
    
    try {
        // Obtener clientes de Alegra usando el método existente
        echo "Obteniendo clientes de Alegra...\n";
        
        // Usar el método público obtenerClientes() del servicio AlegraService
        $resultado = $alegraService->obtenerClientes();
        
        if (!$resultado['success']) {
            echo "Error al obtener clientes de Alegra: " . ($resultado['message'] ?? 'Error desconocido') . "\n";
            return false;
        }
        
        $clientesAlegra = $resultado['data'];
        
        if (!$clientesAlegra || !is_array($clientesAlegra)) {
            echo "Error: No se encontraron clientes en Alegra o el formato de respuesta es incorrecto.\n";
            return false;
        }
        
        $totalClientes = count($clientesAlegra);
        echo "Se encontraron {$totalClientes} clientes en Alegra.\n";
        
        // Procesar clientes
        $clientesCreados = 0;
        $clientesActualizados = 0;
        
        foreach ($clientesAlegra as $clienteAlegra) {
            // Verificar si el cliente ya existe por ID de Alegra
            $clienteExistente = Cliente::where('id_alegra', $clienteAlegra['id'])->first();
            
            // Si no existe por ID de Alegra, buscar por identificación
            if (!$clienteExistente && isset($clienteAlegra['identification'])) {
                $clienteExistente = Cliente::where('cedula', $clienteAlegra['identification'])->first();
            }
            
            // Preparar datos del cliente
            $nombres = $clienteAlegra['name'] ?? '';
            $apellidos = '';
            
            // Intentar separar nombres y apellidos si es una persona
            if (isset($clienteAlegra['kindOfPerson']) && $clienteAlegra['kindOfPerson'] === 'PERSON') {
                $nombreCompleto = explode(' ', $nombres);
                if (count($nombreCompleto) > 1) {
                    $apellidos = array_pop($nombreCompleto);
                    $nombres = implode(' ', $nombreCompleto);
                }
            }
            
            $datosCliente = [
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'cedula' => $clienteAlegra['identification'] ?? '',
                'telefono' => $clienteAlegra['mobile'] ?? ($clienteAlegra['phonePrimary'] ?? ''),
                'email' => $clienteAlegra['email'] ?? '',
                'direccion' => isset($clienteAlegra['address']) ? ($clienteAlegra['address']['address'] ?? '') : '',
                'id_alegra' => $clienteAlegra['id'],
                'estado' => 1
            ];
            
            if ($clienteExistente) {
                // Actualizar cliente existente
                $clienteExistente->update($datosCliente);
                $clientesActualizados++;
                echo "Cliente actualizado: {$datosCliente['nombres']} {$datosCliente['apellidos']} (ID Alegra: {$datosCliente['id_alegra']})\n";
            } else {
                // Crear nuevo cliente
                Cliente::create($datosCliente);
                $clientesCreados++;
                echo "Cliente creado: {$datosCliente['nombres']} {$datosCliente['apellidos']} (ID Alegra: {$datosCliente['id_alegra']})\n";
            }
        }
        
        echo "Importación de clientes completada.\n";
        echo "Clientes creados: {$clientesCreados}\n";
        echo "Clientes actualizados: {$clientesActualizados}\n\n";
        
        return true;
    } catch (\Exception $e) {
        echo "Error durante la importación de clientes: " . $e->getMessage() . "\n";
        echo "Línea: " . $e->getLine() . "\n";
        echo "Archivo: " . $e->getFile() . "\n";
        return false;
    }
}

// Función para importar productos desde Alegra
function importarProductosDesdeAlegra($alegraService) {
    echo "=== IMPORTACIÓN DE PRODUCTOS DESDE ALEGRA ===\n";
    
    try {
        // Obtener productos de Alegra con un límite pequeño (10 productos)
        echo "Obteniendo productos de Alegra (límite 10)...\n";
        
        // Usar el método público obtenerProductos() con un límite pequeño
        $resultado = $alegraService->obtenerProductos(['limit' => 10]);
        
        if (!isset($resultado['success'])) {
            echo "Formato de respuesta inesperado.\n";
            return false;
        }
        
        echo "Éxito de la respuesta: " . ($resultado['success'] ? 'Sí' : 'No') . "\n";
        
        if (!$resultado['success']) {
            echo "Error al obtener productos de Alegra: " . ($resultado['message'] ?? 'Error desconocido') . "\n";
            return false;
        }
        
        $productosAlegra = $resultado['data'] ?? [];
        
        if (empty($productosAlegra)) {
            echo "No se encontraron productos en Alegra.\n";
            return false;
        }
        
        $totalProductos = count($productosAlegra);
        echo "Se encontraron {$totalProductos} productos en Alegra para importar.\n";
        
        $totalProductos = count($productosAlegra);
        echo "Se encontraron {$totalProductos} productos en Alegra.\n";
        
        // Procesar productos
        $productosCreados = 0;
        $productosActualizados = 0;
        
        foreach ($productosAlegra as $productoAlegra) {
            // Verificar si el producto ya existe por ID de Alegra
            $productoExistente = Producto::where('id_alegra', $productoAlegra['id'])->first();
            
            // Si no existe por ID de Alegra, buscar por nombre
            if (!$productoExistente) {
                $productoExistente = Producto::where('nombre', $productoAlegra['name'])->first();
            }
            
            // Preparar datos del producto
            $datosProducto = [
                'nombre' => $productoAlegra['name'],
                'descripcion' => $productoAlegra['description'] ?? '',
                'precio' => $productoAlegra['price'] ?? 0,
                'stock' => 0, // El stock no se sincroniza desde Alegra
                'id_alegra' => $productoAlegra['id'],
                'estado' => 1
            ];
            
            if ($productoExistente) {
                // Actualizar producto existente
                $productoExistente->update($datosProducto);
                $productosActualizados++;
                echo "Producto actualizado: {$datosProducto['nombre']} (ID Alegra: {$datosProducto['id_alegra']})\n";
            } else {
                // Crear nuevo producto
                Producto::create($datosProducto);
                $productosCreados++;
                echo "Producto creado: {$datosProducto['nombre']} (ID Alegra: {$datosProducto['id_alegra']})\n";
            }
        }
        
        echo "Importación de productos completada.\n";
        echo "Productos creados: {$productosCreados}\n";
        echo "Productos actualizados: {$productosActualizados}\n\n";
        
        return true;
    } catch (\Exception $e) {
        echo "Error durante la importación de productos: " . $e->getMessage() . "\n";
        echo "Línea: " . $e->getLine() . "\n";
        echo "Archivo: " . $e->getFile() . "\n";
        return false;
    }
}

// Ejecutar importación
try {
    DB::beginTransaction();
    
    // Importar clientes
    $clientesImportados = importarClientesDesdeAlegra($alegraService);
    
    // Importar productos
    $productosImportados = importarProductosDesdeAlegra($alegraService);
    
    if ($clientesImportados && $productosImportados) {
        DB::commit();
        echo "Importación completada exitosamente.\n";
    } else {
        DB::rollBack();
        echo "La importación no se completó correctamente.\n";
    }
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error durante la importación: " . $e->getMessage() . "\n";
}

echo "\nProceso finalizado.\n";
