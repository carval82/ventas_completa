<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleRemision extends Model
{
    protected $table = 'detalle_remisiones';
    
    protected $fillable = [
        'remision_id',
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
        'cantidad_entregada',
        'cantidad_devuelta',
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
        'total' => 'decimal:2',
        'cantidad_entregada' => 'decimal:3',
        'cantidad_devuelta' => 'decimal:3'
    ];

    /**
     * Relación con Remisión
     */
    public function remision(): BelongsTo
    {
        return $this->belongsTo(Remision::class);
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

    /**
     * Verificar si el producto está completamente entregado
     */
    public function estaCompletamenteEntregado(): bool
    {
        return $this->cantidad_entregada >= $this->cantidad;
    }

    /**
     * Cantidad pendiente por entregar
     */
    public function cantidadPendiente(): float
    {
        return $this->cantidad - $this->cantidad_entregada - $this->cantidad_devuelta;
    }

    /**
     * Registrar entrega parcial o total
     */
    public function registrarEntrega(float $cantidad): bool
    {
        $cantidadPendiente = $this->cantidadPendiente();
        
        if ($cantidad > $cantidadPendiente) {
            return false; // No se puede entregar más de lo pendiente
        }

        $this->increment('cantidad_entregada', $cantidad);
        return true;
    }

    /**
     * Registrar devolución
     */
    public function registrarDevolucion(float $cantidad): bool
    {
        if ($cantidad > $this->cantidad_entregada) {
            return false; // No se puede devolver más de lo entregado
        }

        $this->increment('cantidad_devuelta', $cantidad);
        $this->decrement('cantidad_entregada', $cantidad);
        
        // Devolver al stock
        $this->producto->increment('stock', $cantidad);
        
        return true;
    }
}
