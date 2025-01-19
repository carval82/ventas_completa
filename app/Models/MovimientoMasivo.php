<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoMasivo extends Model
{
    protected $table = 'movimientos_masivos';
    
    protected $fillable = [
        'numero_documento',
        'ubicacion_destino_id',
        'ubicacion_origen_id',
        'tipo_movimiento',
        'motivo',
        'observaciones',
        'user_id',
        'estado',
        'fecha_proceso'
    ];

    // Definimos los campos que deben ser tratados como fechas
    protected $casts = [
        'fecha_proceso' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function detalles()
    {
        return $this->hasMany(MovimientoMasivoDetalle::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ubicacionOrigen()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_origen_id');
    }

    public function ubicacionDestino()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_destino_id');
    }
}