<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Limpiar caché de roles y permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos por módulo
        $modulePermissions = [
            // Productos
            'productos' => ['ver', 'crear', 'editar', 'eliminar'],
            
            // Ventas
            'ventas' => ['ver', 'crear', 'anular', 'reportes'],
            
            // Compras
            'compras' => ['ver', 'crear', 'anular', 'reportes'],
            
            // Movimientos
            'movimientos' => ['ver', 'crear', 'procesar', 'anular'],
            
            // Stock
            'stock' => ['ver', 'ajustar', 'alertas'],
            
            // Usuarios y Roles
            'usuarios' => ['gestionar'],
            'roles' => ['gestionar'],
            
            // Configuración
            'configuracion' => ['ver', 'editar']
        ];

        // Crear todos los permisos
        foreach ($modulePermissions as $module => $actions) {
            foreach ($actions as $action) {
                Permission::create(['name' => $action . ' ' . $module]);
            }
        }

        // Crear roles con sus permisos
        $roles = [
            'Administrador' => Permission::all(), // Todos los permisos
            
            'Secretaria' => [
                'ver productos',
                'ver ventas', 'crear ventas',
                'ver compras',
                'ver stock', 'alertas stock',
            ],
            
            'Contador' => [
                'ver ventas', 'reportes ventas',
                'ver compras', 'reportes compras',
                'ver stock',
            ],
            
            'Técnico' => [
                'ver productos',
                'ver stock', 'ajustar stock',
                'ver movimientos', 'crear movimientos', 'procesar movimientos',
            ],
            
            'Vendedor' => [
                'ver productos',
                'ver ventas', 'crear ventas',
                'ver stock', 'alertas stock',
            ]
        ];

        foreach ($roles as $roleName => $permissions) {
            $role = Role::create(['name' => $roleName]);
            $role->givePermissionTo($permissions);
        }

        // Asignar rol de Administrador al primer usuario (ajusta según necesites)
        $user = \App\Models\User::first();
        if ($user) {
            $user->assignRole('Administrador');
        }
    }
}
