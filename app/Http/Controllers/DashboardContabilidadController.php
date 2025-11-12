<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Venta;
use App\Models\Comprobante;
use App\Models\MovimientoContable;
use App\Models\PlanCuenta;
use App\Models\ConfiguracionContable;
use Carbon\Carbon;

class DashboardContabilidadController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Dashboard principal de contabilidad NIF
     */
    public function index()
    {
        // Estadísticas generales
        $estadisticas = $this->obtenerEstadisticas();
        
        // Resumen financiero
        $resumenFinanciero = $this->obtenerResumenFinanciero();
        
        // Últimos movimientos
        $ultimosMovimientos = $this->obtenerUltimosMovimientos();
        
        // Estado de integración
        $estadoIntegracion = $this->verificarIntegracion();
        
        return view('contabilidad.dashboard.index', compact(
            'estadisticas',
            'resumenFinanciero', 
            'ultimosMovimientos',
            'estadoIntegracion'
        ));
    }

    /**
     * Obtener estadísticas generales
     */
    private function obtenerEstadisticas(): array
    {
        $hoy = Carbon::now();
        $mesActual = $hoy->startOfMonth();
        $anoActual = $hoy->startOfYear();

        return [
            'total_ventas' => Venta::count(),
            'ventas_mes' => Venta::where('fecha_venta', '>=', $mesActual)->count(),
            'total_comprobantes' => Comprobante::count(),
            'comprobantes_mes' => Comprobante::where('fecha', '>=', $mesActual)->count(),
            'total_movimientos' => MovimientoContable::count(),
            'movimientos_mes' => MovimientoContable::where('fecha', '>=', $mesActual)->count(),
            'cuentas_activas' => PlanCuenta::where('estado', true)->count(),
            'cuentas_con_movimientos' => PlanCuenta::whereHas('movimientos')->count()
        ];
    }

    /**
     * Obtener resumen financiero
     */
    private function obtenerResumenFinanciero(): array
    {
        // Activos totales (clase 1)
        $totalActivos = PlanCuenta::where('clase', '1')
                                 ->where('estado', true)
                                 ->get()
                                 ->sum(function($cuenta) {
                                     return $cuenta->getSaldo();
                                 });

        // Pasivos totales (clase 2)
        $totalPasivos = PlanCuenta::where('clase', '2')
                                 ->where('estado', true)
                                 ->get()
                                 ->sum(function($cuenta) {
                                     return abs($cuenta->getSaldo());
                                 });

        // Patrimonio total (clase 3)
        $totalPatrimonio = PlanCuenta::where('clase', '3')
                                    ->where('estado', true)
                                    ->get()
                                    ->sum(function($cuenta) {
                                        return abs($cuenta->getSaldo());
                                    });

        // Ingresos del mes (clase 4)
        $ingresosMes = PlanCuenta::where('clase', '4')
                                ->where('estado', true)
                                ->get()
                                ->sum(function($cuenta) {
                                    return abs($cuenta->getSaldo(Carbon::now()->startOfMonth()->format('Y-m-d')));
                                });

        // Gastos del mes (clase 5)
        $gastosMes = PlanCuenta::where('clase', '5')
                              ->where('estado', true)
                              ->get()
                              ->sum(function($cuenta) {
                                  return abs($cuenta->getSaldo(Carbon::now()->startOfMonth()->format('Y-m-d')));
                              });

        return [
            'total_activos' => $totalActivos,
            'total_pasivos' => $totalPasivos,
            'total_patrimonio' => $totalPatrimonio,
            'ingresos_mes' => $ingresosMes,
            'gastos_mes' => $gastosMes,
            'utilidad_mes' => $ingresosMes - $gastosMes,
            'total_activos_formateado' => number_format($totalActivos, 0, ',', '.'),
            'total_pasivos_formateado' => number_format($totalPasivos, 0, ',', '.'),
            'total_patrimonio_formateado' => number_format($totalPatrimonio, 0, ',', '.'),
            'ingresos_mes_formateado' => number_format($ingresosMes, 0, ',', '.'),
            'gastos_mes_formateado' => number_format($gastosMes, 0, ',', '.'),
            'utilidad_mes_formateado' => number_format($ingresosMes - $gastosMes, 0, ',', '.')
        ];
    }

    /**
     * Obtener últimos movimientos
     */
    private function obtenerUltimosMovimientos(): array
    {
        return MovimientoContable::with(['comprobante', 'cuenta'])
                                ->orderBy('fecha', 'desc')
                                ->orderBy('created_at', 'desc')
                                ->limit(10)
                                ->get()
                                ->map(function($movimiento) {
                                    return [
                                        'fecha' => $movimiento->fecha,
                                        'comprobante' => $movimiento->comprobante->prefijo . $movimiento->comprobante->numero,
                                        'cuenta' => $movimiento->cuenta->codigo . ' - ' . $movimiento->cuenta->nombre,
                                        'descripcion' => $movimiento->descripcion,
                                        'debito' => $movimiento->debito,
                                        'credito' => $movimiento->credito,
                                        'debito_formateado' => number_format($movimiento->debito, 0, ',', '.'),
                                        'credito_formateado' => number_format($movimiento->credito, 0, ',', '.')
                                    ];
                                })
                                ->toArray();
    }

    /**
     * Verificar estado de integración
     */
    private function verificarIntegracion(): array
    {
        $totalVentas = Venta::count();
        $ventasConComprobante = 0;
        
        if ($totalVentas > 0) {
            foreach (Venta::all() as $venta) {
                $comprobante = Comprobante::where('descripcion', 'LIKE', "%{$venta->numero_factura}%")->first();
                if ($comprobante) {
                    $ventasConComprobante++;
                }
            }
        }

        $porcentajeIntegracion = $totalVentas > 0 ? ($ventasConComprobante / $totalVentas) * 100 : 100;

        // Verificar configuración contable
        $configuraciones = ['caja', 'ventas', 'iva_ventas', 'costo_ventas', 'inventario'];
        $configuracionesOk = 0;
        
        foreach ($configuraciones as $concepto) {
            try {
                $cuenta = ConfiguracionContable::getCuentaPorConcepto($concepto);
                if ($cuenta) {
                    $configuracionesOk++;
                }
            } catch (\Exception $e) {
                // Configuración no encontrada
            }
        }

        $porcentajeConfiguracion = (count($configuraciones) > 0) ? ($configuracionesOk / count($configuraciones)) * 100 : 100;

        return [
            'total_ventas' => $totalVentas,
            'ventas_con_comprobante' => $ventasConComprobante,
            'porcentaje_integracion' => $porcentajeIntegracion,
            'configuraciones_ok' => $configuracionesOk,
            'total_configuraciones' => count($configuraciones),
            'porcentaje_configuracion' => $porcentajeConfiguracion,
            'estado_general' => ($porcentajeIntegracion >= 95 && $porcentajeConfiguracion >= 80) ? 'excelente' : 
                              (($porcentajeIntegracion >= 80 && $porcentajeConfiguracion >= 60) ? 'bueno' : 'necesita_atencion')
        ];
    }

    /**
     * Generar reporte rápido
     */
    public function reporteRapido(Request $request)
    {
        $tipo = $request->get('tipo', 'balance');
        $fecha = $request->get('fecha', Carbon::now()->format('Y-m-d'));

        switch ($tipo) {
            case 'balance':
                return redirect()->route('balance-general.index');
            case 'resultados':
                return redirect()->route('estado-resultados.index');
            case 'flujo':
                return redirect()->route('flujo-efectivo.index');
            default:
                return redirect()->route('contabilidad.dashboard');
        }
    }
}
