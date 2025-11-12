<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ConfiguracionDian;
use App\Models\Empresa;
use App\Services\Dian\EmailProcessorService;
use App\Services\Dian\AcuseGeneratorService;
use Illuminate\Support\Facades\Log;

class ProcesarFacturasDian extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dian:procesar-facturas {--empresa-id= : ID específico de empresa} {--force : Forzar procesamiento fuera de horario}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesar automáticamente facturas DIAN y enviar acuses de recibido';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🤖 Iniciando procesamiento automático de facturas DIAN...');
        
        try {
            // Obtener configuraciones activas
            $configuraciones = $this->obtenerConfiguracionesActivas();
            
            if ($configuraciones->isEmpty()) {
                $this->warn('⚠️ No hay configuraciones DIAN activas');
                return 0;
            }

            $totalEmpresas = $configuraciones->count();
            $this->info("📊 Procesando {$totalEmpresas} empresa(s)...");

            $resultadosGlobales = [
                'empresas_procesadas' => 0,
                'emails_procesados' => 0,
                'facturas_encontradas' => 0,
                'acuses_enviados' => 0,
                'errores' => []
            ];

            foreach ($configuraciones as $configuracion) {
                $this->line(""); // Línea en blanco
                $this->info("🏢 Procesando empresa: {$configuracion->empresa->nombre}");
                
                $resultados = $this->procesarEmpresa($configuracion);
                
                // Acumular resultados
                $resultadosGlobales['empresas_procesadas']++;
                $resultadosGlobales['emails_procesados'] += $resultados['emails_procesados'];
                $resultadosGlobales['facturas_encontradas'] += $resultados['facturas_encontradas'];
                $resultadosGlobales['acuses_enviados'] += $resultados['acuses_enviados'];
                $resultadosGlobales['errores'] = array_merge($resultadosGlobales['errores'], $resultados['errores']);
            }

            // Mostrar resumen final
            $this->mostrarResumenFinal($resultadosGlobales);
            
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error crítico: " . $e->getMessage());
            Log::error('Error en comando DIAN: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Obtener configuraciones activas para procesar
     */
    private function obtenerConfiguracionesActivas()
    {
        $query = ConfiguracionDian::with('empresa')
                                 ->where('activo', true);

        // Si se especifica una empresa específica
        if ($this->option('empresa-id')) {
            $query->where('empresa_id', $this->option('empresa-id'));
        }

        return $query->get();
    }

    /**
     * Procesar una empresa específica
     */
    private function procesarEmpresa(ConfiguracionDian $configuracion): array
    {
        $resultados = [
            'emails_procesados' => 0,
            'facturas_encontradas' => 0,
            'acuses_enviados' => 0,
            'errores' => []
        ];

        try {
            // Verificar horario si no es forzado
            if (!$this->option('force') && !$configuracion->enHorarioPermitido()) {
                $this->warn("⏰ Fuera de horario permitido ({$configuracion->hora_inicio} - {$configuracion->hora_fin})");
                return $resultados;
            }

            // Paso 1: Procesar emails
            $this->line("📧 Procesando emails...");
            $resultadosEmails = $this->procesarEmails($configuracion);
            
            $resultados['emails_procesados'] = $resultadosEmails['emails_procesados'];
            $resultados['facturas_encontradas'] = $resultadosEmails['facturas_encontradas'];
            $resultados['errores'] = array_merge($resultados['errores'], $resultadosEmails['errores']);

            if ($resultadosEmails['emails_procesados'] > 0) {
                $this->info("✅ {$resultadosEmails['emails_procesados']} emails procesados, {$resultadosEmails['facturas_encontradas']} facturas encontradas");
            } else {
                $this->line("ℹ️ No hay emails nuevos para procesar");
            }

            // Paso 2: Enviar acuses pendientes
            $this->line("📤 Enviando acuses pendientes...");
            $resultadosAcuses = $this->enviarAcuses($configuracion);
            
            $resultados['acuses_enviados'] = $resultadosAcuses['acuses_enviados'];
            $resultados['errores'] = array_merge($resultados['errores'], $resultadosAcuses['errores']);

            if ($resultadosAcuses['acuses_enviados'] > 0) {
                $this->info("✅ {$resultadosAcuses['acuses_enviados']} acuses enviados");
            } else {
                $this->line("ℹ️ No hay acuses pendientes");
            }

        } catch (\Exception $e) {
            $error = "Error procesando empresa {$configuracion->empresa->nombre}: " . $e->getMessage();
            $resultados['errores'][] = $error;
            $this->error("❌ " . $error);
            Log::error($error);
        }

        return $resultados;
    }

    /**
     * Procesar emails de una configuración
     */
    private function procesarEmails(ConfiguracionDian $configuracion): array
    {
        try {
            $emailProcessor = new EmailProcessorService($configuracion);
            return $emailProcessor->procesarEmails();
            
        } catch (\Exception $e) {
            return [
                'emails_procesados' => 0,
                'facturas_encontradas' => 0,
                'errores' => ["Error procesando emails: " . $e->getMessage()]
            ];
        }
    }

    /**
     * Enviar acuses pendientes
     */
    private function enviarAcuses(ConfiguracionDian $configuracion): array
    {
        try {
            $acuseGenerator = new AcuseGeneratorService($configuracion);
            return $acuseGenerator->enviarAcusesPendientes();
            
        } catch (\Exception $e) {
            return [
                'acuses_enviados' => 0,
                'errores' => ["Error enviando acuses: " . $e->getMessage()]
            ];
        }
    }

    /**
     * Mostrar resumen final
     */
    private function mostrarResumenFinal(array $resultados): void
    {
        $this->line(""); // Línea en blanco
        $this->info("🎯 RESUMEN FINAL:");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        
        $this->line("📊 Empresas procesadas: " . $resultados['empresas_procesadas']);
        $this->line("📧 Emails procesados: " . $resultados['emails_procesados']);
        $this->line("📄 Facturas encontradas: " . $resultados['facturas_encontradas']);
        $this->line("📤 Acuses enviados: " . $resultados['acuses_enviados']);
        
        if (!empty($resultados['errores'])) {
            $this->line("❌ Errores: " . count($resultados['errores']));
            
            if ($this->option('verbose')) {
                $this->line("");
                $this->error("DETALLES DE ERRORES:");
                foreach ($resultados['errores'] as $error) {
                    $this->line("  • " . $error);
                }
            }
        } else {
            $this->line("✅ Sin errores");
        }
        
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("🎉 Procesamiento completado exitosamente");
        
        // Log del resumen
        Log::info('Procesamiento DIAN completado', $resultados);
    }
}
