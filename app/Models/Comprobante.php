<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comprobante extends Model
{
    protected $table = 'comprobantes';

    protected $fillable = [
        'fecha',
        'tipo',
        'prefijo',
        'numero',
        'descripcion',
        'estado',
        'created_by',
        'total_debito',
        'total_credito',
        'approved_by'
    ];

    protected $casts = [
        'fecha' => 'date',
        'total_debito' => 'decimal:2',
        'total_credito' => 'decimal:2'
    ];

    // Relación con movimientos
    public function movimientos()
    {
        return $this->hasMany(MovimientoContable::class);
    }

    // Relación con usuario que creó
    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relación con usuario que aprobó
    public function aprobadoPor()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Verificar si está cuadrado
    public function estaCuadrado()
    {
        return $this->total_debito == $this->total_credito;
    }
}