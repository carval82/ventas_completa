<?php

namespace App\Services;

use App\Contracts\FacturacionElectronicaInterface;
use App\Services\Modules\AlegraModule;
use App\Services\Modules\DianModule;
use App\Services\Modules\SiigoModule;
use App\Services\Modules\WorldOfficeModule;
use Illuminate\Support\Facades\Config;

class FacturacionElectronicaFactory
{
    /**
     * Crear instancia del proveedor de facturación electrónica
     *
     * @param string|null $proveedor
     * @return FacturacionElectronicaInterface
     * @throws \Exception
     */
    public static function create(?string $proveedor = null): FacturacionElectronicaInterface
    {
        $proveedor = $proveedor ?? config('facturacion.proveedor_activo', 'alegra');

        return match (strtolower($proveedor)) {
            'alegra' => new AlegraModule(),
            'dian' => new DianModule(),
            'siigo' => new SiigoModule(),
            'worldoffice' => new WorldOfficeModule(),
            default => throw new \Exception("Proveedor de facturación no soportado: {$proveedor}")
        };
    }

    /**
     * Obtener lista de proveedores disponibles
     *
     * @return array
     */
    public static function getProveedoresDisponibles(): array
    {
        return [
            'alegra' => [
                'nombre' => 'Alegra',
                'descripcion' => 'Integración con Alegra para facturación electrónica',
                'activo' => true
            ],
            'dian' => [
                'nombre' => 'DIAN Directo',
                'descripcion' => 'Integración directa con DIAN',
                'activo' => true
            ],
            'siigo' => [
                'nombre' => 'Siigo',
                'descripcion' => 'Integración con Siigo para facturación electrónica',
                'activo' => false // Por implementar
            ],
            'worldoffice' => [
                'nombre' => 'World Office',
                'descripcion' => 'Integración con World Office',
                'activo' => false // Por implementar
            ]
        ];
    }

    /**
     * Verificar si un proveedor está disponible
     *
     * @param string $proveedor
     * @return bool
     */
    public static function isProveedorDisponible(string $proveedor): bool
    {
        $proveedores = self::getProveedoresDisponibles();
        return isset($proveedores[strtolower($proveedor)]) && $proveedores[strtolower($proveedor)]['activo'];
    }
}
