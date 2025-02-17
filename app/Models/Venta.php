<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\GeneraComprobanteContable;
use App\Models\Traits\GeneraComprobanteVentaCredito;
use Illuminate\Support\Facades\Log;

class Venta extends Model
{
    use GeneraComprobanteContable;
    use GeneraComprobanteVentaCredito;

    protected $fillable = [
        'numero_factura', 'fecha_venta', 
        'subtotal', 'iva', 'total',
        'cliente_id', 'user_id',
        'pago',
        'devuelta',
        'metodo_pago'
    ];

    protected $casts = [
        'fecha_venta' => 'datetime'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function credito()
    {
        return $this->hasOne(Credito::class);
    }

    protected static function booted()
    {
        static::created(function($venta) {
            Log::info('Venta creada - Verificando método de pago', [
                'venta_id' => $venta->id,
                'metodo_pago' => $venta->metodo_pago,
                'traits_loaded' => class_uses_recursive($venta),
                'tiene_metodo' => method_exists($venta, 'generarComprobanteVentaCredito')
            ]);

            // Evitar que se genere comprobante normal para ventas a crédito
            if ($venta->metodo_pago === 'credito') {
                Log::info('Venta a crédito detectada - No generando comprobante normal');
                return;
            }

            Log::info('Generando comprobante de venta normal');
            $venta->generarComprobanteVenta();
        });
    }
}
