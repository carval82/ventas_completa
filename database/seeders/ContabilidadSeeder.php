<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PlanCuenta;
use App\Models\ConfiguracionContable;
use App\Models\Ubicacion;
use Illuminate\Support\Facades\Log;

class ContabilidadSeeder extends Seeder
{
    public function run()
    {
        // Ubicación predeterminada
        Ubicacion::insert([
            [
                'nombre' => 'Bodega Principal',
                'tipo' => 'bodega',
                'descripcion' => 'Almacén principal de mercancía',
                'estado' => true
            ],
            [
                'nombre' => 'Mostrador',
                'tipo' => 'mostrador', 
                'descripcion' => 'Punto de venta principal',
                'estado' => true
            ],
            [
                'nombre' => 'Bodega Secundaria',
                'tipo' => 'bodega',
                'descripcion' => 'Almacén secundario',
                'estado' => true
            ]
        ]);

        // Cuentas principales (usando estructura NIF/NIIF)
        $cuentas = [
            ['1', 'ACTIVO'],
            ['11', 'ACTIVO CORRIENTE'],
            ['1101', 'EFECTIVO Y EQUIVALENTES'],
            ['110101', 'CAJA'],
            ['1102', 'CUENTAS POR COBRAR'],
            
            ['2', 'PASIVO'],
            ['21', 'PASIVO CORRIENTE'],
            ['2101', 'PROVEEDORES'],
            ['2408', 'IVA POR PAGAR'],
            
            ['4', 'INGRESOS'],
            ['41', 'INGRESOS OPERACIONALES'],
            ['4101', 'VENTAS'],
            
            ['5', 'GASTOS'],
            ['51', 'GASTOS OPERACIONALES'],
            ['5101', 'COSTO DE VENTAS']
        ];

        foreach ($cuentas as $cuenta) {
            PlanCuenta::create([
                'codigo' => $cuenta[0],
                'nombre' => $cuenta[1],
                'nivel' => strlen($cuenta[0])/2,
                'estado' => true
            ]);
        }

        // Configuraciones contables básicas
        $configuraciones = [
            ['caja', '110101', 'debito'],
            ['ventas', '4101', 'credito'],
            ['costo_ventas', '5101', 'debito'],
            ['proveedores', '2101', 'credito'],
            ['cuentas_por_cobrar', '1102', 'debito'],
            ['iva_ventas', '2408', 'credito']
        ];

        foreach ($configuraciones as $config) {
            $cuenta = PlanCuenta::where('codigo', $config[1])->first();
            
            if (!$cuenta) {
                Log::error('Cuenta no encontrada para configuración', [
                    'concepto' => $config[0],
                    'codigo' => $config[1]
                ]);
                continue;
            }

            ConfiguracionContable::create([
                'concepto' => $config[0],
                'cuenta_id' => $cuenta->id,
                'tipo_movimiento' => $config[2]
            ]);
        }
    }
}