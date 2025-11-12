<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleVenta extends Model
{
   protected $fillable = [
       'venta_id', 'producto_id',
       'cantidad', 'precio_unitario', 'subtotal',
       'tiene_iva', 'porcentaje_iva', 'valor_iva', 'total_con_iva'
   ];
   
   protected $casts = [
       'precio_unitario' => 'decimal:2',
       'subtotal' => 'decimal:2',
       'tiene_iva' => 'boolean',
       'porcentaje_iva' => 'decimal:2',
       'valor_iva' => 'decimal:2',
       'total_con_iva' => 'decimal:2'
   ];

   public function venta()
   {
       return $this->belongsTo(Venta::class);
   }

   public function producto()
   {
       return $this->belongsTo(Producto::class);
   }
}
