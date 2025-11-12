<?php

namespace App\Http\Controllers;

use App\Models\Remision;
use App\Models\DetalleRemision;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\Cotizacion;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class RemisionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Mostrar lista de remisiones
     */
    public function index(Request $request)
    {
        $query = Remision::with(['cliente', 'vendedor'])
                         ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_remision', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_remision', '<=', $request->fecha_hasta);
        }

        $remisiones = $query->paginate(15);
        $clientes = Cliente::orderBy('nombres')->get();

        return view('remisiones.index', compact('remisiones', 'clientes'));
    }

    /**
     * Mostrar formulario de creación
     */
    public function create(Request $request)
    {
        $clientes = Cliente::orderBy('nombres')->get();
        $productos = Producto::where('stock', '>', 0)->orderBy('nombre')->get();
        $empresa = Empresa::first();
        
        // Si viene desde una venta o cotización
        $venta = null;
        $cotizacion = null;
        
        if ($request->filled('venta_id')) {
            $venta = Venta::with(['detalles.producto', 'cliente'])->find($request->venta_id);
        }
        
        if ($request->filled('cotizacion_id')) {
            $cotizacion = Cotizacion::with(['detalles.producto', 'cliente'])->find($request->cotizacion_id);
        }
        
        return view('remisiones.create', compact('clientes', 'productos', 'empresa', 'venta', 'cotizacion'));
    }

    /**
     * Guardar nueva remisión
     */
    public function store(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'fecha_remision' => 'required|date',
            'tipo' => 'required|in:venta,traslado,devolucion,muestra',
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|numeric|min:0.001',
        ]);

        try {
            DB::beginTransaction();

            // Crear remisión
            $remision = Remision::create([
                'numero_remision' => Remision::generarNumeroRemision(),
                'cliente_id' => $request->cliente_id,
                'fecha_remision' => $request->fecha_remision,
                'fecha_entrega' => $request->fecha_entrega,
                'tipo' => $request->tipo,
                'observaciones' => $request->observaciones,
                'direccion_entrega' => $request->direccion_entrega,
                'transportador' => $request->transportador,
                'vehiculo' => $request->vehiculo,
                'conductor' => $request->conductor,
                'cedula_conductor' => $request->cedula_conductor,
                'vendedor_id' => Auth::id(),
                'venta_id' => $request->venta_id,
                'cotizacion_id' => $request->cotizacion_id,
                'estado' => 'pendiente'
            ]);

            // Crear detalles
            foreach ($request->productos as $item) {
                $producto = Producto::find($item['producto_id']);
                
                // Calcular totales antes de crear el registro
                $cantidad = $item['cantidad'];
                $precioUnitario = $item['precio_unitario'] ?? 0;
                $descuentoPorcentaje = $item['descuento_porcentaje'] ?? 0;
                $impuestoPorcentaje = $item['impuesto_porcentaje'] ?? 0;
                
                $subtotal = $cantidad * $precioUnitario;
                $descuentoValor = ($subtotal * $descuentoPorcentaje) / 100;
                $baseImpuesto = $subtotal - $descuentoValor;
                $impuestoValor = ($baseImpuesto * $impuestoPorcentaje) / 100;
                $total = $baseImpuesto + $impuestoValor;
                
                $detalle = DetalleRemision::create([
                    'remision_id' => $remision->id,
                    'producto_id' => $item['producto_id'],
                    'cantidad' => $cantidad,
                    'unidad_medida' => $item['unidad_medida'] ?? 'UND',
                    'precio_unitario' => $precioUnitario,
                    'descuento_porcentaje' => $descuentoPorcentaje,
                    'descuento_valor' => $descuentoValor,
                    'subtotal' => $subtotal,
                    'impuesto_porcentaje' => $impuestoPorcentaje,
                    'impuesto_valor' => $impuestoValor,
                    'total' => $total,
                    'observaciones' => $item['observaciones'] ?? null
                ]);
            }

            // Calcular totales de la remisión
            $remision->calcularTotales();

            // Actualizar stock según el tipo
            if (in_array($request->tipo, ['venta', 'traslado', 'muestra'])) {
                $remision->actualizarStock('restar');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Remisión creada exitosamente',
                'remision_id' => $remision->id,
                'numero_remision' => $remision->numero_remision
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error creando remisión: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la remisión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar remisión específica
     */
    public function show($id)
    {
        try {
            $remision = Remision::with(['cliente', 'vendedor', 'detalles.producto', 'venta', 'cotizacion'])->findOrFail($id);
            
            return view('remisiones.show', compact('remision'));
        } catch (\Exception $e) {
            Log::error('Error mostrando remisión: ' . $e->getMessage());
            return redirect()->route('remisiones.index')
                           ->with('error', 'Remisión no encontrada');
        }
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit($id)
    {
        try {
            $remision = Remision::with(['detalles.producto'])->findOrFail($id);
            
            if (!in_array($remision->estado, ['pendiente', 'en_transito'])) {
                return redirect()->route('remisiones.show', $remision->id)
                               ->with('error', 'Solo se pueden editar remisiones pendientes o en tránsito');
            }

            $clientes = Cliente::orderBy('nombres')->get();
            $productos = Producto::orderBy('nombre')->get();
            
            return view('remisiones.edit', compact('remision', 'clientes', 'productos'));
        } catch (\Exception $e) {
            Log::error('Error editando remisión: ' . $e->getMessage());
            return redirect()->route('remisiones.index')
                           ->with('error', 'Remisión no encontrada');
        }
    }

    /**
     * Actualizar remisión
     */
    public function update(Request $request, $id)
    {
        try {
            $remision = Remision::findOrFail($id);
            
            if (!in_array($remision->estado, ['pendiente', 'en_transito'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden editar remisiones pendientes o en tránsito'
                ], 400);
            }

            $request->validate([
                'cliente_id' => 'required|exists:clientes,id',
                'fecha_remision' => 'required|date',
                'tipo' => 'required|in:venta,traslado,devolucion,muestra',
                'productos' => 'required|array|min:1',
            ]);
            DB::beginTransaction();

            // Restaurar stock anterior si es necesario
            if (in_array($remision->tipo, ['venta', 'traslado', 'muestra'])) {
                $remision->actualizarStock('sumar');
            }

            // Actualizar remisión
            $remision->update([
                'cliente_id' => $request->cliente_id,
                'fecha_remision' => $request->fecha_remision,
                'fecha_entrega' => $request->fecha_entrega,
                'tipo' => $request->tipo,
                'observaciones' => $request->observaciones,
                'direccion_entrega' => $request->direccion_entrega,
                'transportador' => $request->transportador,
                'vehiculo' => $request->vehiculo,
                'conductor' => $request->conductor,
                'cedula_conductor' => $request->cedula_conductor
            ]);

            // Eliminar detalles existentes
            $remision->detalles()->delete();

            // Crear nuevos detalles
            foreach ($request->productos as $item) {
                // Calcular totales antes de crear el registro
                $cantidad = $item['cantidad'];
                $precioUnitario = $item['precio_unitario'] ?? 0;
                $descuentoPorcentaje = $item['descuento_porcentaje'] ?? 0;
                $impuestoPorcentaje = $item['impuesto_porcentaje'] ?? 0;
                
                $subtotal = $cantidad * $precioUnitario;
                $descuentoValor = ($subtotal * $descuentoPorcentaje) / 100;
                $baseImpuesto = $subtotal - $descuentoValor;
                $impuestoValor = ($baseImpuesto * $impuestoPorcentaje) / 100;
                $total = $baseImpuesto + $impuestoValor;
                
                $detalle = DetalleRemision::create([
                    'remision_id' => $remision->id,
                    'producto_id' => $item['producto_id'],
                    'cantidad' => $cantidad,
                    'unidad_medida' => $item['unidad_medida'] ?? 'UND',
                    'precio_unitario' => $precioUnitario,
                    'descuento_porcentaje' => $descuentoPorcentaje,
                    'descuento_valor' => $descuentoValor,
                    'subtotal' => $subtotal,
                    'impuesto_porcentaje' => $impuestoPorcentaje,
                    'impuesto_valor' => $impuestoValor,
                    'total' => $total,
                    'observaciones' => $item['observaciones'] ?? null
                ]);
            }

            // Recalcular totales
            $remision->calcularTotales();

            // Actualizar stock con los nuevos datos
            if (in_array($request->tipo, ['venta', 'traslado', 'muestra'])) {
                $remision->actualizarStock('restar');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Remisión actualizada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error actualizando remisión: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la remisión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar estado de remisión
     */
    public function cambiarEstado(Request $request, Remision $remision)
    {
        $request->validate([
            'estado' => 'required|in:pendiente,en_transito,entregada,devuelta,cancelada'
        ]);

        try {
            DB::beginTransaction();

            $estadoAnterior = $remision->estado;
            $nuevoEstado = $request->estado;

            // Lógica especial para cancelación
            if ($nuevoEstado === 'cancelada' && $estadoAnterior !== 'cancelada') {
                // Restaurar stock si se cancela
                if (in_array($remision->tipo, ['venta', 'traslado', 'muestra'])) {
                    $remision->actualizarStock('sumar');
                }
            }

            $remision->update([
                'estado' => $nuevoEstado
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error cambiando estado de remisión: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado'
            ], 500);
        }
    }

    /**
     * Registrar entrega parcial o total
     */
    public function registrarEntrega(Request $request, Remision $remision)
    {
        $request->validate([
            'entregas' => 'required|array',
            'entregas.*.detalle_id' => 'required|exists:detalle_remisiones,id',
            'entregas.*.cantidad_entregada' => 'required|numeric|min:0'
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->entregas as $entrega) {
                $detalle = DetalleRemision::find($entrega['detalle_id']);
                $detalle->registrarEntrega($entrega['cantidad_entregada']);
            }

            // Verificar si está completamente entregada
            if ($remision->estaCompletamenteEntregada()) {
                $remision->update(['estado' => 'entregada']);
            } else {
                $remision->update(['estado' => 'en_transito']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Entrega registrada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error registrando entrega: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la entrega'
            ], 500);
        }
    }

    /**
     * Eliminar remisión
     */
    public function destroy(Remision $remision)
    {
        if ($remision->estado === 'entregada') {
            return response()->json([
                'success' => false,
                'message' => 'No se pueden eliminar remisiones entregadas'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Restaurar stock si es necesario
            if (in_array($remision->tipo, ['venta', 'traslado', 'muestra']) && $remision->estado !== 'cancelada') {
                $remision->actualizarStock('sumar');
            }

            $remision->delete();
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Remisión eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error eliminando remisión: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la remisión'
            ], 500);
        }
    }

    /**
     * Generar PDF de remisión
     */
    public function generarPdf($id)
    {
        try {
            $remision = Remision::with(['cliente', 'vendedor', 'detalles.producto', 'cotizacion'])->findOrFail($id);
            $empresa = Empresa::first();
            
            // Generar PDF con DomPDF
            $pdf = Pdf::loadView('remisiones.pdf', compact('remision', 'empresa'));
            
            // Configurar el PDF
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'dpi' => 150,
                'defaultFont' => 'Arial',
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true
            ]);
            
            // Nombre del archivo
            $filename = 'Remision_' . $remision->numero_remision . '.pdf';
            
            // Retornar el PDF para descarga
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            Log::error('Error generando PDF de remisión: ' . $e->getMessage());
            return redirect()->route('remisiones.show', $id)
                           ->with('error', 'Error al generar el PDF');
        }
    }

    /**
     * Crear remisión desde venta
     */
    public function crearDesdeVenta(Venta $venta)
    {
        return redirect()->route('remisiones.create', ['venta_id' => $venta->id]);
    }

    /**
     * Crear remisión desde cotización
     */
    public function crearDesdeCotizacion(Cotizacion $cotizacion)
    {
        return redirect()->route('remisiones.create', ['cotizacion_id' => $cotizacion->id]);
    }
}
