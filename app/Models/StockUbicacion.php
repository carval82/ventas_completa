<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class StockUbicacion extends Model
{
    protected $table = 'stock_ubicaciones';

    protected $fillable = [
        'producto_id',
        'ubicacion_id',
        'stock'
    ];

    // Relaciones
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class);
    }

    // Métodos de utilidad
    public static function actualizarStock($producto_id, $ubicacion_id, $cantidad, $operacion = 'sumar')
{
    $stock = self::firstOrNew([
        'producto_id' => $producto_id,
        'ubicacion_id' => $ubicacion_id
    ]);

    if (!$stock->exists) {
        $stock->stock = 0;
    }

    if ($operacion === 'sumar') {
        $stock->stock += $cantidad;
    } else {
        $stock->stock -= $cantidad;
    }

    $stock->save();

    return $stock;
}

    // Método para verificar si hay stock suficiente
    public function tieneStockSuficiente($cantidad)
    {
        return ($this->stock ?? 0) >= $cantidad;
    }

    // Método para obtener stock total de un producto
    public static function stockTotalProducto($producto_id)
    {
        return self::where('producto_id', $producto_id)->sum('stock');
    }

    // Método para obtener stock por ubicación de un producto
    public static function stockPorUbicacion($producto_id)
    {
        return self::where('producto_id', $producto_id)
            ->with('ubicacion')
            ->get()
            ->map(function ($item) {
                return [
                    'ubicacion' => $item->ubicacion->nombre,
                    'stock' => $item->stock
                ];
            });
    }

    // Método para obtener productos con stock bajo por ubicación
    public static function productosStockBajo()
    {
        return self::with(['producto', 'ubicacion'])
            ->whereHas('producto', function($query) {
                $query->whereColumn('stock_ubicaciones.stock', '<=', 'productos.stock_minimo');
            })
            ->get();
    }

    // Método para transferir stock entre ubicaciones
    public static function transferirStock($producto_id, $origen_id, $destino_id, $cantidad)
    {
        $stockOrigen = self::where([
            'producto_id' => $producto_id,
            'ubicacion_id' => $origen_id
        ])->first();

        if (!$stockOrigen || !$stockOrigen->tieneStockSuficiente($cantidad)) {
            throw new \Exception('Stock insuficiente en la ubicación de origen');
        }

        DB::transaction(function() use ($producto_id, $origen_id, $destino_id, $cantidad) {
            // Reducir stock en origen
            self::actualizarStock($producto_id, $origen_id, $cantidad, 'restar');
            
            // Aumentar stock en destino
            self::actualizarStock($producto_id, $destino_id, $cantidad, 'sumar');
        });

        return true;
    }
}