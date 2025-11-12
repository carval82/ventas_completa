<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacturaDianProcesada extends Model
{
    protected $table = 'facturas_dian_procesadas';

    protected $fillable = [
        'empresa_id',
        'mensaje_id',
        'asunto_email',
        'remitente_email',
        'remitente_nombre',
        'fecha_email',
        'cufe',
        'numero_factura',
        'nit_emisor',
        'nombre_emisor',
        'valor_total',
        'fecha_factura',
        'archivos_adjuntos',
        'archivos_extraidos',
        'ruta_xml',
        'ruta_pdf',
        'archivo_original',
        'archivo_xml',
        'estado',
        'detalles_procesamiento',
        'errores',
        'error_mensaje',
        'observaciones',
        'acuse_enviado',
        'fecha_acuse',
        'id_acuse',
        'contenido_acuse',
        'intentos_procesamiento',
        'ultimo_intento',
        'metadatos_adicionales'
    ];

    protected $casts = [
        'fecha_email' => 'datetime',
        'fecha_factura' => 'date',
        'fecha_acuse' => 'datetime',
        'ultimo_intento' => 'datetime',
        'archivos_adjuntos' => 'array',
        'archivos_extraidos' => 'array',
        'metadatos_adicionales' => 'array',
        'acuse_enviado' => 'boolean',
        'valor_total' => 'decimal:2'
    ];

    /**
     * Relación con la empresa
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Scopes para filtrar por estado
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeProcesadas($query)
    {
        return $query->where('estado', 'procesada');
    }

    public function scopeConErrores($query)
    {
        return $query->where('estado', 'error');
    }

    public function scopeSinAcuse($query)
    {
        return $query->where('acuse_enviado', false)
                    ->where('estado', 'procesada');
    }

    /**
     * Marcar como procesada
     */
    public function marcarComoProcesada(array $datosFactura = []): void
    {
        $this->update(array_merge([
            'estado' => 'procesada',
            'ultimo_intento' => now()
        ], $datosFactura));
    }

    /**
     * Marcar como error
     */
    public function marcarComoError(string $error): void
    {
        $this->update([
            'estado' => 'error',
            'errores' => $error,
            'ultimo_intento' => now()
        ]);
        
        $this->increment('intentos_procesamiento');
    }

    /**
     * Marcar acuse como enviado
     */
    public function marcarAcuseEnviado(string $idAcuse, string $contenido): void
    {
        $this->update([
            'acuse_enviado' => true,
            'fecha_acuse' => now(),
            'id_acuse' => $idAcuse,
            'contenido_acuse' => $contenido,
            'estado' => 'acuse_enviado'
        ]);
    }

    /**
     * Verificar si puede reintentarse el procesamiento
     */
    public function puedeReintentar(): bool
    {
        return $this->intentos_procesamiento < 3 && 
               $this->estado === 'error';
    }

    /**
     * Obtener información resumida para logs
     */
    public function getResumenAttribute(): string
    {
        return "CUFE: {$this->cufe} | Emisor: {$this->nombre_emisor} | Valor: $" . number_format($this->valor_total, 2);
    }

    /**
     * Verificar si tiene archivos XML
     */
    public function tieneXml(): bool
    {
        return !empty($this->ruta_xml) && file_exists(storage_path('app/' . $this->ruta_xml));
    }

    /**
     * Obtener ruta completa del XML
     */
    public function getRutaCompletaXml(): ?string
    {
        return $this->ruta_xml ? storage_path('app/' . $this->ruta_xml) : null;
    }
}
