<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Impuesto extends Model
{
    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'tipo',
        'porcentaje',
        'base_minima',
        'valor_fijo',
        'cuenta_impuesto_id',
        'cuenta_por_pagar_id',
        'cuenta_por_cobrar_id',
        'aplica_compras',
        'aplica_ventas',
        'es_retencion',
        'calcula_sobre_iva',
        'regimenes_aplica',
        'responsabilidades_exentas',
        'rango_desde',
        'rango_hasta',
        'activo',
        'fecha_inicio',
        'fecha_fin'
    ];

    protected $casts = [
        'porcentaje' => 'decimal:4',
        'base_minima' => 'decimal:2',
        'valor_fijo' => 'decimal:2',
        'rango_desde' => 'decimal:2',
        'rango_hasta' => 'decimal:2',
        'aplica_compras' => 'boolean',
        'aplica_ventas' => 'boolean',
        'es_retencion' => 'boolean',
        'calcula_sobre_iva' => 'boolean',
        'activo' => 'boolean',
        'regimenes_aplica' => 'array',
        'responsabilidades_exentas' => 'array',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date'
    ];

    /**
     * Relación con cuenta de impuesto
     */
    public function cuentaImpuesto(): BelongsTo
    {
        return $this->belongsTo(PlanCuenta::class, 'cuenta_impuesto_id');
    }

    /**
     * Relación con cuenta por pagar
     */
    public function cuentaPorPagar(): BelongsTo
    {
        return $this->belongsTo(PlanCuenta::class, 'cuenta_por_pagar_id');
    }

    /**
     * Relación con cuenta por cobrar
     */
    public function cuentaPorCobrar(): BelongsTo
    {
        return $this->belongsTo(PlanCuenta::class, 'cuenta_por_cobrar_id');
    }

    /**
     * Calcular impuesto sobre una base
     */
    public function calcular(float $base, ?Tercero $tercero = null): array
    {
        // Verificar si aplica según el tercero
        if ($tercero && !$this->aplicaParaTercero($tercero)) {
            return [
                'base' => $base,
                'porcentaje' => 0,
                'valor' => 0,
                'aplica' => false,
                'motivo' => 'No aplica para este tercero'
            ];
        }

        // Verificar base mínima
        if ($base < $this->base_minima) {
            return [
                'base' => $base,
                'porcentaje' => 0,
                'valor' => 0,
                'aplica' => false,
                'motivo' => 'Base inferior a mínima requerida'
            ];
        }

        // Verificar rangos
        if ($this->rango_desde && $base < $this->rango_desde) {
            return [
                'base' => $base,
                'porcentaje' => 0,
                'valor' => 0,
                'aplica' => false,
                'motivo' => 'Base fuera del rango'
            ];
        }

        if ($this->rango_hasta && $base > $this->rango_hasta) {
            return [
                'base' => $base,
                'porcentaje' => 0,
                'valor' => 0,
                'aplica' => false,
                'motivo' => 'Base fuera del rango'
            ];
        }

        // Calcular valor
        $valor = 0;
        if ($this->valor_fijo > 0) {
            $valor = $this->valor_fijo;
        } else {
            $valor = ($base * $this->porcentaje) / 100;
        }

        return [
            'base' => $base,
            'porcentaje' => $this->porcentaje,
            'valor' => round($valor, 2),
            'aplica' => true,
            'motivo' => 'Impuesto aplicado correctamente'
        ];
    }

    /**
     * Verificar si aplica para un tercero específico
     */
    public function aplicaParaTercero(Tercero $tercero): bool
    {
        // Verificar régimen fiscal
        if ($this->regimenes_aplica && !in_array($tercero->regimen_fiscal, $this->regimenes_aplica)) {
            return false;
        }

        // Verificar responsabilidades exentas
        if ($this->responsabilidades_exentas && $tercero->responsabilidades_fiscales) {
            $exentas = array_intersect($tercero->responsabilidades_fiscales, $this->responsabilidades_exentas);
            if (!empty($exentas)) {
                return false;
            }
        }

        // Verificar autorretenedores para retenciones
        if ($this->es_retencion) {
            switch ($this->tipo) {
                case 'retencion_renta':
                    return !$tercero->autorretenedor_renta;
                case 'retencion_iva':
                    return !$tercero->autorretenedor_iva;
                case 'retencion_ica':
                    return !$tercero->autorretenedor_ica;
            }
        }

        return true;
    }

    /**
     * Obtener impuestos activos por tipo
     */
    public static function porTipo(string $tipo): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('tipo', $tipo)
                    ->where('activo', true)
                    ->where('fecha_inicio', '<=', now())
                    ->where(function($query) {
                        $query->whereNull('fecha_fin')
                              ->orWhere('fecha_fin', '>=', now());
                    })
                    ->get();
    }

    /**
     * Obtener IVA por porcentaje
     */
    public static function getIva(float $porcentaje): ?self
    {
        return static::where('tipo', 'iva')
                    ->where('porcentaje', $porcentaje)
                    ->where('activo', true)
                    ->first();
    }

    /**
     * Obtener retención en la fuente por porcentaje
     */
    public static function getRetencionRenta(float $porcentaje): ?self
    {
        return static::where('tipo', 'retencion_renta')
                    ->where('porcentaje', $porcentaje)
                    ->where('activo', true)
                    ->first();
    }

    /**
     * Scopes
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true)
                    ->where('fecha_inicio', '<=', now())
                    ->where(function($q) {
                        $q->whereNull('fecha_fin')
                          ->orWhere('fecha_fin', '>=', now());
                    });
    }

    public function scopeParaVentas($query)
    {
        return $query->where('aplica_ventas', true);
    }

    public function scopeParaCompras($query)
    {
        return $query->where('aplica_compras', true);
    }

    public function scopeRetenciones($query)
    {
        return $query->where('es_retencion', true);
    }

    /**
     * Configuración de impuestos colombianos más comunes
     */
    public static function getConfiguracionBasica(): array
    {
        return [
            // IVA
            [
                'codigo' => 'IVA19',
                'nombre' => 'IVA 19%',
                'tipo' => 'iva',
                'porcentaje' => 19.0000,
                'aplica_ventas' => true,
                'aplica_compras' => true
            ],
            [
                'codigo' => 'IVA5',
                'nombre' => 'IVA 5%',
                'tipo' => 'iva',
                'porcentaje' => 5.0000,
                'aplica_ventas' => true,
                'aplica_compras' => true
            ],
            [
                'codigo' => 'IVA0',
                'nombre' => 'IVA 0%',
                'tipo' => 'iva',
                'porcentaje' => 0.0000,
                'aplica_ventas' => true,
                'aplica_compras' => true
            ],
            
            // Retención en la Fuente
            [
                'codigo' => 'RTE35',
                'nombre' => 'Retención Renta 3.5%',
                'tipo' => 'retencion_renta',
                'porcentaje' => 3.5000,
                'base_minima' => 27000, // Aprox 1 UVT
                'es_retencion' => true,
                'aplica_compras' => true
            ],
            [
                'codigo' => 'RTE25',
                'nombre' => 'Retención Renta 2.5%',
                'tipo' => 'retencion_renta',
                'porcentaje' => 2.5000,
                'base_minima' => 27000,
                'es_retencion' => true,
                'aplica_compras' => true
            ],
            
            // Retención de IVA
            [
                'codigo' => 'RTIVA15',
                'nombre' => 'Retención IVA 15%',
                'tipo' => 'retencion_iva',
                'porcentaje' => 15.0000,
                'calcula_sobre_iva' => true,
                'es_retencion' => true,
                'aplica_compras' => true
            ]
        ];
    }
}
