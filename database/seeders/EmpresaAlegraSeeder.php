<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Empresa;

class EmpresaAlegraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar si ya existe una empresa
        $empresa = Empresa::first();
        
        if (!$empresa) {
            // Si no existe, crear una empresa predeterminada
            $empresa = Empresa::create([
                'nombre_comercial' => 'Mi Empresa',
                'razon_social' => 'Mi Empresa S.A.S.',
                'nit' => '900000000-1',
                'direccion' => 'Calle Principal #123',
                'telefono' => '3001234567',
                'email' => 'contacto@miempresa.com',
                'sitio_web' => 'www.miempresa.com',
                'regimen_tributario' => 'RST', // Cambiado a una abreviatura más corta
                'factura_electronica_habilitada' => true,
            ]);
            
            $this->command->info('Empresa predeterminada creada');
        }
        
        // No configuramos credenciales de Alegra, eso lo hará el usuario desde la interfaz
        
        $this->command->info('Configuración básica de empresa completada. Configure las credenciales de Alegra desde la interfaz de administración.');
    }
}
