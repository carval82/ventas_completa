<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Cargar el entorno de Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Empresa;
use App\Services\AlegraService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

// Configurar logging para ver resultados detallados
Log::info('Iniciando prueba de creación de factura real con IVA');

// Obtener la empresa
$empresa = Empresa::first();

if (!$empresa) {
    echo "Error: No hay empresa configurada en el sistema.\n";
    exit(1);
}

echo "Empresa: {$empresa->nombre}\n";
echo "Régimen tributario: {$empresa->regimen_tributario}\n";

// Verificar si la empresa es responsable de IVA
$esResponsableIVA = ($empresa->regimen_tributario === 'responsable_iva');
echo "Es responsable de IVA: " . ($esResponsableIVA ? 'Sí' : 'No') . "\n";

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

// Buscar un cliente existente o crear uno nuevo
echo "Buscando cliente para la prueba...\n";
$cliente = Cliente::where('id_alegra', '>', 0)->first();

if (!$cliente) {
    echo "No se encontró un cliente con ID de Alegra. Buscando cualquier cliente...\n";
    $cliente = Cliente::first();
    
    if (!$cliente) {
        echo "No hay clientes en el sistema. Creando un cliente de prueba...\n";
        
        $cliente = Cliente::create([
            'nombres' => 'Cliente',
            'apellidos' => 'Prueba',
            'cedula' => '1234567890',
            'telefono' => '3001234567',
            'email' => 'cliente.prueba@example.com',
            'direccion' => 'Calle de prueba 123',
            'estado' => 1
        ]);
        
        echo "Cliente de prueba creado con ID: {$cliente->id}\n";
    }
    
    // Sincronizar cliente con Alegra
    echo "Sincronizando cliente con Alegra...\n";
    $sincronizacion = $cliente->syncToAlegra();
    
    if (!$sincronizacion['success']) {
        echo "Error al sincronizar cliente con Alegra: " . ($sincronizacion['message'] ?? 'Error desconocido') . "\n";
        exit(1);
    }
    
    echo "Cliente sincronizado con Alegra. ID Alegra: {$cliente->id_alegra}\n";
}

echo "Cliente seleccionado: {$cliente->nombres} {$cliente->apellidos} (ID: {$cliente->id}, ID Alegra: {$cliente->id_alegra})\n\n";

// Buscar un producto existente o crear uno nuevo
echo "Buscando producto para la prueba...\n";
$producto = Producto::where('id_alegra', '>', 0)->first();

if (!$producto) {
    echo "No se encontró un producto con ID de Alegra. Buscando cualquier producto...\n";
    $producto = Producto::first();
    
    if (!$producto) {
        echo "No hay productos en el sistema. Creando un producto de prueba...\n";
        
        $producto = Producto::create([
            'nombre' => 'Producto de Prueba',
            'descripcion' => 'Producto creado para pruebas',
            'precio' => 50000,
            'stock' => 100,
            'estado' => 1
        ]);
        
        echo "Producto de prueba creado con ID: {$producto->id}\n";
    }
    
    // Sincronizar producto con Alegra
    echo "Sincronizando producto con Alegra...\n";
    $sincronizacion = $producto->syncToAlegra();
    
    if (!$sincronizacion['success']) {
        echo "Error al sincronizar producto con Alegra: " . ($sincronizacion['message'] ?? 'Error desconocido') . "\n";
        exit(1);
    }
    
    echo "Producto sincronizado con Alegra. ID Alegra: {$producto->id_alegra}\n";
}

echo "Producto seleccionado: {$producto->nombre} (ID: {$producto->id}, ID Alegra: {$producto->id_alegra})\n\n";

// Crear una venta de prueba
echo "Creando venta de prueba...\n";

try {
    DB::beginTransaction();
    
    // Datos de la venta
    $cantidad = 2;
    $precioUnitario = $producto->precio;
    $subtotal = $cantidad * $precioUnitario;
    $porcentajeIVA = $esResponsableIVA ? 19 : 0;
    $valorIVA = $esResponsableIVA ? ($subtotal * $porcentajeIVA / 100) : 0;
    $total = $subtotal + $valorIVA;
    
    echo "Datos de la venta:\n";
    echo "Cantidad: {$cantidad}\n";
    echo "Precio unitario: {$precioUnitario}\n";
    echo "Subtotal: {$subtotal}\n";
    echo "Impuesto ({$porcentajeIVA}%): {$valorIVA}\n";
    echo "Total: {$total}\n\n";
    
    // Crear la venta
    $venta = new Venta();
    $venta->cliente_id = $cliente->id;
    $venta->total = $total;
    $venta->fecha = now();
    $venta->estado = 'completada';
    $venta->metodo_pago = 'efectivo';
    $venta->save();
    
    // Crear el detalle de la venta
    $detalle = new DetalleVenta();
    $detalle->venta_id = $venta->id;
    $detalle->producto_id = $producto->id;
    $detalle->cantidad = $cantidad;
    $detalle->precio = $precioUnitario;
    $detalle->iva = $porcentajeIVA;
    $detalle->subtotal = $subtotal;
    $detalle->save();
    
    echo "Venta creada con ID: {$venta->id}\n";
    
    // Crear factura electrónica en Alegra
    echo "Creando factura electrónica en Alegra...\n";
    $resultado = $venta->crearFacturaElectronica();
    
    if (!$resultado['success']) {
        echo "Error al crear factura electrónica: " . ($resultado['message'] ?? 'Error desconocido') . "\n";
        DB::rollBack();
        exit(1);
    }
    
    echo "Factura electrónica creada exitosamente en Alegra.\n";
    echo "ID de factura en Alegra: " . ($resultado['data']['id'] ?? 'No disponible') . "\n";
    echo "Estado de la factura: " . ($resultado['data']['status'] ?? 'No disponible') . "\n";
    
    // Mostrar datos completos de la factura
    echo "\nDatos completos de la factura:\n";
    echo json_encode($resultado['data'], JSON_PRETTY_PRINT) . "\n";
    
    DB::commit();
    echo "\nPrueba completada exitosamente.\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error durante la prueba: " . $e->getMessage() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
}

echo "\nProceso finalizado.\n";
