<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreditoPermissionSeeder extends Seeder
{
    public function run()
    {
        // Crear permisos para créditos
        $permissions = [
            'ver creditos',
            'crear creditos',
            'editar creditos',
            'eliminar creditos',
            'registrar pagos',
            'ver reportes creditos'
        ];

        // Crear los permisos
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Obtener el rol admin existente y asignarle los permisos
        if ($adminRole = Role::where('name', 'admin')->first()) {
            $adminRole->givePermissionTo($permissions);
        }

        // Crear o actualizar el rol de gestor de créditos
        $rolCreditos = Role::firstOrCreate(['name' => 'gestor creditos']);
        $rolCreditos->syncPermissions([
            'ver creditos',
            'registrar pagos',
            'ver reportes creditos'
        ]);
    }
} 