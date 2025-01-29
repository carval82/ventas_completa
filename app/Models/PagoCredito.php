<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoCredito extends Model
{
    protected $table = 'pagos_credito';
    
    protected $fillable = [
        'credito_id',
        'monto',
        'fecha_pago',
        'comprobante',
        'observacion'
    ];

    protected $casts = [
        'fecha_pago' => 'date'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($pago) {
            $pago->comprobante = static::generarNumeroComprobante();
        });
    }
    
    protected static function generarNumeroComprobante()
    {
        $ultimoPago = static::orderBy('id', 'desc')->first();
        $ultimoNumero = $ultimoPago ? intval(substr($ultimoPago->comprobante, 3)) : 0;
        $siguiente = $ultimoNumero + 1;
        
        return 'RCP' . str_pad($siguiente, 8, '0', STR_PAD_LEFT);
    }

    public function credito()
    {
        return $this->belongsTo(Credito::class);
    }
} 