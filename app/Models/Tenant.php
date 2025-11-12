<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;

class Tenant extends Model
{
    protected $fillable = [
        'slug',
        'nombre',
        'nit',
        'email',
        'telefono',
        'direccion',
        'database_name',
        'database_host',
        'database_port',
        'database_username',
        'database_password',
        'activo',
        'configuracion',
        'fecha_creacion',
        'fecha_expiracion',
        'plan',
        'limite_usuarios',
        'limite_productos',
        'limite_ventas_mes'
    ];

    protected $casts = [
        'configuracion' => 'array',
        'fecha_creacion' => 'datetime',
        'fecha_expiracion' => 'datetime',
        'activo' => 'boolean'
    ];

    /**
     * Configurar conexión a la base de datos del tenant
     */
    public function configurarConexion(): void
    {
        Config::set('database.connections.tenant', [
            'driver' => 'mysql',
            'host' => $this->database_host,
            'port' => $this->database_port,
            'database' => $this->database_name,
            'username' => $this->database_username,
            'password' => $this->database_password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);

        // Purgar conexión existente y reconectar
        DB::purge('tenant');
        DB::reconnect('tenant');
    }

    /**
     * Crear base de datos para el tenant
     */
    public function crearBaseDatos(): bool
    {
        try {
            // Crear base de datos
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$this->database_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Configurar conexión
            $this->configurarConexion();
            
            // Ejecutar migraciones en la base de datos del tenant
            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true
            ]);

            // Ejecutar seeders básicos
            $this->ejecutarSeedersBasicos();

            return true;
        } catch (\Exception $e) {
            \Log::error("Error creando base de datos para tenant {$this->slug}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ejecutar seeders básicos para el tenant
     */
    private function ejecutarSeedersBasicos(): void
    {
        DB::connection('tenant')->table('empresas')->insert([
            'nombre' => $this->nombre,
            'nit' => $this->nit,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'direccion' => $this->direccion,
            'regimen_tributario' => 'responsable_iva',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Crear usuario administrador por defecto
        DB::connection('tenant')->table('users')->insert([
            'name' => 'Administrador',
            'email' => $this->email,
            'password' => bcrypt('admin123'), // Cambiar en producción
            'role' => 'admin',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Eliminar tenant y su base de datos
     */
    public function eliminar(): bool
    {
        try {
            // Eliminar base de datos
            DB::statement("DROP DATABASE IF EXISTS `{$this->database_name}`");
            
            // Eliminar registro del tenant
            $this->delete();

            return true;
        } catch (\Exception $e) {
            \Log::error("Error eliminando tenant {$this->slug}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si el tenant está activo y dentro de límites
     */
    public function puedeOperar(): bool
    {
        if (!$this->activo) {
            return false;
        }

        if ($this->fecha_expiracion && $this->fecha_expiracion->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Obtener estadísticas del tenant
     */
    public function getEstadisticas(): array
    {
        if (!$this->puedeOperar()) {
            return [];
        }

        $this->configurarConexion();

        return [
            'usuarios' => DB::connection('tenant')->table('users')->count(),
            'productos' => DB::connection('tenant')->table('productos')->count(),
            'ventas_mes' => DB::connection('tenant')->table('ventas')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'ventas_total' => DB::connection('tenant')->table('ventas')->sum('total'),
        ];
    }
}
