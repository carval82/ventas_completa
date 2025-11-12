<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class EmailConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'nombre',
        'proveedor',
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'from_address',
        'from_name',
        'api_key',
        'configuracion_adicional',
        'activo',
        'es_backup',
        'es_acuses',
        'es_notificaciones',
        'limite_diario',
        'emails_enviados_hoy',
        'fecha_reset_contador',
        'ultimo_envio',
        'estadisticas'
    ];

    protected $casts = [
        'configuracion_adicional' => 'array',
        'estadisticas' => 'array',
        'activo' => 'boolean',
        'es_backup' => 'boolean',
        'es_acuses' => 'boolean',
        'es_notificaciones' => 'boolean',
        'fecha_reset_contador' => 'date',
        'ultimo_envio' => 'datetime'
    ];

    protected $hidden = [
        'password',
        'api_key'
    ];

    /**
     * Relación con empresa
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Encriptar password al guardar
     */
    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = Crypt::encryptString($value);
        }
    }

    /**
     * Desencriptar password al leer
     */
    public function getPasswordAttribute($value)
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }
    /**
     * Encriptar API key al guardar
     */
    public function setApiKeyAttribute($value)
    {
        // No encriptar automáticamente - almacenar tal como viene
        $this->attributes['api_key'] = $value;
    }

    public function getApiKeyAttribute($value)
    {
        // Devolver tal como está almacenado
        return $value;
    }

    /**
     * Verificar si se puede enviar email (límite diario)
     */
    public function puedeEnviarEmail(): bool
    {
        if (!$this->activo) {
            return false;
        }

        if (!$this->limite_diario) {
            return true;
        }

        // Reset contador si es un nuevo día
        if ($this->fecha_reset_contador != now()->toDateString()) {
            $this->update([
                'emails_enviados_hoy' => 0,
                'fecha_reset_contador' => now()->toDateString()
            ]);
        }

        return $this->emails_enviados_hoy < $this->limite_diario;
    }

    /**
     * Incrementar contador de emails enviados
     */
    public function incrementarContador(): void
    {
        $this->increment('emails_enviados_hoy');
        $this->update(['ultimo_envio' => now()]);
    }

    /**
     * Obtener configurción para backup
     */
    public static function paraBackup($empresaId)
    {
        return static::where('empresa_id', $empresaId)
                    ->where('es_backup', true)
                    ->where('activo', true)
                    ->first();
    }

    /**
     * Obtener configurción para acuses DIAN
     */
    public static function paraAcuses($empresaId)
    {
        return static::where('empresa_id', $empresaId)
                    ->where('es_acuses', true)
                    ->where('activo', true)
                    ->first();
    }

    /**
     * Obtener configurción para notificaciones
     */
    public static function paraNotificaciones($empresaId)
    {
        return static::where('empresa_id', $empresaId)
                    ->where('es_notificaciones', true)
                    ->where('activo', true)
                    ->first();
    }

    /**
     * Obtener configurción como array para Laravel Mail
     */
    public function toMailConfig(): array
    {
        $config = [
            'transport' => 'smtp',
            'host' => $this->host,
            'port' => $this->port,
            'encryption' => $this->encryption,
            'username' => $this->username,
            'password' => $this->password,
            'timeout' => null,
            'local_domain' => null,
        ];

        // Configuraciones específicas por proveedor
        switch ($this->proveedor) {
            case 'sendgrid':
                $config['host'] = 'smtp.sendgrid.net';
                $config['port'] = 587;
                $config['username'] = 'apikey';
                $config['password'] = $this->api_key;
                break;
            
            case 'mailgun':
                $config['host'] = 'smtp.mailgun.org';
                $config['port'] = 587;
                break;
            
            case 'ses':
                $config['host'] = 'email-smtp.us-east-1.amazonaws.com';
                $config['port'] = 587;
                break;
        }

        return $config;
    }

    /**
     * Scope para configuraciones activas
     */
    public function scopeActivas($query)
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
