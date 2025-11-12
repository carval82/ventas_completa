<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaCerdo extends Model
{
    use HasFactory;

    protected $table = 'ventas_cerdos';

    protected $fillable = [
        'cerdo_id',
        'cliente_id',
        'fecha_venta',
        'tipo_venta',
        'peso_venta',
        'precio_unitario',
        'precio_total',
        'observaciones',
    ];

    protected $casts = [
        'fecha_venta' => 'date',
    ];

    /**
     * Obtener el cerdo vendido
     */
    public function cerdo(): BelongsTo
    {
        return $this->belongsTo(Cerdo::class);
    }

    /**
     * Obtener el cliente que compró el cerdo
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Calcular la edad del cerdo al momento de la venta
     */
    public function getEdadVentaAttribute()
    {
        return $this->cerdo->camada->fecha_parto->diffInDays($this->fecha_venta);
    }

    /**
     * Calcular la ganancia por kilo
     */
    public function getGananciaKiloAttribute()
    {
        if ($this->tipo_venta == 'kilo') {
            return $this->precio_unitario;
        } else {
            return $this->peso_venta > 0 ? $this->precio_total / $this->peso_venta : 0;
        }
    }
}
