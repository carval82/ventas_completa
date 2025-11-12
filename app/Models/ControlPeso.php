<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlPeso extends Model
{
    use HasFactory;

    protected $table = 'control_pesos';

    protected $fillable = [
        'cerdo_id',
        'fecha_pesaje',
        'peso',
        'etapa',
        'observaciones',
    ];

    protected $casts = [
        'fecha_pesaje' => 'date',
    ];

    /**
     * Obtener el cerdo al que pertenece este registro de peso
     */
    public function cerdo(): BelongsTo
    {
        return $this->belongsTo(Cerdo::class);
    }

    /**
     * Calcular la ganancia de peso desde el último registro
     */
    public function getGananciaPesoAttribute()
    {
        $anterior = ControlPeso::where('cerdo_id', $this->cerdo_id)
            ->where('fecha_pesaje', '<', $this->fecha_pesaje)
            ->orderBy('fecha_pesaje', 'desc')
            ->first();

        if ($anterior) {
            return $this->peso - $anterior->peso;
        }
        
        return 0;
    }

    /**
     * Calcular la ganancia de peso diaria desde el último registro
     */
    public function getGananciaPesoDiariaAttribute()
    {
        $anterior = ControlPeso::where('cerdo_id', $this->cerdo_id)
            ->where('fecha_pesaje', '<', $this->fecha_pesaje)
            ->orderBy('fecha_pesaje', 'desc')
            ->first();

        if ($anterior) {
            $dias = $anterior->fecha_pesaje->diffInDays($this->fecha_pesaje);
            if ($dias > 0) {
                return ($this->peso - $anterior->peso) / $dias;
            }
        }
        
        return 0;
    }
}
