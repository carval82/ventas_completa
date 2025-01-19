<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\GeneraComprobanteContable;

class Venta extends Model
{
    use GeneraComprobanteContable;

    protected $fillable = [
        'numero_factura', 'fecha_venta', 
        'subtotal', 'iva', 'total',
        'cliente_id', 'user_id'
    ];

    protected $casts = [
        'fecha_venta' => 'datetime'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected static function booted()
    {
        static::created(function($venta) {
            $venta->generarComprobanteVenta();
        });
    }
}
