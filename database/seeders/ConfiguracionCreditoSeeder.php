<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PlanCuenta;
use App\Models\ConfiguracionContable;
use Illuminate\Support\Facades\Log;

class ConfiguracionCreditoSeeder extends Seeder
{
    public function run()
    {
        // Solo las cuentas nuevas necesarias para crédito
        $cuentas = [
            ['1102', 'CUENTAS POR COBRAR', 'Activo'],
            ['2408', 'IVA POR PAGAR', 'Pasivo']
        ];

        foreach ($cuentas as $cuenta) {
            // Verificar si la cuenta ya existe
            if (!PlanCuenta::where('codigo', $cuenta[0])->exists()) {
                PlanCuenta::create([
                    'codigo' => $cuenta[0],
                    'nombre' => $cuenta[1],
                    'tipo' => $cuenta[2],
                    'nivel' => strlen($cuenta[0])/2,
                    'estado' => true
                ]);
                Log::info("Cuenta creada: {$cuenta[1]}");
            }
        }

        // Solo las configuraciones nuevas necesarias para crédito
        $configuraciones = [
            ['cuentas_por_cobrar', '1102', 'debito'],
            ['iva_ventas', '2408', 'credito']
        ];

        foreach ($configuraciones as $config) {
            // Verificar si la configuración ya existe
            if (!ConfiguracionContable::where('concepto', $config[0])->exists()) {
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
                Log::info("Configuración creada: {$config[0]}");
            }
        }
    }
} 