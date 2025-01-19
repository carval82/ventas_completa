<?php

namespace Database\Seeders;

use App\Models\PlanCuenta;
use Illuminate\Database\Seeder;

class PlanCuentasSeeder extends Seeder
{
    public function run()
    {
        $cuentas = [
            [
                'codigo' => '1435',
                'nombre' => 'Inventario de Mercancías',
                'tipo' => 'Activo',
                'nivel' => 1,
                'estado' => true
            ],
            [
                'codigo' => '1355',
                'nombre' => 'IVA en Compras',
                'tipo' => 'Activo',
                'nivel' => 1,
                'estado' => true
            ],
            [
                'codigo' => '2205',
                'nombre' => 'Proveedores por Pagar',
                'tipo' => 'Pasivo',
                'nivel' => 1,
                'estado' => true
            ]
        ];

        foreach ($cuentas as $cuenta) {
            PlanCuenta::create($cuenta);
        }
    }
} 