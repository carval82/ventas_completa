<?php

namespace App\Http\Controllers;

use App\Models\VentaCerdo;
use App\Models\Cerdo;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class VentaCerdoController extends Controller
{
    /**
     * Mostrar listado de ventas de cerdos
     */
    public function index(Request $request)
    {
        $query = VentaCerdo::with(['cerdo', 'cliente']);
        
        // Filtros
        if ($request->has('cliente_id') && $request->cliente_id) {
            $query->where('cliente_id', $request->cliente_id);
        }
        
        if ($request->has('tipo_venta') && $request->tipo_venta) {
            $query->where('tipo_venta', $request->tipo_venta);
        }
        
        if ($request->has('fecha_desde') && $request->fecha_desde) {
            $query->whereDate('fecha_venta', '>=', $request->fecha_desde);
        }
        
        if ($request->has('fecha_hasta') && $request->fecha_hasta) {
            $query->whereDate('fecha_venta', '<=', $request->fecha_hasta);
        }

        $ventas = $query->orderBy('fecha_venta', 'desc')->paginate(15);
        $clientes = Cliente::orderBy('nombre')->get();
        
        return view('cerdos.ventas.index', compact('ventas', 'clientes'));
    }

    /**
     * Mostrar detalle de venta
     */
    public function show(VentaCerdo $venta)
    {
        return view('cerdos.ventas.show', compact('venta'));
    }

    /**
     * Mostrar formulario para editar venta
     */
    public function edit(VentaCerdo $venta)
    {
        $clientes = Cliente::orderBy('nombre')->get();
        return view('cerdos.ventas.edit', compact('venta', 'clientes'));
    }

    /**
     * Actualizar venta
     */
    public function update(Request $request, VentaCerdo $venta)
    {
        $validator = Validator::make($request->all(), [
            'cliente_id' => 'nullable|exists:clientes,id',
            'fecha_venta' => 'required|date',
            'tipo_venta' => 'required|in:pie,kilo',
            'peso_venta' => 'required|numeric|min:0',
            'precio_unitario' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Calcular precio total
        $precio_total = $request->tipo_venta == 'kilo' ? 
            $request->peso_venta * $request->precio_unitario : 
            $request->precio_unitario;

        $venta->update([
            'cliente_id' => $request->cliente_id,
            'fecha_venta' => $request->fecha_venta,
            'tipo_venta' => $request->tipo_venta,
            'peso_venta' => $request->peso_venta,
            'precio_unitario' => $request->precio_unitario,
            'precio_total' => $precio_total,
            'observaciones' => $request->observaciones,
        ]);

        return redirect()->route('ventas-cerdos.index')
            ->with('success', 'Venta actualizada correctamente');
    }

    /**
     * Eliminar venta
     */
    public function destroy(VentaCerdo $venta)
    {
        // Obtener el cerdo asociado
        $cerdo = $venta->cerdo;
        
        // Iniciar transacción
        DB::beginTransaction();
        
        try {
            // Eliminar la venta
            $venta->delete();
            
            // Actualizar el estado del cerdo
            $cerdo->update(['estado' => 'engorde']);
            
            DB::commit();
            
            return redirect()->route('ventas-cerdos.index')
                ->with('success', 'Venta eliminada correctamente y cerdo restaurado a estado "engorde"');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Error al eliminar la venta: ' . $e->getMessage());
        }
    }

    /**
     * Generar reporte de ventas
     */
    public function reporte(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde',
            'tipo_reporte' => 'required|in:diario,mensual,tipo_venta,cliente',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $fechaDesde = $request->fecha_desde;
        $fechaHasta = $request->fecha_hasta;
        $tipoReporte = $request->tipo_reporte;

        // Consulta base
        $query = VentaCerdo::with(['cerdo', 'cliente'])
            ->whereDate('fecha_venta', '>=', $fechaDesde)
            ->whereDate('fecha_venta', '<=', $fechaHasta);

        // Agrupar según el tipo de reporte
        switch ($tipoReporte) {
            case 'diario':
                $ventas = $query->select(
                    DB::raw('DATE(fecha_venta) as fecha'),
                    DB::raw('COUNT(*) as cantidad'),
                    DB::raw('SUM(precio_total) as total')
                )
                ->groupBy(DB::raw('DATE(fecha_venta)'))
                ->orderBy('fecha')
                ->get();
                break;
                
            case 'mensual':
                $ventas = $query->select(
                    DB::raw('YEAR(fecha_venta) as anio'),
                    DB::raw('MONTH(fecha_venta) as mes'),
                    DB::raw('COUNT(*) as cantidad'),
                    DB::raw('SUM(precio_total) as total')
                )
                ->groupBy(DB::raw('YEAR(fecha_venta)'), DB::raw('MONTH(fecha_venta)'))
                ->orderBy('anio')
                ->orderBy('mes')
                ->get();
                break;
                
            case 'tipo_venta':
                $ventas = $query->select(
                    'tipo_venta',
                    DB::raw('COUNT(*) as cantidad'),
                    DB::raw('SUM(precio_total) as total'),
                    DB::raw('AVG(precio_unitario) as precio_promedio')
                )
                ->groupBy('tipo_venta')
                ->get();
                break;
                
            case 'cliente':
                $ventas = $query->select(
                    'cliente_id',
                    DB::raw('COUNT(*) as cantidad'),
                    DB::raw('SUM(precio_total) as total')
                )
                ->groupBy('cliente_id')
                ->get();
                
                // Agregar nombre del cliente
                $ventas->each(function ($venta) {
                    if ($venta->cliente_id) {
                        $cliente = Cliente::find($venta->cliente_id);
                        $venta->nombre_cliente = $cliente ? $cliente->nombre : 'Cliente eliminado';
                    } else {
                        $venta->nombre_cliente = 'Sin cliente';
                    }
                });
                break;
        }

        return view('cerdos.ventas.reporte', compact('ventas', 'tipoReporte', 'fechaDesde', 'fechaHasta'));
    }

    /**
     * Mostrar formulario para generar reporte
     */
    public function formReporte()
    {
        return view('cerdos.ventas.form-reporte');
    }
}
