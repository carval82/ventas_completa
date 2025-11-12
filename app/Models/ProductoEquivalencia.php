<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoEquivalencia extends Model
{
    use HasFactory;

    protected $table = 'producto_equivalencias';

    protected $fillable = [
        'producto_id',
        'unidad_origen',
        'unidad_destino',
        'factor_conversion',
        'descripcion',
        'activo'
    ];

    protected $casts = [
        'factor_conversion' => 'decimal:4',
        'activo' => 'boolean'
    ];

    /**
     * Relación con el producto
     */
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Obtener todas las unidades disponibles para un producto
     */
    public static function getUnidadesDisponibles($productoId)
    {
        $equivalencias = self::where('producto_id', $productoId)
            ->where('activo', true)
            ->get();

        $unidades = collect();
        
        foreach ($equivalencias as $equiv) {
            $unidades->push($equiv->unidad_origen);
            $unidades->push($equiv->unidad_destino);
        }

        return $unidades->unique()->values();
    }

    /**
     * Convertir cantidad de una unidad a otra
     */
    public static function convertir($productoId, $unidadOrigen, $unidadDestino, $cantidad)
    {
        // Si son la misma unidad, no hay conversión
        if ($unidadOrigen === $unidadDestino) {
            return [
                'success' => true,
                'cantidad_convertida' => $cantidad,
                'factor_usado' => 1,
                'ruta' => [$unidadOrigen]
            ];
        }

        // Buscar conversión directa
        $conversionDirecta = self::where('producto_id', $productoId)
            ->where('unidad_origen', $unidadOrigen)
            ->where('unidad_destino', $unidadDestino)
            ->where('activo', true)
            ->first();

        if ($conversionDirecta) {
            return [
                'success' => true,
                'cantidad_convertida' => $cantidad * $conversionDirecta->factor_conversion,
                'factor_usado' => $conversionDirecta->factor_conversion,
                'ruta' => [$unidadOrigen, $unidadDestino],
                'descripcion' => $conversionDirecta->descripcion
            ];
        }

        // Buscar conversión a través de unidad intermedia (ej: paca -> kg -> lb)
        $conversionIndirecta = self::buscarConversionIndirecta($productoId, $unidadOrigen, $unidadDestino, $cantidad);
        
        if ($conversionIndirecta['success']) {
            return $conversionIndirecta;
        }

        return [
            'success' => false,
            'error' => 'No se encontró conversión entre estas unidades',
            'unidad_origen' => $unidadOrigen,
            'unidad_destino' => $unidadDestino
        ];
    }

    /**
     * Buscar conversión indirecta a través de unidades intermedias
     */
    private static function buscarConversionIndirecta($productoId, $unidadOrigen, $unidadDestino, $cantidad)
    {
        // Obtener todas las equivalencias del producto
        $equivalencias = self::where('producto_id', $productoId)
            ->where('activo', true)
            ->get();

        // Buscar unidades intermedias desde el origen
        $desdeOrigen = $equivalencias->where('unidad_origen', $unidadOrigen);
        
        foreach ($desdeOrigen as $paso1) {
            $unidadIntermedia = $paso1->unidad_destino;
            
            // Buscar conversión desde la unidad intermedia al destino
            $paso2 = $equivalencias->where('unidad_origen', $unidadIntermedia)
                ->where('unidad_destino', $unidadDestino)
                ->first();
            
            if ($paso2) {
                $cantidadIntermedia = $cantidad * $paso1->factor_conversion;
                $cantidadFinal = $cantidadIntermedia * $paso2->factor_conversion;
                $factorTotal = $paso1->factor_conversion * $paso2->factor_conversion;
                
                return [
                    'success' => true,
                    'cantidad_convertida' => $cantidadFinal,
                    'factor_usado' => $factorTotal,
                    'ruta' => [$unidadOrigen, $unidadIntermedia, $unidadDestino],
                    'descripcion' => "Conversión: {$paso1->descripcion} → {$paso2->descripcion}"
                ];
            }
        }

        return ['success' => false];
    }

    /**
     * Obtener el factor de conversión entre dos unidades
     */
    public static function getFactorConversion($productoId, $unidadOrigen, $unidadDestino)
    {
        $resultado = self::convertir($productoId, $unidadOrigen, $unidadDestino, 1);
        
        return $resultado['success'] ? $resultado['factor_usado'] : null;
    }

    /**
     * Verificar si existe conversión entre dos unidades
     */
    public static function existeConversion($productoId, $unidadOrigen, $unidadDestino)
    {
        $resultado = self::convertir($productoId, $unidadOrigen, $unidadDestino, 1);
        
        return $resultado['success'];
    }
}
