<?php

namespace App\Http\Controllers;

use App\Models\MovimientoMasivo;
use App\Models\Producto;
use App\Models\Ubicacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Jobs\RegularizarProductosJob;

class MovimientoMasivoController extends Controller
{
    public function index(Request $request)
    {
        Log::info('Accediendo a la lista de movimientos masivos', ['filters' => $request->all()]);

        try {
            $query = MovimientoMasivo::with(['ubicacionDestino', 'usuario'])
                ->latest();

            // Filtro por número de documento
            if ($request->filled('documento')) {
                $query->where('numero_documento', 'LIKE', "%{$request->documento}%");
            }

            // Filtro por estado
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            $movimientos = $query->paginate(10);

            return view('movimientos-masivos.index', compact('movimientos'));

        } catch (\Exception $e) {
            Log::error('Error al cargar movimientos masivos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Error al cargar los movimientos: ' . $e->getMessage());
        }
    }

    public function create()
    {
        $productos = Producto::where('estado', true)->get();
        $ubicaciones = Ubicacion::where('estado', true)->get();
        return view('movimientos-masivos.create', compact('productos', 'ubicaciones'));
    }

    public function store(Request $request)
    {
        Log::info('Datos recibidos en store', [
            'request_data' => $request->all()
        ]);

        try {
            DB::beginTransaction();

            // Crear el movimiento masivo y obtener su ID
            $movimientoId = DB::table('movimientos_masivos')->insertGetId([
                'tipo_movimiento' => $request->tipo_movimiento,
                'numero_documento' => 'MOV-' . date('YmdHis'),
                'ubicacion_destino_id' => 1,
                'ubicacion_origen_id' => 1,
                'motivo' => 'Movimiento masivo',
                'user_id' => auth()->id(),
                'estado' => 'borrador',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::info('Movimiento masivo creado', [
                'movimiento_id' => $movimientoId
            ]);

            // Procesar cada producto
            foreach ($request->productos as $producto) {
                Log::info('Procesando producto para movimiento', [
                    'movimiento_id' => $movimientoId,
                    'producto' => $producto
                ]);
                
                // Insertar el detalle del movimiento
                DB::table('movimientos_masivos_detalle')->insert([
                    'movimiento_masivo_id' => $movimientoId,
                    'producto_id' => $producto['id'],
                    'cantidad' => $producto['cantidad'],
                    'costo_unitario' => $producto['precio_compra'],
                    'procesado' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();
            Log::info('Transacción completada exitosamente', [
                'movimiento_id' => $movimientoId
            ]);

            return redirect()->route('movimientos-masivos.index')
                ->with('success', 'Movimiento registrado correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en la transacción', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
            
            return back()->withErrors('Error al procesar el movimiento');
        }
    }

    public function show(MovimientoMasivo $movimientos_masivo)
    {
        $movimientos_masivo->load(['detalles.producto', 'usuario', 'ubicacionOrigen', 'ubicacionDestino']);
        return view('movimientos-masivos.show', compact('movimientos_masivo'));
    }

    public function procesar(MovimientoMasivo $movimientos_masivo)
    {
        try {
            DB::beginTransaction();
            
            // Actualizar estado del movimiento
            $movimientos_masivo->update([
                'estado' => 'procesado',
                'fecha_proceso' => now()
            ]);

            // Procesar cada detalle y actualizar stock
            foreach($movimientos_masivo->detalles as $detalle) {
                $producto = Producto::find($detalle->producto_id);
                
                // Actualizar stock según el tipo de movimiento
                if ($movimientos_masivo->tipo_movimiento === 'entrada') {
                    $producto->stock += $detalle->cantidad;
                } else {
                    $producto->stock -= $detalle->cantidad;
                }
                
                $producto->save();
                $detalle->update(['procesado' => 1]);
            }

            DB::commit();
            return redirect()->route('movimientos-masivos.show', $movimientos_masivo)
                ->with('success', 'Movimiento procesado correctamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Error al procesar el movimiento: ' . $e->getMessage());
        }
    }

    public function anular(MovimientoMasivo $movimientoMasivo)
    {
        try {
            if ($movimientoMasivo->estado !== 'borrador') {
                throw new \Exception('Solo se pueden anular movimientos en estado borrador');
            }

            $movimientoMasivo->update(['estado' => 'anulado']);
            return redirect()->route('movimientos-masivos.show', $movimientoMasivo)
                ->with('success', 'Movimiento anulado correctamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al anular el movimiento: ' . $e->getMessage());
        }
    }

    private function actualizarStock($producto, $ubicacion_id, $cantidad)
    {
        $stockActual = DB::table('producto_ubicacion')
            ->where('producto_id', $producto->id)
            ->where('ubicacion_id', $ubicacion_id)
            ->first();

        if ($stockActual) {
            DB::table('producto_ubicacion')
                ->where('producto_id', $producto->id)
                ->where('ubicacion_id', $ubicacion_id)
                ->update(['stock' => $stockActual->stock + $cantidad]);
        } else {
            DB::table('producto_ubicacion')->insert([
                'producto_id' => $producto->id,
                'ubicacion_id' => $ubicacion_id,
                'stock' => $cantidad
            ]);
        }
    }

    private function verificarStock($producto_id, $ubicacion_id)
    {
        return DB::table('producto_ubicacion')
            ->where('producto_id', $producto_id)
            ->where('ubicacion_id', $ubicacion_id)
            ->value('stock') ?? 0;
    }
}