<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CajaDiaria;
use App\Models\User;
use App\Models\Venta;
use App\Models\Compra;

class MovimientoCaja extends Model
{
    use HasFactory;

    protected $table = 'movimientos_caja';
    
    protected $fillable = [
        'caja_id',
        'fecha',
        'tipo', // 'ingreso', 'gasto', 'pago'
        'concepto',
        'referencia_id', // ID de la venta, compra, etc.
        'referencia_tipo', // 'App\Models\Venta', 'App\Models\Compra', etc.
        'monto',
        'metodo_pago', // 'efectivo', 'transferencia', 'tarjeta', etc.
        'observaciones',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'monto' => 'decimal:2',
    ];

    /**
     * Obtiene la caja a la que pertenece este movimiento
     */
    public function caja()
    {
        return $this->belongsTo(CajaDiaria::class, 'caja_id');
    }

    /**
     * Obtiene el usuario que creó el movimiento
     */
    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Obtiene el usuario que actualizó el movimiento
     */
    public function actualizadoPor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Obtiene el modelo relacionado (venta, compra, etc.)
     */
    public function referencia()
    {
        return $this->morphTo('referencia');
    }

    /**
     * Scope para filtrar por tipo de movimiento
     */
    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Scope para filtrar por fecha
     */
    public function scopeFecha($query, $fecha)
    {
        return $query->whereDate('fecha', $fecha);
    }

    /**
     * Scope para filtrar por rango de fechas
     */
    public function scopeEntreFechas($query, $desde, $hasta)
    {
        return $query->whereDate('fecha', '>=', $desde)
                     ->whereDate('fecha', '<=', $hasta);
    }

    /**
     * Determina si es un ingreso
     */
    public function esIngreso()
    {
        return $this->tipo === 'ingreso';
    }

    /**
     * Determina si es un gasto
     */
    public function esGasto()
    {
        return $this->tipo === 'gasto';
    }

    /**
     * Determina si es un pago
     */
    public function esPago()
    {
        return $this->tipo === 'pago';
    }
}
