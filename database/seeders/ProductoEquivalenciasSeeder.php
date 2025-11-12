<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductoEquivalenciasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ejemplos de equivalencias para diferentes tipos de productos
        
        // Ejemplo 1: Paca de Arroz (ID producto 1 - ajustar según tu BD)
        $equivalenciasPacaArroz = [
            // 1 paca = 25 libras
            ['producto_id' => 1, 'unidad_origen' => 'paca', 'unidad_destino' => 'lb', 'factor_conversion' => 25.0000, 'descripcion' => '1 paca contiene 25 libras'],
            ['producto_id' => 1, 'unidad_origen' => 'lb', 'unidad_destino' => 'paca', 'factor_conversion' => 0.0400, 'descripcion' => '1 libra = 0.04 pacas'],
            
            // 1 paca = 12.5 kilos
            ['producto_id' => 1, 'unidad_origen' => 'paca', 'unidad_destino' => 'kg', 'factor_conversion' => 12.5000, 'descripcion' => '1 paca contiene 12.5 kilos'],
            ['producto_id' => 1, 'unidad_origen' => 'kg', 'unidad_destino' => 'paca', 'factor_conversion' => 0.0800, 'descripcion' => '1 kilo = 0.08 pacas'],
            
            // 1 paca = 1 unidad
            ['producto_id' => 1, 'unidad_origen' => 'paca', 'unidad_destino' => 'unidad', 'factor_conversion' => 1.0000, 'descripcion' => '1 paca = 1 unidad'],
            ['producto_id' => 1, 'unidad_origen' => 'unidad', 'unidad_destino' => 'paca', 'factor_conversion' => 1.0000, 'descripcion' => '1 unidad = 1 paca'],
            
            // Conversiones directas entre kg y lb
            ['producto_id' => 1, 'unidad_origen' => 'kg', 'unidad_destino' => 'lb', 'factor_conversion' => 2.2046, 'descripcion' => '1 kilo = 2.2046 libras'],
            ['producto_id' => 1, 'unidad_origen' => 'lb', 'unidad_destino' => 'kg', 'factor_conversion' => 0.4536, 'descripcion' => '1 libra = 0.4536 kilos'],
        ];
        
        // Ejemplo 2: Bulto de Producto (ID producto 2 - ajustar según tu BD)
        $equivalenciasBulto = [
            // 1 bulto = 40 kilos
            ['producto_id' => 2, 'unidad_origen' => 'bulto', 'unidad_destino' => 'kg', 'factor_conversion' => 40.0000, 'descripcion' => '1 bulto contiene 40 kilos'],
            ['producto_id' => 2, 'unidad_origen' => 'kg', 'unidad_destino' => 'bulto', 'factor_conversion' => 0.0250, 'descripcion' => '1 kilo = 0.025 bultos'],
            
            // 1 bulto = 80 libras (40 kg * 2.2046)
            ['producto_id' => 2, 'unidad_origen' => 'bulto', 'unidad_destino' => 'lb', 'factor_conversion' => 88.1840, 'descripcion' => '1 bulto contiene 88.18 libras'],
            ['producto_id' => 2, 'unidad_origen' => 'lb', 'unidad_destino' => 'bulto', 'factor_conversion' => 0.0113, 'descripcion' => '1 libra = 0.0113 bultos'],
            
            // 1 bulto = 1 unidad
            ['producto_id' => 2, 'unidad_origen' => 'bulto', 'unidad_destino' => 'unidad', 'factor_conversion' => 1.0000, 'descripcion' => '1 bulto = 1 unidad'],
            ['producto_id' => 2, 'unidad_origen' => 'unidad', 'unidad_destino' => 'bulto', 'factor_conversion' => 1.0000, 'descripcion' => '1 unidad = 1 bulto'],
            
            // Conversiones directas entre kg y lb
            ['producto_id' => 2, 'unidad_origen' => 'kg', 'unidad_destino' => 'lb', 'factor_conversion' => 2.2046, 'descripción' => '1 kilo = 2.2046 libras'],
            ['producto_id' => 2, 'unidad_origen' => 'lb', 'unidad_destino' => 'kg', 'factor_conversion' => 0.4536, 'descripcion' => '1 libra = 0.4536 kilos'],
        ];
        
        // Ejemplo 3: Producto líquido en litros
        $equivalenciasLiquido = [
            // 1 galon = 3.785 litros
            ['producto_id' => 3, 'unidad_origen' => 'galon', 'unidad_destino' => 'l', 'factor_conversion' => 3.7854, 'descripcion' => '1 galón contiene 3.785 litros'],
            ['producto_id' => 3, 'unidad_origen' => 'l', 'unidad_destino' => 'galon', 'factor_conversion' => 0.2642, 'descripcion' => '1 litro = 0.264 galones'],
            
            // 1 galon = 1 unidad
            ['producto_id' => 3, 'unidad_origen' => 'galon', 'unidad_destino' => 'unidad', 'factor_conversion' => 1.0000, 'descripcion' => '1 galón = 1 unidad'],
            ['producto_id' => 3, 'unidad_origen' => 'unidad', 'unidad_destino' => 'galon', 'factor_conversion' => 1.0000, 'descripcion' => '1 unidad = 1 galón'],
            
            // Conversiones con mililitros
            ['producto_id' => 3, 'unidad_origen' => 'l', 'unidad_destino' => 'ml', 'factor_conversion' => 1000.0000, 'descripcion' => '1 litro = 1000 mililitros'],
            ['producto_id' => 3, 'unidad_origen' => 'ml', 'unidad_destino' => 'l', 'factor_conversion' => 0.0010, 'descripcion' => '1 mililitro = 0.001 litros'],
        ];
        
        // Insertar todas las equivalencias
        $todasEquivalencias = array_merge($equivalenciasPacaArroz, $equivalenciasBulto, $equivalenciasLiquido);
        
        foreach ($todasEquivalencias as $equivalencia) {
            $equivalencia['created_at'] = now();
            $equivalencia['updated_at'] = now();
            $equivalencia['activo'] = true;
        }
        
        DB::table('producto_equivalencias')->insert($todasEquivalencias);
        
        $this->command->info('Equivalencias de productos creadas exitosamente!');
        $this->command->info('- Paca de Arroz: 1 paca = 25 lb = 12.5 kg');
        $this->command->info('- Bulto: 1 bulto = 40 kg = 88.18 lb');
        $this->command->info('- Líquido: 1 galón = 3.785 l = 3785 ml');
    }
}
