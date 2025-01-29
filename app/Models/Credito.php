<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Credito extends Model
{
    protected $fillable = [
        'venta_id',
        'cliente_id',
        'monto_total',
        'saldo_pendiente',
        'fecha_vencimiento',
        'estado'
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date'
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function pagos()
    {
        return $this->hasMany(PagoCredito::class);
    }
} 