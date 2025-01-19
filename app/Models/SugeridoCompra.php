<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SugeridoCompra extends Model
{
    protected $table = 'sugeridos_compra';

    protected $fillable = [
        'producto_id',
        'proveedor_id',
        'cantidad_sugerida',
        'consumo_promedio_semanal',
        'stock_actual',
        'stock_minimo',
        'fecha_calculo',
        'estado',
        'observaciones'
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }
} 