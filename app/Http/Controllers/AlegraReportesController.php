<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\Alegra\AlegraService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AlegraReportesController extends Controller
{
    protected $alegraService;
    
    public function __construct()
    {
        $this->middleware('auth');
        // Intentamos usar el servicio existente de Alegra
        try {
            $this->alegraService = app(AlegraService::class);
        } catch (\Exception $e) {
            Log::error('Error al inicializar AlegraService: ' . $e->getMessage());
        }
    }
    
    /**
     * Muestra el dashboard de reportes de facturas de Alegra
     */
    public function dashboard(Request $request)
    {
        try {
            // Parámetros de filtrado
            $fechaInicio = $request->input('fecha_inicio', Carbon::now()->subDays(30)->format('Y-m-d'));
            $fechaFin = $request->input('fecha_fin', Carbon::now()->format('Y-m-d'));
            $agrupamiento = $request->input('agrupamiento', 'diario'); // diario, semanal, mensual
            
            // Obtener facturas de Alegra
            $facturas = $this->alegraService->getInvoices([
                'start' => $fechaInicio,
                'end' => $fechaFin,
                'limit' => 1000, // Ajustar según necesidad
            ]);
            
            // Procesar datos para estadísticas
            $estadisticas = $this->procesarEstadisticas($facturas, $fechaInicio, $fechaFin, $agrupamiento);
            
            return view('alegra.reportes.dashboard', compact('estadisticas', 'fechaInicio', 'fechaFin', 'agrupamiento'));
        } catch (\Exception $e) {
            Log::error('Error al generar dashboard de Alegra: ' . $e->getMessage());
            return back()->with('error', 'Error al generar dashboard: ' . $e->getMessage());
        }
    }
    
    /**
     * Muestra el reporte detallado de facturas por período
     */
    public function reportePeriodo(Request $request)
    {
        try {
            // Parámetros de filtrado
            $fechaInicio = $request->input('fecha_inicio', Carbon::now()->subDays(30)->format('Y-m-d'));
            $fechaFin = $request->input('fecha_fin', Carbon::now()->format('Y-m-d'));
            $agrupamiento = $request->input('agrupamiento', 'diario'); // diario, semanal, mensual
            
            // Obtener facturas de Alegra
            $facturas = $this->alegraService->getInvoices([
                'start' => $fechaInicio,
                'end' => $fechaFin,
                'limit' => 1000, // Ajustar según necesidad
            ]);
            
            // Agrupar facturas por período
            $facturasPorPeriodo = $this->agruparFacturasPorPeriodo($facturas, $agrupamiento);
            
            return view('alegra.reportes.periodo', compact('facturasPorPeriodo', 'fechaInicio', 'fechaFin', 'agrupamiento'));
        } catch (\Exception $e) {
            Log::error('Error al generar reporte por período: ' . $e->getMessage());
            return back()->with('error', 'Error al generar reporte: ' . $e->getMessage());
        }
    }
    
    /**
     * Procesa las estadísticas de facturas
     */
    private function procesarEstadisticas($facturas, $fechaInicio, $fechaFin, $agrupamiento)
    {
        // Inicializar estructura de datos
        $estadisticas = [
            'total_facturas' => 0,
            'total_monto' => 0,
            'promedio_monto' => 0,
            'facturas_por_periodo' => [],
            'montos_por_periodo' => [],
            'periodos' => [],
        ];
        
        if (empty($facturas)) {
            return $estadisticas;
        }
        
        // Crear períodos según agrupamiento
        $periodos = $this->crearPeriodos($fechaInicio, $fechaFin, $agrupamiento);
        
        // Inicializar contadores por período
        foreach ($periodos as $periodo) {
            $estadisticas['facturas_por_periodo'][$periodo] = 0;
            $estadisticas['montos_por_periodo'][$periodo] = 0;
        }
        
        // Procesar cada factura
        foreach ($facturas as $factura) {
            $fechaFactura = Carbon::parse($factura['date']);
            $periodo = $this->obtenerPeriodo($fechaFactura, $agrupamiento);
            
            // Incrementar contadores
            $estadisticas['total_facturas']++;
            $estadisticas['total_monto'] += $factura['total'] ?? 0;
            
            // Incrementar contadores por período
            if (isset($estadisticas['facturas_por_periodo'][$periodo])) {
                $estadisticas['facturas_por_periodo'][$periodo]++;
                $estadisticas['montos_por_periodo'][$periodo] += $factura['total'] ?? 0;
            }
        }
        
        // Calcular promedio
        if ($estadisticas['total_facturas'] > 0) {
            $estadisticas['promedio_monto'] = $estadisticas['total_monto'] / $estadisticas['total_facturas'];
        }
        
        $estadisticas['periodos'] = array_keys($estadisticas['facturas_por_periodo']);
        
        return $estadisticas;
    }
    
    /**
     * Agrupa facturas por período
     */
    private function agruparFacturasPorPeriodo($facturas, $agrupamiento)
    {
        $facturasPorPeriodo = [];
        
        foreach ($facturas as $factura) {
            $fechaFactura = Carbon::parse($factura['date']);
            $periodo = $this->obtenerPeriodo($fechaFactura, $agrupamiento);
            
            if (!isset($facturasPorPeriodo[$periodo])) {
                $facturasPorPeriodo[$periodo] = [
                    'facturas' => [],
                    'total' => 0,
                    'cantidad' => 0
                ];
            }
            
            $facturasPorPeriodo[$periodo]['facturas'][] = $factura;
            $facturasPorPeriodo[$periodo]['total'] += $factura['total'] ?? 0;
            $facturasPorPeriodo[$periodo]['cantidad']++;
        }
        
        // Ordenar por período
        ksort($facturasPorPeriodo);
        
        return $facturasPorPeriodo;
    }
    
    /**
     * Crea un array de períodos según el agrupamiento
     */
    private function crearPeriodos($fechaInicio, $fechaFin, $agrupamiento)
    {
        $inicio = Carbon::parse($fechaInicio);
        $fin = Carbon::parse($fechaFin);
        $periodos = [];
        
        if ($agrupamiento === 'diario') {
            $period = CarbonPeriod::create($inicio, '1 day', $fin);
            foreach ($period as $date) {
                $periodos[] = $date->format('Y-m-d');
            }
        } elseif ($agrupamiento === 'semanal') {
            $currentDate = $inicio->copy()->startOfWeek();
            while ($currentDate <= $fin) {
                $periodos[] = $currentDate->format('Y-W');
                $currentDate->addWeek();
            }
        } elseif ($agrupamiento === 'mensual') {
            $currentDate = $inicio->copy()->startOfMonth();
            while ($currentDate <= $fin) {
                $periodos[] = $currentDate->format('Y-m');
                $currentDate->addMonth();
            }
        }
        
        return $periodos;
    }
    
    /**
     * Obtiene el período al que pertenece una fecha según el agrupamiento
     */
    private function obtenerPeriodo($fecha, $agrupamiento)
    {
        if ($agrupamiento === 'diario') {
            return $fecha->format('Y-m-d');
        } elseif ($agrupamiento === 'semanal') {
            return $fecha->format('Y-W');
        } elseif ($agrupamiento === 'mensual') {
            return $fecha->format('Y-m');
        }
        
        return $fecha->format('Y-m-d');
    }
}
