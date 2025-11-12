<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';
    
    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'id';
    }
    
    protected $fillable = [
        'numero_cotizacion',
        'fecha_cotizacion',
        'fecha_vencimiento',
        'estado',
        'subtotal',
        'descuento',
        'impuestos',
        'total',
        'observaciones',
        'condiciones_comerciales',
        'forma_pago',
        'dias_validez',
        'vendedor_id',
        'venta_id'
    ];

    protected $casts = [
        'fecha_cotizacion' => 'date',
        'fecha_vencimiento' => 'date',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'impuestos' => 'decimal:2',
        'total' => 'decimal:2',
        'dias_validez' => 'integer'
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
     * Relación con Venta (si se convierte)
     */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    /**
     * Relación con Detalle de Cotizaciones
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleCotizacion::class);
    }

    /**
     * Generar número de cotización automático
     */
    public static function generarNumeroCotizacion()
    {
        $ultimaCotizacion = self::orderBy('id', 'desc')->first();
        $numero = $ultimaCotizacion ? $ultimaCotizacion->id + 1 : 1;
        return 'COT-' . str_pad($numero, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verificar si la cotización está vencida
     */
    public function estaVencida(): bool
    {
        return $this->fecha_vencimiento < now()->toDateString() && $this->estado === 'pendiente';
    }

    /**
     * Calcular totales de la cotización
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
     * Scope para cotizaciones pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Scope para cotizaciones vencidas
     */
    public function scopeVencidas($query)
    {
        return $query->where('estado', 'pendiente')
                    ->where('fecha_vencimiento', '<', now()->toDateString());
    }
}
