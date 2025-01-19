<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movimiento extends Model
{
    protected $table = 'movimientos';

    protected $fillable = [
        'producto_id',
        'tipo',
        'cantidad',
        'fecha',
        'referencia',
        'observaciones'
    ];

    protected $casts = [
        'fecha' => 'date'
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
} 