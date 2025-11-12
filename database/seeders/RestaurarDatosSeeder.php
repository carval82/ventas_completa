<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Empresa;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;

class RestaurarDatosSeeder extends Seeder
{
    /**
     * Ejecutar el seeder de restauración automática
     */
    public function run()
    {
        $this->command->info('🔄 Iniciando restauración automática de datos...');
        
        try {
            // Buscar archivos de backup disponibles
            $backupFiles = $this->buscarArchivosBackup();
            
            if (empty($backupFiles)) {
                $this->command->info('ℹ️ No se encontraron archivos de backup para restaurar');
                return;
            }
            
            $this->command->info('📁 Archivos de backup encontrados: ' . count($backupFiles));
            
            // Usar el backup más reciente
            $backupFile = $backupFiles[0];
            $this->command->info("📖 Restaurando desde: {$backupFile}");
            
            // Restaurar solo los datos, no la estructura
            $this->restaurarSoloDatos($backupFile);
            
            $this->command->info('✅ Restauración automática completada');
            
        } catch (\Exception $e) {
            $this->command->error('❌ Error en restauración automática: ' . $e->getMessage());
            Log::error('Error en RestaurarDatosSeeder', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Buscar archivos de backup disponibles
     */
    private function buscarArchivosBackup()
    {
        $posiblesUbicaciones = [
            storage_path('app/backups'),
            base_path('storage/app/backups'),
            base_path('backups'),
            'C:/Users/Lc Desarrollo/Documents/sistema_ventas_completo_20250410/storage/app/backups'
        ];
        
        $archivosEncontrados = [];
        
        foreach ($posiblesUbicaciones as $ubicacion) {
            if (is_dir($ubicacion)) {
                $archivos = glob($ubicacion . '/*.sql');
                foreach ($archivos as $archivo) {
                    $archivosEncontrados[] = $archivo;
                }
            }
        }
        
        // Ordenar por fecha de modificación (más reciente primero)
        usort($archivosEncontrados, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        return $archivosEncontrados;
    }
    
    /**
     * Restaurar solo los datos, manteniendo la estructura actual
     */
    private function restaurarSoloDatos($backupFile)
    {
        $this->command->info('📖 Leyendo archivo de backup...');
        
        $sqlContent = file_get_contents($backupFile);
        if (!$sqlContent) {
            throw new \Exception('No se pudo leer el archivo de backup');
        }
        
        // Extraer solo los INSERT statements
        $insertStatements = $this->extraerInsertStatements($sqlContent);
        
        $this->command->info("🔄 Procesando " . count($insertStatements) . " statements de datos...");
        
        // Deshabilitar verificaciones temporalmente
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::statement('SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO"');
        
        $exitosos = 0;
        $errores = 0;
        
        foreach ($insertStatements as $statement) {
            try {
                // Solo ejecutar si es un INSERT de datos (no estructura)
                if ($this->esStatementDeDatos($statement)) {
                    DB::statement($statement);
                    $exitosos++;
                }
            } catch (\Exception $e) {
                $errores++;
                // Solo logear errores no críticos
                if (!$this->esErrorNoCritico($e->getMessage())) {
                    Log::warning('Error restaurando statement', [
                        'error' => $e->getMessage(),
                        'statement' => substr($statement, 0, 100) . '...'
                    ]);
                }
            }
        }
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        $this->command->info("✅ Restauración completada: {$exitosos} exitosos, {$errores} errores no críticos");
        
        // Verificar datos restaurados
        $this->verificarDatosRestaurados();
    }
    
    /**
     * Extraer solo los INSERT statements del backup
     */
    private function extraerInsertStatements($sqlContent)
    {
        // Limpiar contenido
        $sqlContent = str_replace(["\r\n", "\r"], "\n", $sqlContent);
        
        // Dividir en statements
        $statements = explode(";\n", $sqlContent);
        
        $insertStatements = [];
        foreach ($statements as $statement) {
            $statement = trim($statement);
            
            // Solo incluir INSERT statements
            if (preg_match('/^INSERT\s+INTO/i', $statement)) {
                $insertStatements[] = $statement;
            }
        }
        
        return $insertStatements;
    }
    
    /**
     * Verificar si el statement es de datos (no estructura)
     */
    private function esStatementDeDatos($statement)
    {
        // Tablas de datos que queremos restaurar
        $tablasDatos = [
            'empresas', 'clientes', 'productos', 'ventas', 
            'detalle_ventas', 'categorias', 'proveedores'
        ];
        
        foreach ($tablasDatos as $tabla) {
            if (preg_match("/INSERT\s+INTO\s+`?{$tabla}`?/i", $statement)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verificar si el error es no crítico
     */
    private function esErrorNoCritico($mensaje)
    {
        $erroresNoCriticos = [
            'Duplicate entry',
            'already exists',
            'Column count doesn\'t match'
        ];
        
        foreach ($erroresNoCriticos as $error) {
            if (strpos($mensaje, $error) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verificar que los datos se restauraron correctamente
     */
    private function verificarDatosRestaurados()
    {
        $tablas = [
            'empresas' => Empresa::count(),
            'clientes' => Cliente::count(),
            'productos' => Producto::count(),
            'ventas' => Venta::count()
        ];
        
        $this->command->info('📊 Datos restaurados:');
        foreach ($tablas as $tabla => $count) {
            $this->command->info("   {$tabla}: {$count} registros");
        }
    }
}
