<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailBuzon extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'mensaje_id',
        'cuenta_email',
        'remitente_email',
        'remitente_nombre',
        'asunto',
        'contenido_texto',
        'contenido_html',
        'fecha_email',
        'fecha_descarga',
        'archivos_adjuntos',
        'tiene_facturas',
        'procesado',
        'fecha_procesado',
        'estado',
        'metadatos',
        'observaciones'
    ];

    protected $casts = [
        'fecha_email' => 'datetime',
        'fecha_descarga' => 'datetime',
        'fecha_procesado' => 'datetime',
        'archivos_adjuntos' => 'array',
        'metadatos' => 'array',
        'tiene_facturas' => 'boolean',
        'procesado' => 'boolean'
    ];

    /**
     * Relación con empresa
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Verificar si el email tiene archivos adjuntos
     */
    public function tieneAdjuntos(): bool
    {
        return !empty($this->archivos_adjuntos);
    }

    /**
     * Obtener archivos adjuntos como colección
     */
    public function getAdjuntos(): array
    {
        return $this->archivos_adjuntos ?? [];
    }

    /**
     * Marcar como procesado
     */
    public function marcarProcesado(): void
    {
        $this->update([
            'procesado' => true,
            'fecha_procesado' => now(),
            'estado' => 'procesado'
        ]);
    }

    /**
     * Marcar como error
     */
    public function marcarError(string $observacion = null): void
    {
        $this->update([
            'estado' => 'error',
            'observaciones' => $observacion
        ]);
    }

    /**
     * Scope para emails no procesados
     */
    public function scopeNoProcessados($query)
    {
        return $query->where('procesado', false);
    }

    /**
     * Scope para emails con facturas
     */
    public function scopeConFacturas($query)
    {
        return $query->where('tiene_facturas', true);
    }

    /**
     * Scope por empresa
     */
    public function scopePorEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }
}
