<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Cargar el entorno de Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Empresa;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Services\AlegraService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

// Configurar logging para ver resultados detallados
Log::info('Iniciando prueba de envío de IVA a Alegra');

// Obtener la empresa para verificar el régimen tributario y porcentaje de IVA
$empresa = Empresa::first();

if (!$empresa) {
    echo "Error: No hay empresa configurada en el sistema.\n";
    exit(1);
}

echo "Empresa: {$empresa->nombre}\n";
echo "Régimen tributario: {$empresa->regimen_tributario}\n";
echo "Porcentaje IVA: {$empresa->porcentaje_iva}%\n";

// Verificar si es responsable de IVA
$esResponsableIVA = $empresa->regimen_tributario === 'responsable_iva';
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

echo "Conexión exitosa con Alegra.\n";

// Obtener un cliente para la prueba o crear uno si no existe
$cliente = Cliente::first();

if (!$cliente) {
    echo "No hay clientes en el sistema. Creando uno de prueba...\n";
    
    $cliente = Cliente::create([
        'nombres' => 'Cliente',
        'apellidos' => 'Prueba',
        'cedula' => '1234567890',
        'telefono' => '3001234567',
        'email' => 'cliente.prueba@example.com',
        'direccion' => 'Calle de prueba',
        'estado' => 1
    ]);
    
    echo "Cliente creado con ID: {$cliente->id}\n";
}

// Asegurar que el cliente tenga ID de Alegra
if (!$cliente->id_alegra) {
    echo "Sincronizando cliente con Alegra...\n";
    $cliente->syncToAlegra();
    $cliente = Cliente::find($cliente->id); // Recargar para obtener el id_alegra actualizado
}

echo "Cliente: {$cliente->nombres} {$cliente->apellidos} (ID Alegra: {$cliente->id_alegra})\n";

// Obtener un producto para la prueba o crear uno si no existe
$producto = Producto::first();

if (!$producto) {
    echo "No hay productos en el sistema. Creando uno de prueba...\n";
    
    $producto = Producto::create([
        'nombre' => 'Producto de Prueba',
        'descripcion' => 'Producto para prueba de IVA',
        'precio' => 50000,
        'stock' => 100,
        'estado' => 1
    ]);
    
    echo "Producto creado con ID: {$producto->id}\n";
}

// Asegurar que el producto tenga ID de Alegra
if (!$producto->id_alegra) {
    echo "Sincronizando producto con Alegra...\n";
    $producto->syncToAlegra();
    $producto = Producto::find($producto->id); // Recargar para obtener el id_alegra actualizado
}

echo "Producto: {$producto->nombre} (ID Alegra: {$producto->id_alegra})\n";

// Crear una venta de prueba
echo "Creando venta de prueba...\n";

DB::beginTransaction();
try {
    $venta = new Venta();
    $venta->cliente_id = $cliente->id;
    $venta->fecha_venta = now();
    $venta->subtotal = 100000;
    $venta->impuesto = $esResponsableIVA ? 19000 : 0;
    $venta->total = 100000 + ($esResponsableIVA ? 19000 : 0);
    $venta->metodo_pago = 'efectivo';
    $venta->estado = 'completada';
    $venta->save();
    
    // Crear detalle de venta
    $detalle = new DetalleVenta();
    $detalle->venta_id = $venta->id;
    $detalle->producto_id = $producto->id;
    $detalle->cantidad = 2;
    $detalle->precio_unitario = 50000;
    $detalle->subtotal = 100000;
    $detalle->save();
    
    DB::commit();
    
    echo "Venta creada con ID: {$venta->id}\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error al crear la venta: " . $e->getMessage() . "\n";
    exit(1);
}

// Recargar la venta con sus relaciones
$venta = Venta::with(['cliente', 'detalles.producto'])->find($venta->id);

// Preparar los datos para Alegra
echo "Preparando datos para Alegra...\n";
$datos = $venta->prepararFacturaAlegra();

// Mostrar los datos que se enviarán a Alegra
echo "\nDatos que se enviarán a Alegra:\n";
echo json_encode($datos, JSON_PRETTY_PRINT) . "\n";

// Preguntar si desea continuar con la creación de la factura
echo "\n¿Desea continuar con la creación de la factura en Alegra? (s/n): ";
$respuesta = trim(fgets(STDIN));

if (strtolower($respuesta) !== 's') {
    echo "Operación cancelada.\n";
    exit(0);
}

// Crear la factura en Alegra
echo "Creando factura en Alegra...\n";
$resultado = $venta->crearFacturaElectronica();

// Mostrar el resultado
echo "\nResultado de la creación de la factura:\n";
echo json_encode($resultado, JSON_PRETTY_PRINT) . "\n";

if ($resultado['success']) {
    echo "\nFactura creada correctamente en Alegra con ID: " . $resultado['data']['id'] . "\n";
    echo "Estado: " . ($resultado['data']['status'] ?? 'Desconocido') . "\n";
    
    // Verificar si se incluyó el IVA correctamente
    if (isset($resultado['data']['items'][0]['tax'])) {
        echo "IVA incluido correctamente en la factura.\n";
        echo "Valor del IVA: " . $resultado['data']['items'][0]['tax']['value'] . "\n";
    } else {
        echo "ADVERTENCIA: No se pudo verificar si el IVA se incluyó correctamente.\n";
    }
} else {
    echo "\nError al crear la factura en Alegra: " . ($resultado['message'] ?? 'Error desconocido') . "\n";
}

echo "\nPrueba finalizada.\n";
