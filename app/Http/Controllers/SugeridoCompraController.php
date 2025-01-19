<?php

namespace App\Http\Controllers;

use App\Models\SugeridoCompra;
use App\Models\Producto;
use App\Models\MovimientoInterno;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SugeridoCompraController extends Controller
{
    public function index(Request $request)
    {
        // Obtener todos los proveedores activos
        $proveedores = Proveedor::where('estado', true)->get();
        
        // Debug para ver qué contienen los proveedores
        \Log::info('Proveedores:', $proveedores->toArray());
        
        $proveedorActual = null;
        $sugeridos = collect();
        
        if ($request->has('proveedor_id')) {
            $proveedorActual = Proveedor::findOrFail($request->proveedor_id);
            \Log::info('Proveedor actual:', $proveedorActual->toArray());
            
            $sugeridos = SugeridoCompra::with(['producto' => function($query) {
                $query->select(
                    'id',
                    'codigo',
                    'nombre',
                    'descripcion',
                    'stock',
                    'stock_minimo',
                    'precio_compra'
                );
            }])
            ->where('proveedor_id', $request->proveedor_id)
            ->where('estado', 'pendiente')
            ->get();
        }
        
        return view('sugeridos.index', compact('proveedores', 'proveedorActual', 'sugeridos'));
    }

    public function actualizarCantidad(Request $request)
    {
        $sugerido = SugeridoCompra::findOrFail($request->sugerido_id);
        $sugerido->cantidad_sugerida = $request->cantidad;
        $sugerido->save();
        
        return response()->json(['success' => true]);
    }

    public function generarOrdenIndividual(SugeridoCompra $sugerido)
    {
        try {
            // Cargar el producto con sus precios
            $producto = $sugerido->producto;
            
            // Verificar que el precio de compra existe y es mayor que 0
            if (!$producto->precio_compra || $producto->precio_compra <= 0) {
                throw new \Exception("El producto {$producto->nombre} no tiene un precio de compra válido.");
            }

            DB::beginTransaction();
            
            // Crear la orden de compra
            $orden = OrdenCompra::create([
                'numero_orden' => 'OC-' . str_pad(OrdenCompra::max('id') + 1, 6, '0', STR_PAD_LEFT),
                'proveedor_id' => $sugerido->proveedor_id,
                'fecha_orden' => now(),
                'fecha_entrega_esperada' => now()->addDays(7),
                'estado' => 'pendiente',
                'total' => $sugerido->cantidad_sugerida * $producto->precio_compra,
                'observaciones' => 'Orden generada desde sugerido individual',
                'user_id' => auth()->id()
            ]);

            // Crear el detalle
            $orden->detalles()->create([
                'producto_id' => $sugerido->producto_id,
                'cantidad' => $sugerido->cantidad_sugerida,
                'precio_unitario' => $producto->precio_compra,
                'subtotal' => $sugerido->cantidad_sugerida * $producto->precio_compra
            ]);

            // Actualizar el estado del sugerido
            $sugerido->update(['estado' => 'procesado']);

            DB::commit();

            return redirect()->route('ordenes.show', $orden->id)
                ->with('success', 'La orden de compra ' . $orden->numero_orden . ' se ha generado correctamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('sugeridos.index')
                ->with('error', 'Error al generar la orden de compra: ' . $e->getMessage());
        }
    }

    public function generarOrden(Request $request)
    {
        try {
            $proveedor = Proveedor::findOrFail($request->proveedor_id);
            
            // Cargar los productos con sus precios
            $sugeridos = SugeridoCompra::with(['producto'])
                ->where('proveedor_id', $proveedor->id)
                ->where('estado', 'pendiente')
                ->get();

            if ($sugeridos->isEmpty()) {
                return redirect()->route('sugeridos.index')
                    ->with('error', 'No hay sugeridos pendientes para este proveedor');
            }

            DB::beginTransaction();
            
            // Calcular el total
            $total = $sugeridos->sum(function($s) { 
                return $s->cantidad_sugerida * ($s->precio_compra ?? 0); 
            });

            // Generar número de orden
            $ultimaOrden = OrdenCompra::latest('id')->first();
            $numeroOrden = 'OC-' . str_pad($ultimaOrden ? ($ultimaOrden->id + 1) : 1, 6, '0', STR_PAD_LEFT);

            // Crear la orden
            $orden = OrdenCompra::create([
                'numero_orden' => $numeroOrden,
                'proveedor_id' => $proveedor->id,
                'fecha_orden' => now(),
                'fecha_entrega_esperada' => now()->addDays(7),
                'estado' => 'pendiente',
                'total' => $total,
                'observaciones' => 'Orden generada desde sugeridos de compra',
                'user_id' => auth()->id()
            ]);

            // Crear los detalles verificando que haya precio
            foreach ($sugeridos as $sugerido) {
                // Verificar y obtener el precio
                $precioUnitario = $sugerido->precio_compra;
                
                if (!$precioUnitario) {
                    throw new \Exception('El producto ' . $sugerido->producto->nombre . ' no tiene precio de compra definido.');
                }

                $subtotal = $sugerido->cantidad_sugerida * $precioUnitario;

                $orden->detalles()->create([
                    'producto_id' => $sugerido->producto_id,
                    'cantidad' => $sugerido->cantidad_sugerida,
                    'precio_unitario' => $precioUnitario,
                    'subtotal' => $subtotal
                ]);

                $sugerido->update(['estado' => 'procesado']);
            }

            DB::commit();

            return redirect()->route('ordenes.show', $orden->id)
                ->with('success', 'La orden de compra ' . $numeroOrden . ' se ha generado correctamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('sugeridos.index')
                ->with('error', 'Error al generar la orden de compra: ' . $e->getMessage());
        }
    }
} 