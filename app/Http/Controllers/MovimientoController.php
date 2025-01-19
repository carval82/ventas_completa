<?php

namespace App\Http\Controllers;

use App\Models\Movimiento;
use App\Models\Producto;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MovimientoController extends Controller
{
    /**
     * Registra movimientos automáticos por venta
     */
    public function registrarMovimientoVenta($venta)
    {
        DB::beginTransaction();
        try {
            foreach ($venta->detalles as $detalle) {
                Movimiento::create([
                    'producto_id' => $detalle->producto_id,
                    'tipo' => 'salida',
                    'cantidad' => $detalle->cantidad,
                    'fecha' => $venta->fecha,
                    'referencia' => 'VENTA-' . $venta->numero_factura,
                    'observaciones' => 'Venta automática'
                ]);

                // Actualizar stock
                $detalle->producto->decrement('stock', $detalle->cantidad);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Registra movimientos automáticos por compra
     */
    public function registrarMovimientoCompra($ordenCompra)
    {
        DB::beginTransaction();
        try {
            foreach ($ordenCompra->detalles as $detalle) {
                Movimiento::create([
                    'producto_id' => $detalle->producto_id,
                    'tipo' => 'entrada',
                    'cantidad' => $detalle->cantidad,
                    'fecha' => $ordenCompra->fecha_recepcion,
                    'referencia' => 'OC-' . $ordenCompra->numero_orden,
                    'observaciones' => 'Recepción de compra'
                ]);

                // Actualizar stock
                $detalle->producto->increment('stock', $detalle->cantidad);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Registra movimientos masivos (ajustes, mermas, etc.)
     */
    public function registrarMovimientoMasivo(Request $request)
    {
        $request->validate([
            'productos' => 'required|array',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer',
            'tipo' => 'required|in:entrada,salida',
            'referencia' => 'required|string',
            'observaciones' => 'required|string'
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->productos as $movimiento) {
                Movimiento::create([
                    'producto_id' => $movimiento['producto_id'],
                    'tipo' => $request->tipo,
                    'cantidad' => abs($movimiento['cantidad']),
                    'fecha' => now(),
                    'referencia' => $request->referencia,
                    'observaciones' => $request->observaciones
                ]);

                $producto = Producto::find($movimiento['producto_id']);
                if ($request->tipo === 'entrada') {
                    $producto->increment('stock', $movimiento['cantidad']);
                } else {
                    $producto->decrement('stock', $movimiento['cantidad']);
                }
            }
            DB::commit();
            return response()->json(['message' => 'Movimientos registrados correctamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtiene el historial de movimientos para el cálculo de sugeridos
     */
    public function obtenerHistorialMovimientos($producto_id, $semanas = 4)
    {
        $fechaInicio = Carbon::now()->subWeeks($semanas);
        
        return Movimiento::where('producto_id', $producto_id)
            ->where('fecha', '>=', $fechaInicio)
            ->orderBy('fecha', 'desc')
            ->get();
    }
} 