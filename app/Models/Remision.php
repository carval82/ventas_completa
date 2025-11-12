<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Remision extends Model
{
    protected $table = 'remisiones';
    
    protected $fillable = [
        'numero_remision',
        'cliente_id',
        'fecha_remision',
        'fecha_entrega',
        'estado',
        'tipo',
        'subtotal',
        'descuento',
        'impuestos',
        'total',
        'observaciones',
        'direccion_entrega',
        'transportador',
        'vehiculo',
        'conductor',
        'cedula_conductor',
        'vendedor_id',
        'venta_id',
        'cotizacion_id'
    ];

    protected $casts = [
        'fecha_remision' => 'date',
        'fecha_entrega' => 'date',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'impuestos' => 'decimal:2',
        'total' => 'decimal:2'
    ];

    /**
     * Relación con Cliente
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Relación con Vendedor (Usuario)
     */
    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    /**
     * Relación con Venta (si proviene de una venta)
     */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    /**
     * Relación con Cotización (si proviene de una cotización)
     */
    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    /**
     * Relación con Detalle de Remisiones
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleRemision::class);
    }

    /**
     * Generar número de remisión automático
     */
    public static function generarNumeroRemision()
    {
        $ultimaRemision = self::orderBy('id', 'desc')->first();
        $numero = $ultimaRemision ? $ultimaRemision->id + 1 : 1;
        return 'REM-' . str_pad($numero, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verificar si la remisión está completamente entregada
     */
    public function estaCompletamenteEntregada(): bool
    {
        foreach ($this->detalles as $detalle) {
            if ($detalle->cantidad_entregada < $detalle->cantidad) {
                return false;
            }
        }
        return true;
    }

    /**
     * Calcular totales de la remisión
     */
    public function calcularTotales()
    {
        $subtotal = $this->detalles->sum('subtotal');
        $descuento = $this->detalles->sum('descuento_valor');
        $impuestos = $this->detalles->sum('impuesto_valor');
        $total = $subtotal - $descuento + $impuestos;

        $this->update([
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'impuestos' => $impuestos,
            'total' => $total
        ]);
    }

    /**
     * Actualizar stock según el tipo de remisión
     */
    public function actualizarStock($operacion = 'restar')
    {
        foreach ($this->detalles as $detalle) {
            $producto = $detalle->producto;
            
            if ($operacion === 'restar') {
                // Restar del stock (cuando se crea la remisión)
                $producto->decrement('stock', $detalle->cantidad);
            } elseif ($operacion === 'sumar') {
                // Sumar al stock (cuando se cancela la remisión)
                $producto->increment('stock', $detalle->cantidad);
            }
        }
    }

    /**
     * Scope para remisiones pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Scope para remisiones en tránsito
     */
    public function scopeEnTransito($query)
    {
        return $query->where('estado', 'en_transito');
    }

    /**
     * Scope para remisiones entregadas
     */
    public function scopeEntregadas($query)
    {
        return $query->where('estado', 'entregada');
    }

    /**
     * Scope por tipo de remisión
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }
}
