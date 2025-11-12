<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Empresa;

class EmpresaDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear empresa demo si no existe
        if (Empresa::count() == 0) {
            $empresa = Empresa::create([
                'nombre_comercial' => 'Empresa Demo',
                'razon_social' => 'Empresa Demo S.A.S.',
                'nit' => '123456789-0',
                'direccion' => 'Calle 123 #45-67',
                'telefono' => '(555) 123-4567',
                'email' => 'contacto@empresademo.com',
                'ciudad' => 'Bogotá',
                'departamento' => 'Cundinamarca',
                'pais' => 'Colombia',
                'regimen_tributario' => 'responsable_iva',
                'porcentaje_iva' => 19.00,
                'resolucion_facturacion' => 'Resolución DIAN 000123 del 2024',
                'prefijo_factura' => 'FV',
                'numero_inicial' => 1,
                'numero_final' => 10000,
                'fecha_vencimiento_resolucion' => '2025-12-31',
                'alegra_multiples_impuestos' => false, // Por defecto NO
            ]);

            $this->command->info("Empresa demo creada: {$empresa->nombre_comercial}");
        } else {
            $this->command->info("Empresa ya existe, omitiendo creación");
        }
    }
}
