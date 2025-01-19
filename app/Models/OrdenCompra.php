<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenCompra extends Model
{
    protected $table = 'ordenes_compra';

    protected $fillable = [
        'numero_orden',
        'proveedor_id',
        'fecha_orden',
        'fecha_entrega_esperada',
        'estado',
        'total',
        'observaciones',
        'user_id'
    ];

    protected $casts = [
        'fecha_orden' => 'date',
        'fecha_entrega_esperada' => 'date'
    ];

    public function detalles()
    {
        return $this->hasMany(OrdenCompraDetalle::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function generarNumeroOrden()
    {
        $ultimaOrden = self::latest()->first();
        $ultimo = $ultimaOrden ? intval(substr($ultimaOrden->numero_orden, 3)) : 0;
        return 'OC-' . str_pad($ultimo + 1, 6, '0', STR_PAD_LEFT);
    }
} 