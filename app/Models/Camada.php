<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Camada extends Model
{
    use HasFactory;

    protected $table = 'camadas';

    protected $fillable = [
        'cerda_id',
        'fecha_parto',
        'total_nacidos',
        'nacidos_vivos',
        'nacidos_muertos',
        'fecha_destete',
        'total_destetados',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_parto' => 'date',
        'fecha_destete' => 'date',
    ];

    /**
     * Obtener la cerda madre de esta camada
     */
    public function cerda(): BelongsTo
    {
        return $this->belongsTo(Cerda::class);
    }

    /**
     * Obtener todos los cerdos de esta camada
     */
    public function cerdos(): HasMany
    {
        return $this->hasMany(Cerdo::class);
    }

    /**
     * Calcular la edad de la camada en días
     */
    public function getEdadAttribute()
    {
        return $this->fecha_parto->diffInDays(now());
    }

    /**
     * Calcular los días de lactancia
     */
    public function getDiasLactanciaAttribute()
    {
        if ($this->fecha_destete) {
            return $this->fecha_parto->diffInDays($this->fecha_destete);
        }
        return $this->fecha_parto->diffInDays(now());
    }

    /**
     * Calcular la tasa de supervivencia
     */
    public function getTasaSupervivenciaAttribute()
    {
        if ($this->nacidos_vivos > 0) {
            return ($this->total_destetados / $this->nacidos_vivos) * 100;
        }
        return 0;
    }

    /**
     * Destetar automáticamente la camada
     */
    public function destetar($fecha_destete, $total_destetados)
    {
        $this->fecha_destete = $fecha_destete;
        $this->total_destetados = $total_destetados;
        $this->estado = 'destetada';
        $this->save();

        // Crear automáticamente los cerdos destetados
        for ($i = 1; $i <= $total_destetados; $i++) {
            $codigo = $this->cerda->codigo . '-' . date('Ymd', strtotime($this->fecha_parto)) . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);
            
            Cerdo::create([
                'codigo' => $codigo,
                'camada_id' => $this->id,
                'sexo' => rand(0, 1) ? 'macho' : 'hembra', // Asignar sexo aleatoriamente para ejemplo
                'tipo' => 'engorde', // Por defecto todos son de engorde
                'estado' => 'destete',
            ]);
        }
    }
}
