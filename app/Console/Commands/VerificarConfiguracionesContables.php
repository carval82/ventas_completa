<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ConfiguracionContable;
use App\Models\PlanCuenta;

class VerificarConfiguracionesContables extends Command
{
    protected $signature = 'contabilidad:verificar-configuraciones';
    protected $description = 'Verifica que existan todas las configuraciones contables necesarias';

    public function handle()
    {
        $configuraciones = [
            'caja' => '110101',
            'ventas' => '4101',
            'costo_ventas' => '5101',
            'proveedores' => '2101',
            'cuentas_por_cobrar' => '1102',
            'iva_ventas' => '2408'
        ];

        $this->info('Verificando configuraciones contables...');

        foreach ($configuraciones as $concepto => $codigo) {
            $cuenta = PlanCuenta::where('codigo', $codigo)->first();
            $config = ConfiguracionContable::where('concepto', $concepto)->first();

            if (!$cuenta) {
                $this->error("❌ No existe la cuenta con código: $codigo para $concepto");
                continue;
            }

            if (!$config) {
                $this->error("❌ No existe la configuración para: $concepto");
                continue;
            }

            if ($config->cuenta_id !== $cuenta->id) {
                $this->error("❌ La configuración de $concepto apunta a una cuenta incorrecta");
                continue;
            }

            $this->info("✅ Configuración de $concepto correcta");
        }
    }
} 