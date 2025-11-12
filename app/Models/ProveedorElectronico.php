<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProveedorElectronico extends Model
{
    use HasFactory;

    protected $table = 'proveedores_electronicos';

    protected $fillable = [
        'empresa_id',
        'nombre_proveedor',
        'email_proveedor',
        'nit_proveedor',
        'dominios_email',
        'palabras_clave',
        'activo',
        'observaciones'
    ];

    protected $casts = [
        'dominios_email' => 'array',
        'palabras_clave' => 'array',
        'activo' => 'boolean'
    ];

    /**
     * Relación con empresa
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Verificar si un email coincide con este proveedor
     */
    public function coincideConEmail(string $email): bool
    {
        // Verificar email exacto
        if (strtolower($this->email_proveedor) === strtolower($email)) {
            return true;
        }

        // Verificar dominios
        if ($this->dominios_email) {
            $dominio_email = strtolower(substr(strrchr($email, '@'), 1));
            foreach ($this->dominios_email as $dominio) {
                if (strtolower($dominio) === $dominio_email) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Verificar si un asunto coincide con las palabras clave
     */
    public function coincideConAsunto(string $asunto): bool
    {
        if (!$this->palabras_clave) {
            return false;
        }

        $asunto_lower = strtolower($asunto);
        foreach ($this->palabras_clave as $palabra) {
            if (stripos($asunto_lower, strtolower($palabra)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verificar si un remitente coincide con las palabras clave (nombre de empresa)
     */
    public function coincideConRemitente(string $remitente_nombre): bool
    {
        if (!$this->palabras_clave) {
            return false;
        }

        $remitente_lower = strtolower($remitente_nombre);
        foreach ($this->palabras_clave as $palabra) {
            if (stripos($remitente_lower, strtolower($palabra)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Scope para proveedores activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope por empresa
     */
    public function scopePorEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }
}
