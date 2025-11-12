<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddIvaToProductosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ejecutar consulta SQL directamente para añadir columna IVA
        \DB::statement('ALTER TABLE productos ADD COLUMN IF NOT EXISTS iva DECIMAL(5,2) DEFAULT 0 AFTER precio_venta');
        
        // Actualizar todos los productos existentes con un valor de IVA por defecto (19% para Colombia)
        \DB::table('productos')->update(['iva' => 19]);
        
        \DB::statement('ALTER TABLE productos MODIFY COLUMN iva DECIMAL(5,2) DEFAULT 19 COMMENT "Porcentaje de IVA del producto"');
    }
}
