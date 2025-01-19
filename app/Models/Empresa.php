<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// app/Models/Empresa.php
class Empresa extends Model
{
    protected $fillable = [
        'nombre_comercial',
        'razon_social',
        'nit',
        'direccion', 
        'telefono',
        'email',
        'sitio_web',
        'logo',
        'regimen_tributario'
    ];
}