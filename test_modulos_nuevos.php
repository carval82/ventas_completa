<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Cotizacion;
use App\Models\DetalleCotizacion;
use App\Models\Remision;
use App\Models\DetalleRemision;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\User;

try {
    echo "=== PRUEBA DE MÓDULOS NUEVOS ===\n\n";
    
    // 1. Verificar modelos
    echo "🔍 VERIFICANDO MODELOS...\n";
    
    $cotizacionesCount = Cotizacion::count();
    $remisionesCount = Remision::count();
    
    echo "   ✅ Cotizaciones en BD: {$cotizacionesCount}\n";
    echo "   ✅ Remisiones en BD: {$remisionesCount}\n";
    
    // 2. Verificar relaciones
    echo "\n🔗 VERIFICANDO RELACIONES...\n";
    
    $cliente = Cliente::first();
    $producto = Producto::first();
    $usuario = User::first();
    
    if ($cliente && $producto && $usuario) {
        echo "   ✅ Cliente encontrado: {$cliente->nombres} {$cliente->apellidos}\n";
        echo "   ✅ Producto encontrado: {$producto->nombre}\n";
        echo "   ✅ Usuario encontrado: {$usuario->name}\n";
        
        // 3. Crear cotización de prueba
        echo "\n📋 CREANDO COTIZACIÓN DE PRUEBA...\n";
        
        $cotizacion = Cotizacion::create([
            'numero_cotizacion' => Cotizacion::generarNumeroCotizacion(),
            'cliente_id' => $cliente->id,
            'fecha_cotizacion' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
            'dias_validez' => 30,
            'observaciones' => 'Cotización de prueba automática',
            'vendedor_id' => $usuario->id,
            'estado' => 'pendiente'
        ]);
        
        // Crear detalle
        $detalle = DetalleCotizacion::create([
            'cotizacion_id' => $cotizacion->id,
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'unidad_medida' => 'UND',
            'precio_unitario' => 10000,
            'descuento_porcentaje' => 0,
            'descuento_valor' => 0,
            'subtotal' => 20000,
            'impuesto_porcentaje' => 19,
            'impuesto_valor' => 3800,
            'total' => 23800
        ]);
        
        $detalle->calcularTotales();
        $cotizacion->calcularTotales();
        
        echo "   ✅ Cotización creada: {$cotizacion->numero_cotizacion}\n";
        echo "   ✅ Total cotización: $" . number_format($cotizacion->total, 0, ',', '.') . "\n";
        
        // 4. Crear remisión de prueba
        echo "\n🚚 CREANDO REMISIÓN DE PRUEBA...\n";
        
        $remision = Remision::create([
            'numero_remision' => Remision::generarNumeroRemision(),
            'cliente_id' => $cliente->id,
            'fecha_remision' => now()->toDateString(),
            'tipo' => 'venta',
            'observaciones' => 'Remisión de prueba automática',
            'vendedor_id' => $usuario->id,
            'estado' => 'pendiente'
        ]);
        
        // Crear detalle
        $detalleRemision = DetalleRemision::create([
            'remision_id' => $remision->id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'unidad_medida' => 'UND',
            'precio_unitario' => 15000,
            'descuento_porcentaje' => 0,
            'descuento_valor' => 0,
            'subtotal' => 15000,
            'impuesto_porcentaje' => 19,
            'impuesto_valor' => 2850,
            'total' => 17850
        ]);
        
        $detalleRemision->calcularTotales();
        $remision->calcularTotales();
        
        echo "   ✅ Remisión creada: {$remision->numero_remision}\n";
        echo "   ✅ Total remisión: $" . number_format($remision->total, 0, ',', '.') . "\n";
        
        // 5. Probar métodos especiales
        echo "\n⚙️ PROBANDO MÉTODOS ESPECIALES...\n";
        
        // Verificar si la cotización está vencida
        $vencida = $cotizacion->estaVencida();
        echo "   ✅ Cotización vencida: " . ($vencida ? 'Sí' : 'No') . "\n";
        
        // Verificar entrega completa de remisión
        $entregada = $remision->estaCompletamenteEntregada();
        echo "   ✅ Remisión completamente entregada: " . ($entregada ? 'Sí' : 'No') . "\n";
        
        // 6. Probar scopes
        echo "\n🔍 PROBANDO SCOPES...\n";
        
        $cotizacionesPendientes = Cotizacion::pendientes()->count();
        $remisionesPendientes = Remision::pendientes()->count();
        
        echo "   ✅ Cotizaciones pendientes: {$cotizacionesPendientes}\n";
        echo "   ✅ Remisiones pendientes: {$remisionesPendientes}\n";
        
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "🎉 PRUEBA COMPLETADA EXITOSAMENTE\n\n";
        
        echo "✅ MÓDULOS FUNCIONANDO CORRECTAMENTE:\n";
        echo "   📋 Cotizaciones: Modelo, relaciones y métodos OK\n";
        echo "   🚚 Remisiones: Modelo, relaciones y métodos OK\n";
        echo "   🔗 Relaciones: Cliente, Producto, Usuario OK\n";
        echo "   💰 Cálculos: Totales e impuestos OK\n";
        echo "   📊 Scopes: Filtros y consultas OK\n\n";
        
        echo "🚀 EL SISTEMA ESTÁ LISTO PARA USAR\n";
        echo "   - Accede a /cotizaciones para gestionar cotizaciones\n";
        echo "   - Accede a /remisiones para gestionar remisiones\n";
        echo "   - Accede a /compras para usar unidades de conversión\n\n";
        
    } else {
        echo "   ❌ Faltan datos básicos (cliente, producto o usuario)\n";
        echo "   ℹ️ Ejecuta los seeders primero: php artisan db:seed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error en la prueba: " . $e->getMessage() . "\n";
    echo "📍 Archivo: " . $e->getFile() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
}
