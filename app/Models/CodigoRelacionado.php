<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodigoRelacionado extends Model
{
    use HasFactory;

    protected $table = 'codigos_relacionados';
    
    protected $fillable = [
        'codigo',
        'descripcion',
        'producto_id'
    ];

    /**
     * Obtiene el producto principal al que está asociado este código.
     */
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
