<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanCuenta extends Model
{
    protected $table = 'plan_cuentas';
    
    protected $fillable = [
        'codigo',
        'nombre',
        'tipo',
        'nivel',
        'cuenta_padre_id',
        'estado'
    ];

    protected $casts = [
        'estado' => 'boolean',
        'nivel' => 'integer'
    ];

    // Relación con la cuenta padre
    public function cuentaPadre()
    {
        return $this->belongsTo(PlanCuenta::class, 'cuenta_padre_id');
    }

    // Relación con las subcuentas
    public function subcuentas()
    {
        return $this->hasMany(PlanCuenta::class, 'cuenta_padre_id');
    }

    // Relación con movimientos contables
    public function movimientos()
    {
        return $this->hasMany(MovimientoContable::class, 'cuenta_id');
    }

    // Obtener saldo de la cuenta
    public function getSaldo($fechaInicio = null, $fechaFin = null)
    {
        $query = $this->movimientos();
        
        if ($fechaInicio) {
            $query->where('fecha', '>=', $fechaInicio);
        }
        
        if ($fechaFin) {
            $query->where('fecha', '<=', $fechaFin);
        }

        $totales = $query->selectRaw('SUM(debito) as total_debito, SUM(credito) as total_credito')
            ->first();

        $saldo = $totales->total_debito - $totales->total_credito;

        // Invertir saldo según el tipo de cuenta
        if (in_array($this->tipo, ['Pasivo', 'Patrimonio', 'Ingreso'])) {
            $saldo *= -1;
        }

        return $saldo;
    }
}