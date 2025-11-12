<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\DetalleCotizacion;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class CotizacionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Mostrar lista de cotizaciones
     */
    public function index(Request $request)
    {
        $query = Cotizacion::with(['cliente', 'vendedor'])
                          ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_cotizacion', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_cotizacion', '<=', $request->fecha_hasta);
        }

        $cotizaciones = $query->paginate(15);
        $clientes = Cliente::orderBy('nombres')->get();

        return view('cotizaciones.index', compact('cotizaciones', 'clientes'));
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        $clientes = Cliente::orderBy('nombres')->get();
        $productos = Producto::where('stock', '>', 0)->orderBy('nombre')->get();
        $empresa = Empresa::first();
        
        return view('cotizaciones.create', compact('clientes', 'productos', 'empresa'));
    }

    /**
     * Guardar nueva cotización
     */
    public function store(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'fecha_cotizacion' => 'required|date',
            'dias_validez' => 'required|integer|min:1',
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|numeric|min:0.001',
            'productos.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Crear cotización
            $cotizacion = Cotizacion::create([
                'numero_cotizacion' => Cotizacion::generarNumeroCotizacion(),
                'cliente_id' => $request->cliente_id,
                'fecha_cotizacion' => $request->fecha_cotizacion,
                'fecha_vencimiento' => now()->addDays((int)$request->dias_validez)->toDateString(),
                'dias_validez' => $request->dias_validez,
                'observaciones' => $request->observaciones,
                'condiciones_comerciales' => $request->condiciones_comerciales,
                'forma_pago' => $request->forma_pago,
                'vendedor_id' => Auth::id(),
                'estado' => 'pendiente'
            ]);

            // Crear detalles
            foreach ($request->productos as $item) {
                $producto = Producto::find($item['producto_id']);
                
                // Calcular totales antes de crear el registro
                $cantidad = $item['cantidad'];
                $precioUnitario = $item['precio_unitario'];
                $descuentoPorcentaje = $item['descuento_porcentaje'] ?? 0;
                $impuestoPorcentaje = $item['impuesto_porcentaje'] ?? 0;
                
                $subtotal = $cantidad * $precioUnitario;
                $descuentoValor = ($subtotal * $descuentoPorcentaje) / 100;
                $baseImpuesto = $subtotal - $descuentoValor;
                $impuestoValor = ($baseImpuesto * $impuestoPorcentaje) / 100;
                $total = $baseImpuesto + $impuestoValor;
                
                $detalle = DetalleCotizacion::create([
                    'cotizacion_id' => $cotizacion->id,
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

            // Calcular totales de la cotización
            $cotizacion->calcularTotales();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cotización creada exitosamente',
                'cotizacion_id' => $cotizacion->id,
                'numero_cotizacion' => $cotizacion->numero_cotizacion
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error creando cotización: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar cotización específica
     */
    public function show($id)
    {
        try {
            $cotizacion = Cotizacion::with(['cliente', 'vendedor', 'detalles.producto'])->findOrFail($id);
            
            return view('cotizaciones.show', compact('cotizacion'));
        } catch (\Exception $e) {
            Log::error('Error mostrando cotización: ' . $e->getMessage());
            return redirect()->route('cotizaciones.index')
                           ->with('error', 'Cotización no encontrada');
        }
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit($id)
    {
        try {
            $cotizacion = Cotizacion::with(['detalles.producto'])->findOrFail($id);
            
            if ($cotizacion->estado !== 'pendiente') {
                return redirect()->route('cotizaciones.show', $cotizacion->id)
                               ->with('error', 'Solo se pueden editar cotizaciones pendientes');
            }

            $clientes = Cliente::orderBy('nombres')->get();
            $productos = Producto::orderBy('nombre')->get();
            
            return view('cotizaciones.edit', compact('cotizacion', 'clientes', 'productos'));
        } catch (\Exception $e) {
            Log::error('Error editando cotización: ' . $e->getMessage());
            return redirect()->route('cotizaciones.index')
                           ->with('error', 'Cotización no encontrada');
        }
    }

    /**
     * Actualizar cotización
     */
    public function update(Request $request, $id)
    {
        try {
            $cotizacion = Cotizacion::findOrFail($id);
            
            if ($cotizacion->estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden editar cotizaciones pendientes'
                ], 400);
            }

            $request->validate([
                'cliente_id' => 'required|exists:clientes,id',
                'fecha_cotizacion' => 'required|date',
                'dias_validez' => 'required|integer|min:1',
                'productos' => 'required|array|min:1',
            ]);
            DB::beginTransaction();

            // Actualizar cotización
            $cotizacion->update([
                'cliente_id' => $request->cliente_id,
                'fecha_cotizacion' => $request->fecha_cotizacion,
                'fecha_vencimiento' => now()->addDays((int)$request->dias_validez)->toDateString(),
                'dias_validez' => $request->dias_validez,
                'observaciones' => $request->observaciones,
                'condiciones_comerciales' => $request->condiciones_comerciales,
                'forma_pago' => $request->forma_pago
            ]);

            // Eliminar detalles existentes
            $cotizacion->detalles()->delete();

            // Crear nuevos detalles
            foreach ($request->productos as $item) {
                // Calcular totales antes de crear el registro
                $cantidad = $item['cantidad'];
                $precioUnitario = $item['precio_unitario'];
                $descuentoPorcentaje = $item['descuento_porcentaje'] ?? 0;
                $impuestoPorcentaje = $item['impuesto_porcentaje'] ?? 0;
                
                $subtotal = $cantidad * $precioUnitario;
                $descuentoValor = ($subtotal * $descuentoPorcentaje) / 100;
                $baseImpuesto = $subtotal - $descuentoValor;
                $impuestoValor = ($baseImpuesto * $impuestoPorcentaje) / 100;
                $total = $baseImpuesto + $impuestoValor;
                
                $detalle = DetalleCotizacion::create([
                    'cotizacion_id' => $cotizacion->id,
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
            $cotizacion->calcularTotales();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cotización actualizada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error actualizando cotización: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar estado de cotización
     */
    public function cambiarEstado(Request $request, Cotizacion $cotizacion)
    {
        $request->validate([
            'estado' => 'required|in:pendiente,aprobada,rechazada,vencida'
        ]);

        $cotizacion->update([
            'estado' => $request->estado
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado exitosamente'
        ]);
    }

    /**
     * Convertir cotización a venta
     */
    public function convertirAVenta(Cotizacion $cotizacion)
    {
        if ($cotizacion->estado !== 'aprobada') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden convertir cotizaciones aprobadas'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Crear venta
            $venta = Venta::create([
                'numero_factura' => Venta::generarNumeroFactura(),
                'cliente_id' => $cotizacion->cliente_id,
                'fecha' => now()->toDateString(),
                'subtotal' => $cotizacion->subtotal,
                'descuento' => $cotizacion->descuento,
                'impuestos' => $cotizacion->impuestos,
                'total' => $cotizacion->total,
                'observaciones' => $cotizacion->observaciones,
                'forma_pago' => $cotizacion->forma_pago ?? 'efectivo',
                'vendedor_id' => $cotizacion->vendedor_id,
                'estado' => 'completada'
            ]);

            // Crear detalles de venta
            foreach ($cotizacion->detalles as $detalleCot) {
                DetalleVenta::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $detalleCot->producto_id,
                    'cantidad' => $detalleCot->cantidad,
                    'precio_unitario' => $detalleCot->precio_unitario,
                    'descuento' => $detalleCot->descuento_valor,
                    'subtotal' => $detalleCot->subtotal,
                    'impuesto' => $detalleCot->impuesto_valor,
                    'total' => $detalleCot->total
                ]);

                // Actualizar stock
                $producto = Producto::find($detalleCot->producto_id);
                $producto->decrement('stock', $detalleCot->cantidad);
            }

            // Actualizar cotización
            $cotizacion->update([
                'estado' => 'convertida',
                'venta_id' => $venta->id
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cotización convertida a venta exitosamente',
                'venta_id' => $venta->id,
                'numero_factura' => $venta->numero_factura
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error convirtiendo cotización a venta: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al convertir la cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar cotización
     */
    public function destroy(Cotizacion $cotizacion)
    {
        if ($cotizacion->estado === 'convertida') {
            return response()->json([
                'success' => false,
                'message' => 'No se pueden eliminar cotizaciones convertidas a venta'
            ], 400);
        }

        try {
            $cotizacion->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Cotización eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error eliminando cotización: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la cotización'
            ], 500);
        }
    }

    /**
     * Generar PDF de cotización
     */
    public function generarPdf($id)
    {
        try {
            $cotizacion = Cotizacion::with(['cliente', 'vendedor', 'detalles.producto'])->findOrFail($id);
            $empresa = Empresa::first();
            
            // Generar PDF con DomPDF
            $pdf = Pdf::loadView('cotizaciones.pdf', compact('cotizacion', 'empresa'));
            
            // Configurar el PDF
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'dpi' => 150,
                'defaultFont' => 'Arial',
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true
            ]);
            
            // Nombre del archivo
            $filename = 'Cotizacion_' . $cotizacion->numero_cotizacion . '.pdf';
            
            // Retornar el PDF para descarga
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            Log::error('Error generando PDF de cotización: ' . $e->getMessage());
            return redirect()->route('cotizaciones.show', $id)
                           ->with('error', 'Error al generar el PDF');
        }
    }
}
