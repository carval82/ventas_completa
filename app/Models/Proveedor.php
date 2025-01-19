<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
   protected $table = 'proveedores';

   protected $fillable = [
       'nit',
       'razon_social',
       'regimen',
       'tipo_identificacion',
       'direccion',
       'ciudad',
       'telefono',
       'celular',
       'fax',
       'correo_electronico',
       'contacto',
       'estado'
   ];

   protected $attributes = [
       'estado' => true
   ];

   protected $casts = [
       'estado' => 'boolean'
   ];

   // Relaciones
   public function compras()
   {
       return $this->hasMany(Compra::class);
   }

   // Mutadores
   public function setNitAttribute($value)
   {
       $this->attributes['nit'] = strtoupper($value);
   }

   public function setRazonSocialAttribute($value) 
   {
       $this->attributes['razon_social'] = strtoupper($value);
   }

   // Alcances
   public function scopeActivos($query)
   {
       return $query->where('estado', true);
   }

   // Atributos calculados
   public function getTotalComprasAttribute()
   {
       return $this->compras()->sum('total');
   }

   public function getUltimaCompraAttribute()
   {
       return $this->compras()->latest()->first();
   }

   /**
    * Obtiene los productos asociados al proveedor
    */
   public function productos()
   {
       return $this->belongsToMany(Producto::class, 'producto_proveedor')
           ->withPivot('precio_compra', 'codigo_proveedor')
           ->withTimestamps();
   }

   /**
    * Obtiene los sugeridos de compra del proveedor
    */
   public function sugeridosCompra()
   {
       return $this->hasMany(SugeridoCompra::class);
   }
}