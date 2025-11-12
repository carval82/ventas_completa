<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ConversionUnidad;

class ConversionUnidadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar tabla existente
        ConversionUnidad::truncate();

        // Conversiones de PESO
        $conversionesPeso = [
            // Kilogramo como base
            ['kg', 'g', 1000, 'peso'],
            ['kg', 'lb', 2.20462, 'peso'],
            ['kg', 'oz', 35.274, 'peso'],
            
            // Gramo
            ['g', 'kg', 0.001, 'peso'],
            ['g', 'lb', 0.00220462, 'peso'],
            ['g', 'oz', 0.035274, 'peso'],
            
            // Libra
            ['lb', 'kg', 0.453592, 'peso'],
            ['lb', 'g', 453.592, 'peso'],
            ['lb', 'oz', 16, 'peso'],
            
            // Onza
            ['oz', 'kg', 0.0283495, 'peso'],
            ['oz', 'g', 28.3495, 'peso'],
            ['oz', 'lb', 0.0625, 'peso'],
        ];

        // Conversiones de VOLUMEN
        $conversionesVolumen = [
            // Litro como base
            ['l', 'ml', 1000, 'volumen'],
            ['l', 'cc', 1000, 'volumen'], // centímetros cúbicos
            ['l', 'gal', 0.264172, 'volumen'], // galones US
            
            // Mililitro
            ['ml', 'l', 0.001, 'volumen'],
            ['ml', 'cc', 1, 'volumen'],
            ['ml', 'gal', 0.000264172, 'volumen'],
            
            // Centímetros cúbicos (para veterinarios)
            ['cc', 'l', 0.001, 'volumen'],
            ['cc', 'ml', 1, 'volumen'],
            ['cc', 'gal', 0.000264172, 'volumen'],
            
            // Galón
            ['gal', 'l', 3.78541, 'volumen'],
            ['gal', 'ml', 3785.41, 'volumen'],
            ['gal', 'cc', 3785.41, 'volumen'],
        ];

        // Conversiones de LONGITUD
        $conversionesLongitud = [
            // Metro como base
            ['m', 'cm', 100, 'longitud'],
            ['m', 'mm', 1000, 'longitud'],
            ['m', 'ft', 3.28084, 'longitud'],
            ['m', 'in', 39.3701, 'longitud'],
            
            // Centímetro
            ['cm', 'm', 0.01, 'longitud'],
            ['cm', 'mm', 10, 'longitud'],
            ['cm', 'ft', 0.0328084, 'longitud'],
            ['cm', 'in', 0.393701, 'longitud'],
            
            // Milímetro
            ['mm', 'm', 0.001, 'longitud'],
            ['mm', 'cm', 0.1, 'longitud'],
            ['mm', 'ft', 0.00328084, 'longitud'],
            ['mm', 'in', 0.0393701, 'longitud'],
        ];

        // Conversiones de CANTIDAD
        $conversionesCantidad = [
            // Unidad como base
            ['unit', 'dozen', 0.0833333, 'cantidad'], // 1 unidad = 1/12 docena
            ['unit', 'box', 1, 'cantidad'], // dependerá del producto específico
            ['unit', 'pack', 1, 'cantidad'], // dependerá del producto específico
            ['unit', 'bulto', 1, 'cantidad'], // dependerá del producto específico
            
            // Docena
            ['dozen', 'unit', 12, 'cantidad'],
            ['dozen', 'box', 12, 'cantidad'], // asumiendo caja = unidad
            ['dozen', 'pack', 12, 'cantidad'], // asumiendo paquete = unidad
            
            // Caja (genérica, se puede personalizar por producto)
            ['box', 'unit', 1, 'cantidad'],
            ['box', 'dozen', 0.0833333, 'cantidad'],
            
            // Paquete (genérico, se puede personalizar por producto)
            ['pack', 'unit', 1, 'cantidad'],
            ['pack', 'dozen', 0.0833333, 'cantidad'],
            
            // Bulto (genérico, se puede personalizar por producto)
            ['bulto', 'unit', 1, 'cantidad'],
            ['bulto', 'kg', 1, 'cantidad'], // se definirá por producto
        ];

        // Insertar todas las conversiones
        $todasLasConversiones = array_merge(
            $conversionesPeso,
            $conversionesVolumen,
            $conversionesLongitud,
            $conversionesCantidad
        );

        foreach ($todasLasConversiones as $conversion) {
            ConversionUnidad::create([
                'unidad_origen' => $conversion[0],
                'unidad_destino' => $conversion[1],
                'factor_conversion' => $conversion[2],
                'categoria' => $conversion[3],
                'activo' => true
            ]);
        }

        $this->command->info('Conversiones de unidades creadas exitosamente.');
        $this->command->info('Total de conversiones: ' . count($todasLasConversiones));
    }
}
