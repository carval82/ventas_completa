<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear el rol de administrador si no existe
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        
        // Crear el usuario administrador
        $admin = User::create([
            'name' => 'Luis Carlos Correa Arrieta',
            'email' => 'pcapacho24@gmail.com',
            'password' => Hash::make('anaval33'),
            'estado' => true, // Cambiado de 'activo' a true para que coincida con el tipo de dato boolean
            'email_verified_at' => now(),
        ]);
        
        // Asignar el rol de administrador al usuario
        $admin->assignRole($adminRole);
        
        $this->command->info('Usuario administrador creado: ' . $admin->name);
    }
}
