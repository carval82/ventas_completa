<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoMasivoDetalle extends Model
{
    protected $table = 'movimientos_masivos_detalle';
    
    protected $fillable = [
        'movimiento_masivo_id',
        'producto_id',
        'cantidad',
        'costo_unitario',
        'procesado'
    ];

    public function movimientoMasivo()
    {
        return $this->belongsTo(MovimientoMasivo::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}