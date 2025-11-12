<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cerda extends Model
{
    use HasFactory;

    protected $table = 'cerdas';

    protected $fillable = [
        'codigo',
        'nombre',
        'fecha_nacimiento',
        'raza',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    /**
     * Obtener todas las camadas de esta cerda
     */
    public function camadas(): HasMany
    {
        return $this->hasMany(Camada::class);
    }

    /**
     * Calcular la edad de la cerda en meses
     */
    public function getEdadAttribute()
    {
        return $this->fecha_nacimiento->diffInMonths(now());
    }

    /**
     * Obtener el total de lechones nacidos de esta cerda
     */
    public function getTotalLechonesAttribute()
    {
        return $this->camadas->sum('total_nacidos');
    }

    /**
     * Obtener el total de lechones vivos nacidos de esta cerda
     */
    public function getTotalLechonesVivosAttribute()
    {
        return $this->camadas->sum('nacidos_vivos');
    }

    /**
     * Obtener el total de lechones destetados de esta cerda
     */
    public function getTotalLechonesDestetadosAttribute()
    {
        return $this->camadas->sum('total_destetados');
    }
}
