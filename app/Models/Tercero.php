<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tercero extends Model
{
    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'digito_verificacion',
        'razon_social',
        'nombre_comercial',
        'primer_nombre',
        'segundo_nombre',
        'primer_apellido',
        'segundo_apellido',
        'tipo_persona',
        'tipo_tercero',
        'clasificaciones',
        'regimen_fiscal',
        'responsabilidades_fiscales',
        'email',
        'telefono',
        'celular',
        'direccion',
        'ciudad',
        'departamento',
        'codigo_postal',
        'pais',
        'cupo_credito',
        'dias_credito',
        'bloquear_cartera',
        'banco',
        'tipo_cuenta',
        'numero_cuenta',
        'autorretenedor_renta',
        'autorretenedor_iva',
        'autorretenedor_ica',
        'porcentaje_retencion_renta',
        'porcentaje_retencion_iva',
        'porcentaje_retencion_ica',
        'observaciones',
        'estado',
        'fecha_registro',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'clasificaciones' => 'array',
        'responsabilidades_fiscales' => 'array',
        'cupo_credito' => 'decimal:2',
        'porcentaje_retencion_renta' => 'decimal:2',
        'porcentaje_retencion_iva' => 'decimal:2',
        'porcentaje_retencion_ica' => 'decimal:2',
        'bloquear_cartera' => 'boolean',
        'autorretenedor_renta' => 'boolean',
        'autorretenedor_iva' => 'boolean',
        'autorretenedor_ica' => 'boolean',
        'estado' => 'boolean',
        'fecha_registro' => 'date'
    ];

    /**
     * Relación con usuario que creó
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación con usuario que actualizó
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Obtener nombre completo del tercero
     */
    public function getNombreCompletoAttribute(): string
    {
        if ($this->tipo_persona === 'juridica') {
            return $this->razon_social;
        }
        
        $nombres = array_filter([
            $this->primer_nombre,
            $this->segundo_nombre,
            $this->primer_apellido,
            $this->segundo_apellido
        ]);
        
        return implode(' ', $nombres) ?: $this->razon_social;
    }

    /**
     * Obtener NIT formateado con DV
     */
    public function getNitFormateadoAttribute(): string
    {
        if ($this->tipo_documento === 'NIT' && $this->digito_verificacion) {
            return $this->numero_documento . '-' . $this->digito_verificacion;
        }
        
        return $this->numero_documento;
    }

    /**
     * Verificar si es cliente
     */
    public function esCliente(): bool
    {
        return in_array('cliente', $this->clasificaciones ?? []) || $this->tipo_tercero === 'cliente';
    }

    /**
     * Verificar si es proveedor
     */
    public function esProveedor(): bool
    {
        return in_array('proveedor', $this->clasificaciones ?? []) || $this->tipo_tercero === 'proveedor';
    }

    /**
     * Verificar si es gran contribuyente
     */
    public function esGranContribuyente(): bool
    {
        return $this->regimen_fiscal === 'gran_contribuyente';
    }

    /**
     * Verificar si es autorretenedor
     */
    public function esAutorretenedor(): bool
    {
        return $this->regimen_fiscal === 'autorretenedor' || 
               $this->autorretenedor_renta || 
               $this->autorretenedor_iva || 
               $this->autorretenedor_ica;
    }

    /**
     * Calcular retención en la fuente
     */
    public function calcularRetencionRenta(float $baseGravable): float
    {
        if ($this->autorretenedor_renta || $this->esGranContribuyente()) {
            return 0;
        }
        
        return ($baseGravable * $this->porcentaje_retencion_renta) / 100;
    }

    /**
     * Calcular retención de IVA
     */
    public function calcularRetencionIva(float $valorIva): float
    {
        if ($this->autorretenedor_iva || $this->regimen_fiscal === 'simplificado') {
            return 0;
        }
        
        return ($valorIva * $this->porcentaje_retencion_iva) / 100;
    }

    /**
     * Calcular retención de ICA
     */
    public function calcularRetencionIca(float $baseGravable): float
    {
        if ($this->autorretenedor_ica) {
            return 0;
        }
        
        return ($baseGravable * $this->porcentaje_retencion_ica) / 100;
    }

    /**
     * Validar cupo de crédito disponible
     */
    public function validarCupoCredito(float $valorFactura): bool
    {
        if ($this->cupo_credito <= 0) {
            return true; // Sin límite
        }
        
        $carteraActual = $this->getCarteraActual();
        return ($carteraActual + $valorFactura) <= $this->cupo_credito;
    }

    /**
     * Obtener cartera actual del tercero
     */
    public function getCarteraActual(): float
    {
        // Aquí se calcularía la cartera pendiente
        // Por ahora retornamos 0
        return 0;
    }

    /**
     * Scopes
     */
    public function scopeClientes($query)
    {
        return $query->where(function($q) {
            $q->where('tipo_tercero', 'cliente')
              ->orWhereJsonContains('clasificaciones', 'cliente');
        });
    }

    public function scopeProveedores($query)
    {
        return $query->where(function($q) {
            $q->where('tipo_tercero', 'proveedor')
              ->orWhereJsonContains('clasificaciones', 'proveedor');
        });
    }

    public function scopeActivos($query)
    {
        return $query->where('estado', true);
    }

    /**
     * Responsabilidades fiscales DIAN más comunes
     */
    public static function getResponsabilidadesFiscales(): array
    {
        return [
            'O-13' => 'Gran Contribuyente',
            'O-15' => 'Autorretenedor',
            'O-23' => 'Agente de retención IVA',
            'O-47' => 'Régimen Simple de Tributación',
            'R-99-PN' => 'Responsable del Impuesto Nacional al Consumo'
        ];
    }

    /**
     * Generar dígito de verificación para NIT
     */
    public static function calcularDigitoVerificacion(string $nit): string
    {
        $vpri = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];
        $x = 0;
        $y = 0;
        $z = strlen($nit);
        
        for ($i = 0; $i < $z; $i++) {
            $y = substr($nit, $i, 1);
            $x += ($y * $vpri[$z - 1 - $i]);
        }
        
        $y = $x % 11;
        
        return ($y > 1) ? (string)(11 - $y) : (string)$y;
    }
}
