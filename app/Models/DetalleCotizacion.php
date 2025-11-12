<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleCotizacion extends Model
{
    protected $table = 'detalle_cotizaciones';
    
    protected $fillable = [
        'cotizacion_id',
        'producto_id',
        'cantidad',
        'unidad_medida',
        'precio_unitario',
        'descuento_porcentaje',
        'descuento_valor',
        'subtotal',
        'impuesto_porcentaje',
        'impuesto_valor',
        'total',
        'observaciones'
    ];

    protected $casts = [
        'cantidad' => 'decimal:3',
        'precio_unitario' => 'decimal:2',
        'descuento_porcentaje' => 'decimal:2',
        'descuento_valor' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'impuesto_porcentaje' => 'decimal:2',
        'impuesto_valor' => 'decimal:2',
        'total' => 'decimal:2'
    ];

    /**
     * Relación con Cotización
     */
    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    /**
     * Relación con Producto
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Calcular totales del detalle
     */
    public function calcularTotales()
    {
        $subtotal = $this->cantidad * $this->precio_unitario;
        $descuento_valor = ($subtotal * $this->descuento_porcentaje) / 100;
        $base_impuesto = $subtotal - $descuento_valor;
        $impuesto_valor = ($base_impuesto * $this->impuesto_porcentaje) / 100;
        $total = $base_impuesto + $impuesto_valor;

        $this->update([
            'subtotal' => $subtotal,
            'descuento_valor' => $descuento_valor,
            'impuesto_valor' => $impuesto_valor,
            'total' => $total
        ]);
    }
}
