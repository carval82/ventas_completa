<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\MovimientoCaja;
use App\Models\User;

class CajaDiaria extends Model
{
    use HasFactory;

    protected $table = 'cajas_diarias';
    
    protected $fillable = [
        'fecha_apertura',
        'fecha_cierre',
        'monto_apertura',
        'monto_cierre',
        'total_ventas',
        'total_gastos',
        'total_pagos',
        'diferencia',
        'observaciones',
        'estado', // 'abierta', 'cerrada'
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
        'monto_apertura' => 'decimal:2',
        'monto_cierre' => 'decimal:2',
        'total_ventas' => 'decimal:2',
        'total_gastos' => 'decimal:2',
        'total_pagos' => 'decimal:2',
        'diferencia' => 'decimal:2',
    ];

    /**
     * Obtiene los movimientos asociados a esta caja
     */
    public function movimientos()
    {
        return $this->hasMany(MovimientoCaja::class, 'caja_id');
    }

    /**
     * Obtiene el usuario que creó la caja
     */
    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Obtiene el usuario que actualizó la caja
     */
    public function actualizadoPor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Obtiene las ventas asociadas a esta caja
     */
    public function ventas()
    {
        return $this->hasMany(Venta::class, 'caja_id');
    }

    /**
     * Calcula el total de ventas para esta caja
     */
    public function calcularTotalVentas()
    {
        return $this->ventas()->sum('total');
    }

    /**
     * Calcula el total de gastos para esta caja
     */
    public function calcularTotalGastos()
    {
        return $this->movimientos()
            ->where('tipo', 'gasto')
            ->sum('monto');
    }

    /**
     * Calcula el total de pagos para esta caja
     */
    public function calcularTotalPagos()
    {
        return $this->movimientos()
            ->where('tipo', 'pago')
            ->sum('monto');
    }

    /**
     * Calcula la diferencia entre el monto de cierre teórico y el real
     */
    public function calcularDiferencia()
    {
        $montoTeorico = $this->monto_apertura + $this->total_ventas - $this->total_gastos - $this->total_pagos;
        return $this->monto_cierre - $montoTeorico;
    }

    /**
     * Verifica si la caja está abierta
     */
    public function estaAbierta()
    {
        return $this->estado === 'abierta';
    }

    /**
     * Verifica si hay una caja abierta para la fecha actual
     */
    public static function hayUnaAbierta()
    {
        return self::where('estado', 'abierta')->exists();
    }

    /**
     * Obtiene la caja abierta actual, si existe
     */
    public static function obtenerCajaAbierta()
    {
        return self::where('estado', 'abierta')->first();
    }
}
