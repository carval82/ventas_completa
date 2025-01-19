<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperUserSeeder extends Seeder
{
    public function run()
    {
        // Crear el rol de Super Admin si no existe
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);

        // Verificar si el usuario ya existe
        $superUser = User::firstOrCreate(
            ['email' => 'pcapacho24@gmail.com'],  // Buscar por email
            [
                'name' => 'Luis Carlos Correa Arrieta',
                'password' => Hash::make('Anaval331$'),
                'estado' => true,
            ]
        );

        // Asignar el rol de Super Admin si no lo tiene
        if (!$superUser->hasRole('Super Admin')) {
            $superUser->assignRole('Super Admin');
        }
    }
} 