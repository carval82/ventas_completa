<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanCuenta extends Model
{
    protected $table = 'plan_cuentas';
    
    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'clase',
        'grupo',
        'cuenta',
        'subcuenta',
        'auxiliar',
        'tipo_cuenta',
        'naturaleza',
        'nivel',
        'cuenta_padre_id',
        'exige_tercero',
        'exige_centro_costo',
        'maneja_base',
        'cuenta_puente',
        'estado'
    ];

    protected $casts = [
        'estado' => 'boolean',
        'nivel' => 'integer',
        'exige_tercero' => 'boolean',
        'exige_centro_costo' => 'boolean',
        'maneja_base' => 'boolean',
        'cuenta_puente' => 'boolean'
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

        // Ajustar saldo según la naturaleza de la cuenta
        if ($this->naturaleza === 'credito') {
            $saldo *= -1;
        }

        return $saldo;
    }

    /**
     * Verificar si es cuenta de activo
     */
    public function esActivo(): bool
    {
        return $this->clase === '1';
    }

    /**
     * Verificar si es cuenta de pasivo
     */
    public function esPasivo(): bool
    {
        return $this->clase === '2';
    }

    /**
     * Verificar si es cuenta de patrimonio
     */
    public function esPatrimonio(): bool
    {
        return $this->clase === '3';
    }

    /**
     * Verificar si es cuenta de ingreso
     */
    public function esIngreso(): bool
    {
        return $this->clase === '4';
    }

    /**
     * Verificar si es cuenta de gasto
     */
    public function esGasto(): bool
    {
        return in_array($this->clase, ['5', '6']);
    }

    /**
     * Obtener saldo según naturaleza de la cuenta
     */
    public function getSaldoNaturaleza($fechaInicio = null, $fechaFin = null): float
    {
        $saldo = $this->getSaldo($fechaInicio, $fechaFin);
        
        // Para cuentas de naturaleza crédito, invertir el signo
        if ($this->naturaleza === 'credito') {
            $saldo *= -1;
        }
        
        return $saldo;
    }

    /**
     * Generar código PUC automático
     */
    public static function generarCodigoPUC(string $clase, string $grupo = null, string $cuenta = null, string $subcuenta = null): string
    {
        $codigo = $clase;
        
        if ($grupo) {
            $codigo .= str_pad($grupo, 2, '0', STR_PAD_LEFT);
        }
        
        if ($cuenta) {
            $codigo .= str_pad($cuenta, 2, '0', STR_PAD_LEFT);
        }
        
        if ($subcuenta) {
            $codigo .= str_pad($subcuenta, 2, '0', STR_PAD_LEFT);
        }
        
        return $codigo;
    }

    /**
     * Scopes para filtrar por tipo
     */
    public function scopeActivos($query)
    {
        return $query->where('clase', '1');
    }

    public function scopePasivos($query)
    {
        return $query->where('clase', '2');
    }

    public function scopePatrimonio($query)
    {
        return $query->where('clase', '3');
    }

    public function scopeIngresos($query)
    {
        return $query->where('clase', '4');
    }

    public function scopeGastos($query)
    {
        return $query->whereIn('clase', ['5', '6']);
    }

    public function scopeCuentasMovimiento($query)
    {
        return $query->where('nivel', '>=', 4); // Cuentas de 4 o más dígitos
    }
}