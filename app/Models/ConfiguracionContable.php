<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionContable extends Model
{
    protected $table = 'configuracion_contable';

    protected $fillable = [
        'concepto',
        'cuenta_id',
        'descripcion',
        'estado'
    ];

    protected $casts = [
        'estado' => 'boolean'
    ];

    // Relación con cuenta contable
    public function cuenta()
    {
        return $this->belongsTo(PlanCuenta::class, 'cuenta_id');
    }

    // Obtener configuración por concepto
    public static function getCuentaPorConcepto($concepto)
    {
        $config = static::where('concepto', $concepto)
            ->where('estado', true)
            ->first();
            
        return $config ? $config->cuenta : null;
    }
}