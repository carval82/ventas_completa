<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Impuesto;

class ImpuestosBasicosSeeder extends Seeder
{
    /**
     * Crear configuración básica de impuestos colombianos
     */
    public function run(): void
    {
        $impuestos = [
            // IVA - Impuesto sobre las Ventas
            [
                'codigo' => 'IVA19',
                'nombre' => 'IVA General 19%',
                'descripcion' => 'Impuesto sobre las Ventas tarifa general del 19%',
                'tipo' => 'iva',
                'porcentaje' => 19.0000,
                'base_minima' => 0,
                'aplica_compras' => true,
                'aplica_ventas' => true,
                'es_retencion' => false,
                'regimenes_aplica' => ['comun', 'gran_contribuyente', 'autorretenedor'],
                'activo' => true
            ],
            [
                'codigo' => 'IVA5',
                'nombre' => 'IVA Reducido 5%',
                'descripcion' => 'Impuesto sobre las Ventas tarifa reducida del 5%',
                'tipo' => 'iva',
                'porcentaje' => 5.0000,
                'base_minima' => 0,
                'aplica_compras' => true,
                'aplica_ventas' => true,
                'es_retencion' => false,
                'regimenes_aplica' => ['comun', 'gran_contribuyente', 'autorretenedor'],
                'activo' => true
            ],
            [
                'codigo' => 'IVA0',
                'nombre' => 'IVA Excluido 0%',
                'descripcion' => 'Productos excluidos de IVA',
                'tipo' => 'iva',
                'porcentaje' => 0.0000,
                'base_minima' => 0,
                'aplica_compras' => true,
                'aplica_ventas' => true,
                'es_retencion' => false,
                'regimenes_aplica' => ['comun', 'gran_contribuyente', 'autorretenedor', 'simplificado'],
                'activo' => true
            ],

            // Retención en la Fuente - Renta
            [
                'codigo' => 'RTE35',
                'nombre' => 'Retención Renta 3.5%',
                'descripcion' => 'Retención en la fuente por compras generales 3.5%',
                'tipo' => 'retencion_renta',
                'porcentaje' => 3.5000,
                'base_minima' => 135000, // Aproximadamente 5 UVT 2024
                'aplica_compras' => true,
                'aplica_ventas' => false,
                'es_retencion' => true,
                'regimenes_aplica' => ['comun', 'gran_contribuyente'],
                'responsabilidades_exentas' => ['O-15'], // Autorretenedores
                'activo' => true
            ],
            [
                'codigo' => 'RTE25',
                'nombre' => 'Retención Renta 2.5%',
                'descripcion' => 'Retención en la fuente por servicios 2.5%',
                'tipo' => 'retencion_renta',
                'porcentaje' => 2.5000,
                'base_minima' => 135000,
                'aplica_compras' => true,
                'aplica_ventas' => false,
                'es_retencion' => true,
                'regimenes_aplica' => ['comun', 'gran_contribuyente'],
                'responsabilidades_exentas' => ['O-15'],
                'activo' => true
            ],
            [
                'codigo' => 'RTE11',
                'nombre' => 'Retención Renta 11%',
                'descripcion' => 'Retención en la fuente por honorarios y servicios profesionales',
                'tipo' => 'retencion_renta',
                'porcentaje' => 11.0000,
                'base_minima' => 135000,
                'aplica_compras' => true,
                'aplica_ventas' => false,
                'es_retencion' => true,
                'regimenes_aplica' => ['comun', 'gran_contribuyente'],
                'responsabilidades_exentas' => ['O-15'],
                'activo' => true
            ],

            // Retención de IVA
            [
                'codigo' => 'RTIVA15',
                'nombre' => 'Retención IVA 15%',
                'descripcion' => 'Retención del 15% sobre el IVA facturado',
                'tipo' => 'retencion_iva',
                'porcentaje' => 15.0000,
                'base_minima' => 0,
                'aplica_compras' => true,
                'aplica_ventas' => false,
                'es_retencion' => true,
                'calcula_sobre_iva' => true,
                'regimenes_aplica' => ['comun', 'gran_contribuyente'],
                'responsabilidades_exentas' => ['O-15'], // Autorretenedores
                'activo' => true
            ],

            // Retención de ICA (ejemplo Bogotá)
            [
                'codigo' => 'RTICA414',
                'nombre' => 'Retención ICA 4.14‰',
                'descripcion' => 'Retención Industria y Comercio Bogotá - Actividades comerciales',
                'tipo' => 'retencion_ica',
                'porcentaje' => 0.4140, // 4.14 por mil
                'base_minima' => 135000,
                'aplica_compras' => true,
                'aplica_ventas' => false,
                'es_retencion' => true,
                'regimenes_aplica' => ['comun', 'gran_contribuyente'],
                'responsabilidades_exentas' => ['O-15'],
                'activo' => true
            ],
            [
                'codigo' => 'RTICA690',
                'nombre' => 'Retención ICA 6.90‰',
                'descripcion' => 'Retención Industria y Comercio Bogotá - Actividades de servicios',
                'tipo' => 'retencion_ica',
                'porcentaje' => 0.6900, // 6.90 por mil
                'base_minima' => 135000,
                'aplica_compras' => true,
                'aplica_ventas' => false,
                'es_retencion' => true,
                'regimenes_aplica' => ['comun', 'gran_contribuyente'],
                'responsabilidades_exentas' => ['O-15'],
                'activo' => true
            ],

            // Impuesto al Consumo (ejemplo)
            [
                'codigo' => 'CONSUMO8',
                'nombre' => 'Impuesto al Consumo 8%',
                'descripcion' => 'Impuesto nacional al consumo para ciertos productos',
                'tipo' => 'impuesto_consumo',
                'porcentaje' => 8.0000,
                'base_minima' => 0,
                'aplica_compras' => true,
                'aplica_ventas' => true,
                'es_retencion' => false,
                'regimenes_aplica' => ['comun', 'gran_contribuyente', 'autorretenedor'],
                'activo' => true
            ]
        ];

        foreach ($impuestos as $impuesto) {
            Impuesto::create($impuesto);
        }

        $this->command->info('✅ Impuestos básicos creados exitosamente');
    }
}
