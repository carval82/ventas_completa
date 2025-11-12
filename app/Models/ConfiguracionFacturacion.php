<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionFacturacion extends Model
{
    use HasFactory;

    protected $table = 'configuracion_facturacion';

    protected $fillable = [
        'proveedor',
        'configuracion',
        'activo',
        'configurado',
        'ultima_prueba',
        'resultado_prueba'
    ];

    protected $casts = [
        'configuracion' => 'array',
        'activo' => 'boolean',
        'configurado' => 'boolean',
        'ultima_prueba' => 'datetime'
    ];

    /**
     * Obtener configuración activa
     */
    public static function getProveedorActivo()
    {
        return self::where('activo', true)->first();
    }

    /**
     * Activar un proveedor
     */
    public static function activarProveedor($proveedor)
    {
        // Desactivar todos
        self::query()->update(['activo' => false]);
        
        // Activar el seleccionado
        return self::updateOrCreate(
            ['proveedor' => $proveedor],
            ['activo' => true]
        );
    }

    /**
     * Guardar configuración de proveedor
     */
    public static function guardarConfiguracion($proveedor, $configuracion)
    {
        return self::updateOrCreate(
            ['proveedor' => $proveedor],
            [
                'configuracion' => $configuracion,
                'configurado' => true
            ]
        );
    }
}
