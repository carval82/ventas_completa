<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model 
{
   protected $fillable = [
       'nombres', 'apellidos', 'cedula', 'telefono',
       'email', 'direccion', 'estado'
   ];

   public function ventas()
   {
       return $this->hasMany(Venta::class);
   }
}