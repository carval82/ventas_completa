<?php

namespace App\Http\Controllers;

use App\Models\MovimientoInterno;
use App\Models\Producto;
use App\Models\Ubicacion;
use App\Models\StockUbicacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;  // Agregamos esta línea

class MovimientoInternoController extends Controller
{
    public function index(Request $request)
    {
        $movimientos = MovimientoInterno::with(['producto', 'ubicacionOrigen', 'ubicacionDestino', 'usuario'])
            ->filtrarPorFecha($request->fecha_inicio, $request->fecha_fin)
            ->filtrarPorTipo($request->tipo)
            ->latest()
            ->paginate(10);

        return view('movimientos.index', compact('movimientos'));
    }

    public function create()
    {
        $productos = Producto::where('estado', true)->get();
        $ubicaciones = Ubicacion::where('estado', true)->get();
        $tipos_movimiento = [
            MovimientoInterno::TIPO_ENTRADA => 'Entrada',
            MovimientoInterno::TIPO_SALIDA => 'Salida',
            MovimientoInterno::TIPO_TRASLADO => 'Traslado'
        ];
        $motivos = [
            MovimientoInterno::MOTIVO_AJUSTE => 'Ajuste de Inventario',
            MovimientoInterno::MOTIVO_AVERIA => 'Avería/Daño',
            MovimientoInterno::MOTIVO_TRASLADO => 'Traslado entre Ubicaciones'
        ];

        return view('movimientos.create', compact('productos', 'ubicaciones', 'tipos_movimiento', 'motivos'));
    }

    public function store(Request $request)
{
    Log::info('Iniciando proceso de movimiento interno', [
        'request_data' => $request->all()
    ]);

    try {
        DB::beginTransaction();
        Log::info('Iniciando transacción DB');

        // Validación
        Log::info('Validando request');
        $validated = $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'tipo_movimiento' => 'required|in:entrada,salida,traslado',
            'cantidad' => 'required|numeric|min:1',
            'motivo' => 'required',
            'ubicacion_origen_id' => 'required_if:tipo_movimiento,salida,traslado|exists:ubicaciones,id',
            'ubicacion_destino_id' => 'required_if:tipo_movimiento,entrada,traslado|exists:ubicaciones,id',
            'observaciones' => 'nullable|string'
        ]);

        Log::info('Request validado correctamente', ['validated_data' => $validated]);

        // Verificar stock si es necesario
        if (in_array($request->tipo_movimiento, ['salida', 'traslado'])) {
            Log::info('Verificando stock disponible', [
                'producto_id' => $request->producto_id,
                'ubicacion_id' => $request->ubicacion_origen_id
            ]);

            $stockDisponible = StockUbicacion::where([
                'producto_id' => $request->producto_id,
                'ubicacion_id' => $request->ubicacion_origen_id
            ])->value('stock') ?? 0;

            Log::info('Stock disponible encontrado', ['stock' => $stockDisponible]);

            if ($stockDisponible < $request->cantidad) {
                throw new \Exception("Stock insuficiente. Disponible: {$stockDisponible}, Solicitado: {$request->cantidad}");
            }
        }

        // Preparar datos para el movimiento
        Log::info('Preparando datos para el movimiento');
        $data = $request->all();
        $data['user_id'] = Auth::id();

        // Realizar el movimiento
        Log::info('Llamando a MovimientoInterno::realizarMovimiento');
        $movimiento = MovimientoInterno::realizarMovimiento($data);

        Log::info('Movimiento creado exitosamente', ['movimiento_id' => $movimiento->id]);

        // Disparar el job de regularización
        RegularizarProductosJob::dispatch($movimiento->producto_id)
            ->delay(now()->addSeconds(5));

        DB::commit();
        Log::info('Transacción completada exitosamente');

        return redirect()->route('movimientos.index')
            ->with('success', 'Movimiento registrado exitosamente. La regularización se procesará en breve.');

    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollback();
        Log::error('Error de validación', [
            'errors' => $e->errors(),
            'message' => $e->getMessage()
        ]);
        return back()->withErrors($e->errors())->withInput();

    } catch (\Exception $e) {
        DB::rollback();
        Log::error('Error en el proceso de movimiento', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request_data' => $request->all()
        ]);
        return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
    }
}

    public function show(MovimientoInterno $movimiento)
    {
        $movimiento->load(['producto', 'ubicacionOrigen', 'ubicacionDestino', 'usuario']);
        return view('movimientos.show', compact('movimiento'));
    }

    public function reporteStock()
    {
        $productos = Producto::with('ubicaciones')
            ->where('estado', true)
            ->get();
        
        $ubicaciones = Ubicacion::where('estado', true)->get();
    
        return view('movimientos.reporte-stock', compact('productos', 'ubicaciones'));
    }

    public function stockBajo()
{
    $productosStockBajo = StockUbicacion::productosStockBajo();
    $ubicaciones = Ubicacion::where('estado', true)->get();
    
    return view('movimientos.stock-bajo', compact('productosStockBajo', 'ubicaciones'));
}

    public function getStockUbicacion(Request $request)
    {
        Log::info('Consultando stock por ubicación', [
            'producto_id' => $request->producto_id,
            'ubicacion_id' => $request->ubicacion_id
        ]);

        try {
            $stock = StockUbicacion::where([
                'producto_id' => $request->producto_id,
                'ubicacion_id' => $request->ubicacion_id
            ])->value('stock') ?? 0;

            Log::info('Stock encontrado', ['stock' => $stock]);

            return response()->json(['stock' => $stock]);
        } catch (\Exception $e) {
            Log::error('Error al consultar stock', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Error al consultar stock'], 500);
        }
    }
}