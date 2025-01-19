<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ubicacion extends Model
{
    protected $table = 'ubicaciones';

    protected $fillable = [
        'nombre',
        'tipo',
        'descripcion',
        'estado'
    ];

    protected $casts = [
        'estado' => 'boolean'
    ];

    public function stockUbicaciones()
    {
        return $this->hasMany(StockUbicacion::class);
    }

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'stock_ubicaciones')
            ->withPivot('stock');
    }
}