<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConfiguracionDian extends Model
{
    protected $table = 'configuracion_dian';

    protected $fillable = [
        'empresa_id',
        'email_dian',
        'password_email',
        'servidor_imap',
        'puerto_imap',
        'ssl_enabled',
        'carpeta_descarga',
        'carpeta_procesadas',
        'carpeta_errores',
        'email_remitente',
        'nombre_remitente',
        'plantilla_acuse',
        'procesamiento_automatico',
        'frecuencia_minutos',
        'hora_inicio',
        'hora_fin',
        'facturas_procesadas',
        'acuses_enviados',
        'ultimo_procesamiento',
        'activo',
        'configuracion_adicional'
    ];

    protected $casts = [
        'ssl_enabled' => 'boolean',
        'procesamiento_automatico' => 'boolean',
        'activo' => 'boolean',
        'ultimo_procesamiento' => 'datetime',
        'configuracion_adicional' => 'array'
    ];

    /**
     * Relación con la empresa
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Relación con las facturas procesadas
     */
    public function facturasProcesadas(): HasMany
    {
        return $this->hasMany(FacturaDianProcesada::class, 'empresa_id', 'empresa_id');
    }

    /**
     * Obtener configuración activa para una empresa
     */
    public static function getConfiguracionActiva($empresaId)
    {
        return static::where('empresa_id', $empresaId)
                    ->where('activo', true)
                    ->first();
    }

    /**
     * Verificar si el procesamiento está en horario permitido
     */
    public function enHorarioPermitido(): bool
    {
        if (!$this->procesamiento_automatico) {
            return false;
        }

        $horaActual = now()->format('H:i:s');
        return $horaActual >= $this->hora_inicio && $horaActual <= $this->hora_fin;
    }

    /**
     * Incrementar contador de facturas procesadas
     */
    public function incrementarFacturasProcesadas(): void
    {
        $this->increment('facturas_procesadas');
        $this->update(['ultimo_procesamiento' => now()]);
    }

    /**
     * Incrementar contador de acuses enviados
     */
    public function incrementarAcusesEnviados(): void
    {
        $this->increment('acuses_enviados');
    }

    /**
     * Obtener plantilla de acuse por defecto
     */
    public function getPlantillaAcuseAttribute($value)
    {
        return $value ?: $this->getPlantillaAcusePorDefecto();
    }

    /**
     * Plantilla de acuse por defecto
     */
    private function getPlantillaAcusePorDefecto(): string
    {
        return "Estimado proveedor,

Confirmamos la recepción de su factura electrónica con los siguientes datos:

CUFE: {cufe}
Número de Factura: {numero_factura}
NIT Emisor: {nit_emisor}
Valor Total: \${valor_total}
Fecha de Factura: {fecha_factura}

La factura ha sido recibida y procesada correctamente en nuestro sistema.

Atentamente,
{nombre_empresa}
NIT: {nit_empresa}

---
Este es un mensaje automático generado por nuestro sistema de procesamiento de facturas electrónicas.";
    }
}
