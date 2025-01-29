<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reiniciar cachés de roles y permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos por módulo
        $permisos = [
            // Módulo de Usuarios y Configuración
            'ver usuarios',
            'crear usuarios',
            'editar usuarios',
            'eliminar usuarios',
            'ver configuracion',
            'editar configuracion',

            // Módulo de Ventas
            'ver ventas',
            'crear ventas',
            'anular ventas',
            'ver reportes ventas',
            'imprimir facturas',

            // Módulo de Créditos
            'ver creditos',
            'crear creditos',
            'editar creditos',
            'eliminar creditos',
            'registrar pagos',
            'ver reportes creditos',

            // Módulo de Inventario
            'ver productos',
            'crear productos',
            'editar productos',
            'eliminar productos',
            'ver stock',
            'ajustar stock',
            'ver movimientos',

            // Módulo de Compras
            'ver compras',
            'crear compras',
            'editar compras',
            'anular compras',
            'ver proveedores',

            // Módulo de Contabilidad
            'ver contabilidad',
            'crear asientos',
            'editar asientos',
            'ver reportes contables',
            'cerrar periodos'
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        // Crear roles y asignar permisos
        
        // Super Usuario - acceso total
        $roleSuperAdmin = Role::firstOrCreate(['name' => 'super admin']);
        $roleSuperAdmin->givePermissionTo(Permission::all());

        // Administrador - acceso amplio pero no total
        $roleAdmin = Role::firstOrCreate(['name' => 'admin']);
        $roleAdmin->givePermissionTo([
            'ver usuarios', 'crear usuarios', 'editar usuarios',
            'ver configuracion', 'editar configuracion',
            'ver ventas', 'crear ventas', 'anular ventas', 'ver reportes ventas',
            'ver creditos', 'crear creditos', 'registrar pagos',
            'ver productos', 'crear productos', 'editar productos',
            'ver stock', 'ajustar stock', 'ver movimientos',
            'ver compras', 'crear compras', 'ver proveedores',
            'ver contabilidad', 'ver reportes contables'
        ]);

        // Secretaria - gestión básica y atención
        $roleSecretaria = Role::firstOrCreate(['name' => 'secretaria']);
        $roleSecretaria->givePermissionTo([
            'ver ventas', 'crear ventas',
            'ver creditos', 'registrar pagos',
            'ver productos', 'ver stock',
            'ver reportes ventas',
            'imprimir facturas'
        ]);

        // Contador - gestión financiera y contable
        $roleContador = Role::firstOrCreate(['name' => 'contador']);
        $roleContador->givePermissionTo([
            'ver contabilidad',
            'crear asientos',
            'editar asientos',
            'ver reportes contables',
            'cerrar periodos',
            'ver ventas',
            'ver compras',
            'ver creditos',
            'ver reportes ventas',
            'ver reportes creditos'
        ]);

        // Técnico - gestión de inventario y productos
        $roleTecnico = Role::firstOrCreate(['name' => 'tecnico']);
        $roleTecnico->givePermissionTo([
            'ver productos',
            'editar productos',
            'ver stock',
            'ajustar stock',
            'ver movimientos'
        ]);

        // Vendedor - ventas y atención al cliente
        $roleVendedor = Role::firstOrCreate(['name' => 'vendedor']);
        $roleVendedor->givePermissionTo([
            'ver ventas',
            'crear ventas',
            'ver productos',
            'ver stock',
            'ver creditos',
            'registrar pagos',
            'imprimir facturas'
        ]);
    }
}
