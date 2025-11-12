<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SetupTenantMigrations extends Command
{
    protected $signature = 'tenant:setup-migrations';
    protected $description = 'Copia las migraciones principales al directorio tenant';

    public function handle()
    {
        $this->info('🔄 Configurando migraciones para tenants...');

        $migrationesOriginales = database_path('migrations');
        $migracionesTenant = database_path('migrations/tenant');

        // Crear directorio si no existe
        if (!File::exists($migracionesTenant)) {
            File::makeDirectory($migracionesTenant, 0755, true);
        }

        // Migraciones que deben copiarse a cada tenant
        $migrationesACopiar = [
            // Core del sistema
            '2014_10_12_000000_create_users_table.php',
            '2014_10_12_100000_create_password_reset_tokens_table.php',
            '2019_08_19_000000_create_failed_jobs_table.php',
            '2019_12_14_000001_create_personal_access_tokens_table.php',
            
            // Tablas específicas del negocio
            '2023_04_08_000000_create_empresas_table.php',
            '2023_04_08_000001_create_clientes_table.php',
            '2023_04_08_000002_create_productos_table.php',
            '2023_04_08_000003_create_ventas_table.php',
            '2023_04_08_000004_create_venta_productos_table.php',
            '2023_04_08_000005_create_ubicaciones_table.php',
            '2023_04_08_000006_create_stock_ubicaciones_table.php',
            
            // Equivalencias y conversiones
            '2025_09_11_200521_add_peso_bulto_to_productos_table.php',
            '2025_09_20_211251_add_producto_equivalente_fields_to_productos_table.php',
            
            // Agregar más según necesites
        ];

        $copiadas = 0;
        $errores = 0;

        foreach ($migrationesACopiar as $migracion) {
            $origen = $migrationesOriginales . '/' . $migracion;
            $destino = $migracionesTenant . '/' . $migracion;

            if (File::exists($origen)) {
                try {
                    File::copy($origen, $destino);
                    $this->line("✅ Copiada: {$migracion}");
                    $copiadas++;
                } catch (\Exception $e) {
                    $this->error("❌ Error copiando {$migracion}: " . $e->getMessage());
                    $errores++;
                }
            } else {
                $this->warn("⚠️  No encontrada: {$migracion}");
            }
        }

        // Crear migración específica para tenant
        $this->crearMigracionTenantEspecifica();

        $this->info("\n📊 Resumen:");
        $this->info("✅ Migraciones copiadas: {$copiadas}");
        if ($errores > 0) {
            $this->error("❌ Errores: {$errores}");
        }

        $this->info("\n🎉 Setup de migraciones completado!");
        $this->info("Las migraciones están listas en: database/migrations/tenant/");
    }

    private function crearMigracionTenantEspecifica()
    {
        $contenido = '<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Configuraciones específicas para tenant
     */
    public function up(): void
    {
        // Agregar configuraciones específicas del tenant si es necesario
        // Por ejemplo, configuraciones de la empresa, etc.
        
        Schema::table(\'empresas\', function (Blueprint $table) {
            $table->json(\'configuracion_tenant\')->nullable()->after(\'regimen_tributario\');
            $table->string(\'tenant_slug\')->nullable()->after(\'configuracion_tenant\');
        });
    }

    public function down(): void
    {
        Schema::table(\'empresas\', function (Blueprint $table) {
            $table->dropColumn([\'configuracion_tenant\', \'tenant_slug\']);
        });
    }
};';

        $archivo = database_path('migrations/tenant/2025_01_01_000001_tenant_specific_configurations.php');
        File::put($archivo, $contenido);
        $this->line("✅ Creada migración específica de tenant");
    }
}
