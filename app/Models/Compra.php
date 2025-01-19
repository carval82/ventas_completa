<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\GeneraComprobanteCompra;

class Compra extends Model
{
    use GeneraComprobanteCompra;

    protected $fillable = [
        'numero_factura',
        'fecha_compra',
        'subtotal',
        'iva',
        'total',
        'proveedor_id',
        'user_id'
    ];

    protected static function booted()
    {
        static::created(function ($compra) {
            $compra->generarComprobanteCompra();
        });
    }

    protected $casts = [
        'fecha_compra' => 'datetime',
        'subtotal' => 'decimal:2',
        'iva' => 'decimal:2',
        'total' => 'decimal:2'
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleCompra::class);
    }
}