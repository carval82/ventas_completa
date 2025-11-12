<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Cargar el entorno de Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Empresa;
use App\Services\AlegraService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

// Configurar logging para ver resultados detallados
Log::info('Iniciando sincronización con Alegra');

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

// Función para sincronizar clientes
function sincronizarClientes($alegraService) {
    echo "=== SINCRONIZACIÓN DE CLIENTES ===\n";
    
    // Obtener clientes de Alegra
    echo "Obteniendo clientes de Alegra...\n";
    $clientesAlegra = $alegraService->obtenerClientes();
    
    if (!$clientesAlegra['success']) {
        echo "Error al obtener clientes de Alegra: " . ($clientesAlegra['message'] ?? 'Error desconocido') . "\n";
        return false;
    }
    
    $totalClientes = count($clientesAlegra['data']);
    echo "Se encontraron {$totalClientes} clientes en Alegra.\n";
    
    // Procesar clientes
    $clientesCreados = 0;
    $clientesActualizados = 0;
    
    foreach ($clientesAlegra['data'] as $clienteAlegra) {
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
            'telefono' => $clienteAlegra['mobile'] ?? ($clienteAlegra['phone'] ?? ''),
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
    
    echo "Sincronización de clientes completada.\n";
    echo "Clientes creados: {$clientesCreados}\n";
    echo "Clientes actualizados: {$clientesActualizados}\n\n";
    
    return true;
}

// Función para sincronizar productos
function sincronizarProductos($alegraService) {
    echo "=== SINCRONIZACIÓN DE PRODUCTOS ===\n";
    
    // Obtener productos de Alegra
    echo "Obteniendo productos de Alegra...\n";
    $productosAlegra = $alegraService->obtenerProductos();
    
    if (!$productosAlegra['success']) {
        echo "Error al obtener productos de Alegra: " . ($productosAlegra['message'] ?? 'Error desconocido') . "\n";
        return false;
    }
    
    $totalProductos = count($productosAlegra['data']);
    echo "Se encontraron {$totalProductos} productos en Alegra.\n";
    
    // Procesar productos
    $productosCreados = 0;
    $productosActualizados = 0;
    
    foreach ($productosAlegra['data'] as $productoAlegra) {
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
    
    echo "Sincronización de productos completada.\n";
    echo "Productos creados: {$productosCreados}\n";
    echo "Productos actualizados: {$productosActualizados}\n\n";
    
    return true;
}

// Ejecutar sincronización
try {
    DB::beginTransaction();
    
    // Sincronizar clientes
    $clientesSincronizados = sincronizarClientes($alegraService);
    
    // Sincronizar productos
    $productosSincronizados = sincronizarProductos($alegraService);
    
    if ($clientesSincronizados && $productosSincronizados) {
        DB::commit();
        echo "Sincronización completada exitosamente.\n";
    } else {
        DB::rollBack();
        echo "La sincronización no se completó correctamente.\n";
    }
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error durante la sincronización: " . $e->getMessage() . "\n";
}

echo "\nProceso finalizado.\n";
