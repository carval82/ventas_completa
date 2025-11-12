<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EmailConfiguration;
use App\Models\Empresa;

class EmailConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todas las empresas
        $empresas = Empresa::all();

        foreach ($empresas as $empresa) {
            // Verificar si ya tiene configuraciones
            $tieneConfiguraciones = EmailConfiguration::where('empresa_id', $empresa->id)->exists();
            
            if (!$tieneConfiguraciones) {
                $this->crearConfiguracionesPorDefecto($empresa);
            }
        }
    }

    /**
     * Crear configuraciones por defecto para una empresa
     */
    private function crearConfiguracionesPorDefecto(Empresa $empresa)
    {
        // Configuración SendGrid por defecto
        EmailConfiguration::create([
            'empresa_id' => $empresa->id,
            'nombre' => 'SendGrid Principal',
            'proveedor' => 'sendgrid',
            'host' => 'smtp.sendgrid.net',
            'port' => 587,
            'username' => 'apikey',
            'password' => null, // Se debe configurar manualmente
            'api_key' => null, // Se debe configurar manualmente
            'encryption' => 'tls',
            'from_address' => 'sistema@' . strtolower(str_replace(' ', '', $empresa->nombre)) . '.com',
            'from_name' => 'Sistema ' . $empresa->nombre,
            'limite_diario' => 100,
            'activo' => false, // Inactiva hasta configurar API Key
            'es_backup' => true,
            'es_acuses' => true,
            'es_notificaciones' => true,
            'fecha_reset_contador' => now()->toDateString(),
            'configuracion_adicional' => [
                'descripcion' => 'Configuración SendGrid para uso general',
                'plan' => 'gratuito',
                'limite_mensual' => 3000
            ]
        ]);

        // Configuración SMTP Gmail como alternativa
        EmailConfiguration::create([
            'empresa_id' => $empresa->id,
            'nombre' => 'Gmail SMTP',
            'proveedor' => 'smtp',
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'username' => null, // Se debe configurar manualmente
            'password' => null, // Se debe configurar manualmente
            'encryption' => 'tls',
            'from_address' => 'sistema@gmail.com',
            'from_name' => 'Sistema ' . $empresa->nombre,
            'limite_diario' => 500,
            'activo' => false, // Inactiva hasta configurar credenciales
            'es_backup' => false,
            'es_acuses' => false,
            'es_notificaciones' => true,
            'fecha_reset_contador' => now()->toDateString(),
            'configuracion_adicional' => [
                'descripcion' => 'Configuración Gmail SMTP como alternativa',
                'requiere_app_password' => true,
                'instrucciones' => 'Usar App Password, no contraseña normal'
            ]
        ]);

        // Configuración de prueba (solo para desarrollo)
        if (app()->environment(['local', 'development'])) {
            EmailConfiguration::create([
                'empresa_id' => $empresa->id,
                'nombre' => 'Mailtrap (Desarrollo)',
                'proveedor' => 'smtp',
                'host' => 'smtp.mailtrap.io',
                'port' => 2525,
                'username' => null, // Se debe configurar manualmente
                'password' => null, // Se debe configurar manualmente
                'encryption' => 'tls',
                'from_address' => 'test@' . strtolower(str_replace(' ', '', $empresa->nombre)) . '.test',
                'from_name' => 'Test ' . $empresa->nombre,
                'limite_diario' => null, // Sin límite para pruebas
                'activo' => false,
                'es_backup' => true,
                'es_acuses' => true,
                'es_notificaciones' => true,
                'fecha_reset_contador' => now()->toDateString(),
                'configuracion_adicional' => [
                    'descripcion' => 'Configuración para pruebas de desarrollo',
                    'entorno' => 'desarrollo',
                    'url_inbox' => 'https://mailtrap.io/inboxes'
                ]
            ]);
        }

        echo "✅ Configuraciones creadas para empresa: {$empresa->nombre}\n";
    }
}
