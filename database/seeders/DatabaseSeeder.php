<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            ContabilidadSeeder::class,
            ConfiguracionCreditoSeeder::class,
            AdminUserSeeder::class, // Usuario administrador
            RestaurarDatosSeeder::class, // Restauración automática de datos
            // EmpresaAlegraSeeder::class, // Comentado para evitar errores
        ]);
    }
}
