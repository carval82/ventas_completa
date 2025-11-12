<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// DetalleCompra.php
class DetalleCompra extends Model
{
    protected $fillable = [
        'compra_id',
        'producto_id',
        'cantidad',
        'unidad_medida',
        'factor_conversion',
        'cantidad_stock',
        'precio_unitario',
        'subtotal',
        'tiene_iva',
        'porcentaje_iva',
        'valor_iva',
        'total_con_iva'
    ];

    protected $casts = [
        'cantidad' => 'decimal:3',
        'factor_conversion' => 'decimal:6',
        'cantidad_stock' => 'decimal:3',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tiene_iva' => 'boolean',
        'porcentaje_iva' => 'decimal:2',
        'valor_iva' => 'decimal:2',
        'total_con_iva' => 'decimal:2'
    ];

    public function compra()
    {
        return $this->belongsTo(Compra::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
