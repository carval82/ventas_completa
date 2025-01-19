<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoContable extends Model
{
    protected $table = 'movimientos_contables';

    protected $fillable = [
        'comprobante_id',
        'cuenta_id',
        'fecha',
        'descripcion',
        'debito',
        'credito',
        'referencia',
        'referencia_tipo'
    ];

    protected $casts = [
        'fecha' => 'date',
        'debito' => 'decimal:2',
        'credito' => 'decimal:2'
    ];

    // Relación con comprobante
    public function comprobante()
    {
        return $this->belongsTo(Comprobante::class);
    }

    // Relación con cuenta contable
    public function cuenta()
    {
        return $this->belongsTo(PlanCuenta::class, 'cuenta_id');
    }

    // Obtener el modelo referenciado (polimórfico)
    public function getReferencia()
    {
        if (!$this->referencia || !$this->referencia_tipo) {
            return null;
        }

        $modelo = "App\\Models\\" . $this->referencia_tipo;
        return $modelo::find($this->referencia);
    }
}