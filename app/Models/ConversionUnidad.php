<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversionUnidad extends Model
{
    protected $table = 'conversiones_unidades';
    
    protected $fillable = [
        'unidad_origen',
        'unidad_destino', 
        'factor_conversion',
        'categoria',
        'activo'
    ];

    protected $casts = [
        'factor_conversion' => 'decimal:6',
        'activo' => 'boolean'
    ];

    /**
     * Obtener factor de conversión entre dos unidades
     */
    public static function obtenerFactor($unidadOrigen, $unidadDestino)
    {
        // Si son la misma unidad, factor es 1
        if ($unidadOrigen === $unidadDestino) {
            return 1.0;
        }

        // Buscar conversión directa
        $conversion = self::where('unidad_origen', $unidadOrigen)
                         ->where('unidad_destino', $unidadDestino)
                         ->where('activo', true)
                         ->first();

        if ($conversion) {
            return $conversion->factor_conversion;
        }

        // Buscar conversión inversa
        $conversionInversa = self::where('unidad_origen', $unidadDestino)
                               ->where('unidad_destino', $unidadOrigen)
                               ->where('activo', true)
                               ->first();

        if ($conversionInversa) {
            return 1 / $conversionInversa->factor_conversion;
        }

        return null; // No hay conversión disponible
    }

    /**
     * Convertir cantidad entre unidades
     */
    public static function convertir($cantidad, $unidadOrigen, $unidadDestino)
    {
        $factor = self::obtenerFactor($unidadOrigen, $unidadDestino);
        
        if ($factor === null) {
            return null;
        }

        return $cantidad * $factor;
    }

    /**
     * Obtener todas las unidades disponibles por categoría
     */
    public static function obtenerUnidadesPorCategoria($categoria = null)
    {
        $query = self::where('activo', true);
        
        if ($categoria) {
            $query->where('categoria', $categoria);
        }

        return $query->select('unidad_origen as unidad')
                    ->union(
                        self::select('unidad_destino as unidad')
                            ->where('activo', true)
                            ->when($categoria, function($q) use ($categoria) {
                                return $q->where('categoria', $categoria);
                            })
                    )
                    ->distinct()
                    ->orderBy('unidad')
                    ->pluck('unidad');
    }

    /**
     * Obtener conversiones disponibles para una unidad
     */
    public static function obtenerConversionesDisponibles($unidad)
    {
        return self::where('activo', true)
                  ->where(function($query) use ($unidad) {
                      $query->where('unidad_origen', $unidad)
                            ->orWhere('unidad_destino', $unidad);
                  })
                  ->get()
                  ->map(function($conversion) use ($unidad) {
                      if ($conversion->unidad_origen === $unidad) {
                          return [
                              'unidad' => $conversion->unidad_destino,
                              'factor' => $conversion->factor_conversion,
                              'categoria' => $conversion->categoria
                          ];
                      } else {
                          return [
                              'unidad' => $conversion->unidad_origen,
                              'factor' => 1 / $conversion->factor_conversion,
                              'categoria' => $conversion->categoria
                          ];
                      }
                  });
    }
}
