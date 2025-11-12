<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PlanCuenta;
use App\Models\MovimientoContable;
use Carbon\Carbon;

class FlujoEfectivoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Mostrar formulario del Flujo de Efectivo
     */
    public function index()
    {
        return view('contabilidad.flujo-efectivo.index');
    }

    /**
     * Generar Flujo de Efectivo
     */
    public function generar(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'metodo' => 'required|in:directo,indirecto',
        ]);

        $fechaInicio = Carbon::parse($request->fecha_inicio);
        $fechaFin = Carbon::parse($request->fecha_fin);
        $metodo = $request->metodo;

        $flujoEfectivo = $this->calcularFlujoEfectivo($fechaInicio, $fechaFin, $metodo);

        return response()->json([
            'success' => true,
            'flujo_efectivo' => $flujoEfectivo,
            'periodo' => $fechaInicio->format('d/m/Y') . ' - ' . $fechaFin->format('d/m/Y'),
            'metodo' => $metodo
        ]);
    }

    /**
     * Exportar Flujo de Efectivo a PDF
     */
    public function exportarPdf(Request $request)
    {
        $fechaInicio = Carbon::parse($request->fecha_inicio);
        $fechaFin = Carbon::parse($request->fecha_fin);
        $metodo = $request->metodo;

        $flujoEfectivo = $this->calcularFlujoEfectivo($fechaInicio, $fechaFin, $metodo);

        $pdf = \PDF::loadView('contabilidad.flujo-efectivo.pdf', compact('flujoEfectivo', 'fechaInicio', 'fechaFin', 'metodo'));
        
        return $pdf->download('Flujo_Efectivo_' . $fechaInicio->format('Y-m-d') . '_' . $fechaFin->format('Y-m-d') . '.pdf');
    }

    /**
     * Calcular Flujo de Efectivo
     */
    private function calcularFlujoEfectivo(Carbon $fechaInicio, Carbon $fechaFin, string $metodo): array
    {
        if ($metodo === 'directo') {
            return $this->calcularFlujoDirecto($fechaInicio, $fechaFin);
        } else {
            return $this->calcularFlujoIndirecto($fechaInicio, $fechaFin);
        }
    }

    /**
     * Calcular Flujo de Efectivo Método Directo
     */
    private function calcularFlujoDirecto(Carbon $fechaInicio, Carbon $fechaFin): array
    {
        $flujo = [
            'actividades_operacion' => [
                'entradas' => [],
                'salidas' => [],
                'total_entradas' => 0,
                'total_salidas' => 0,
                'flujo_neto_operacion' => 0
            ],
            'actividades_inversion' => [
                'entradas' => [],
                'salidas' => [],
                'total_entradas' => 0,
                'total_salidas' => 0,
                'flujo_neto_inversion' => 0
            ],
            'actividades_financiacion' => [
                'entradas' => [],
                'salidas' => [],
                'total_entradas' => 0,
                'total_salidas' => 0,
                'flujo_neto_financiacion' => 0
            ],
            'totales' => [
                'flujo_neto_periodo' => 0,
                'efectivo_inicio' => 0,
                'efectivo_final' => 0
            ]
        ];

        // Obtener movimientos de efectivo y equivalentes (cuentas 11)
        $cuentasEfectivo = PlanCuenta::where('codigo', 'LIKE', '11%')
                                   ->where('estado', true)
                                   ->get();

        foreach ($cuentasEfectivo as $cuenta) {
            $movimientos = $cuenta->movimientos()
                                 ->whereBetween('fecha', [$fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d')])
                                 ->with('comprobante')
                                 ->get();

            foreach ($movimientos as $movimiento) {
                $this->clasificarMovimientoDirecto($movimiento, $flujo);
            }
        }

        // Calcular totales
        $flujo['actividades_operacion']['flujo_neto_operacion'] = 
            $flujo['actividades_operacion']['total_entradas'] - $flujo['actividades_operacion']['total_salidas'];
        
        $flujo['actividades_inversion']['flujo_neto_inversion'] = 
            $flujo['actividades_inversion']['total_entradas'] - $flujo['actividades_inversion']['total_salidas'];
        
        $flujo['actividades_financiacion']['flujo_neto_financiacion'] = 
            $flujo['actividades_financiacion']['total_entradas'] - $flujo['actividades_financiacion']['total_salidas'];

        $flujo['totales']['flujo_neto_periodo'] = 
            $flujo['actividades_operacion']['flujo_neto_operacion'] +
            $flujo['actividades_inversion']['flujo_neto_inversion'] +
            $flujo['actividades_financiacion']['flujo_neto_financiacion'];

        // Calcular efectivo al inicio y final del período
        $flujo['totales']['efectivo_inicio'] = $this->calcularEfectivoFecha($fechaInicio->copy()->subDay());
        $flujo['totales']['efectivo_final'] = $this->calcularEfectivoFecha($fechaFin);

        // Formatear valores para mostrar
        $this->formatearValoresFlujo($flujo);

        return $flujo;
    }

    /**
     * Calcular Flujo de Efectivo Método Indirecto
     */
    private function calcularFlujoIndirecto(Carbon $fechaInicio, Carbon $fechaFin): array
    {
        $flujo = [
            'actividades_operacion' => [
                'utilidad_neta' => 0,
                'ajustes' => [],
                'cambios_capital_trabajo' => [],
                'total_ajustes' => 0,
                'total_cambios_capital' => 0,
                'flujo_neto_operacion' => 0
            ],
            'actividades_inversion' => [
                'movimientos' => [],
                'total' => 0
            ],
            'actividades_financiacion' => [
                'movimientos' => [],
                'total' => 0
            ],
            'totales' => [
                'flujo_neto_periodo' => 0,
                'efectivo_inicio' => 0,
                'efectivo_final' => 0
            ]
        ];

        // Calcular utilidad neta del período
        $flujo['actividades_operacion']['utilidad_neta'] = $this->calcularUtilidadNeta($fechaInicio, $fechaFin);

        // Agregar ajustes por partidas que no afectan el efectivo
        $this->agregarAjustesIndirecto($flujo, $fechaInicio, $fechaFin);

        // Calcular cambios en el capital de trabajo
        $this->calcularCambiosCapitalTrabajo($flujo, $fechaInicio, $fechaFin);

        // Calcular actividades de inversión y financiación
        $this->calcularActividadesInversionFinanciacion($flujo, $fechaInicio, $fechaFin);

        // Calcular totales
        $flujo['actividades_operacion']['flujo_neto_operacion'] = 
            $flujo['actividades_operacion']['utilidad_neta'] +
            $flujo['actividades_operacion']['total_ajustes'] +
            $flujo['actividades_operacion']['total_cambios_capital'];

        $flujo['totales']['flujo_neto_periodo'] = 
            $flujo['actividades_operacion']['flujo_neto_operacion'] +
            $flujo['actividades_inversion']['total'] +
            $flujo['actividades_financiacion']['total'];

        // Calcular efectivo al inicio y final del período
        $flujo['totales']['efectivo_inicio'] = $this->calcularEfectivoFecha($fechaInicio->copy()->subDay());
        $flujo['totales']['efectivo_final'] = $this->calcularEfectivoFecha($fechaFin);

        // Formatear valores para mostrar
        $this->formatearValoresFlujo($flujo);

        return $flujo;
    }

    /**
     * Clasificar movimiento para método directo
     */
    private function clasificarMovimientoDirecto($movimiento, &$flujo)
    {
        $concepto = $movimiento->concepto ?? 'Movimiento de efectivo';
        $valor = $movimiento->debito - $movimiento->credito;
        
        // Clasificar según el concepto o la cuenta contrapartida
        if ($this->esOperacional($movimiento)) {
            if ($valor > 0) {
                $flujo['actividades_operacion']['entradas'][] = [
                    'concepto' => $concepto,
                    'valor' => $valor,
                    'fecha' => $movimiento->fecha
                ];
                $flujo['actividades_operacion']['total_entradas'] += $valor;
            } else {
                $flujo['actividades_operacion']['salidas'][] = [
                    'concepto' => $concepto,
                    'valor' => abs($valor),
                    'fecha' => $movimiento->fecha
                ];
                $flujo['actividades_operacion']['total_salidas'] += abs($valor);
            }
        } elseif ($this->esInversion($movimiento)) {
            if ($valor > 0) {
                $flujo['actividades_inversion']['entradas'][] = [
                    'concepto' => $concepto,
                    'valor' => $valor,
                    'fecha' => $movimiento->fecha
                ];
                $flujo['actividades_inversion']['total_entradas'] += $valor;
            } else {
                $flujo['actividades_inversion']['salidas'][] = [
                    'concepto' => $concepto,
                    'valor' => abs($valor),
                    'fecha' => $movimiento->fecha
                ];
                $flujo['actividades_inversion']['total_salidas'] += abs($valor);
            }
        } else {
            // Actividades de financiación
            if ($valor > 0) {
                $flujo['actividades_financiacion']['entradas'][] = [
                    'concepto' => $concepto,
                    'valor' => $valor,
                    'fecha' => $movimiento->fecha
                ];
                $flujo['actividades_financiacion']['total_entradas'] += $valor;
            } else {
                $flujo['actividades_financiacion']['salidas'][] = [
                    'concepto' => $concepto,
                    'valor' => abs($valor),
                    'fecha' => $movimiento->fecha
                ];
                $flujo['actividades_financiacion']['total_salidas'] += abs($valor);
            }
        }
    }

    /**
     * Determinar si un movimiento es operacional
     */
    private function esOperacional($movimiento): bool
    {
        $concepto = strtolower($movimiento->concepto ?? '');
        
        return str_contains($concepto, 'venta') ||
               str_contains($concepto, 'ingreso') ||
               str_contains($concepto, 'gasto') ||
               str_contains($concepto, 'pago') ||
               str_contains($concepto, 'cobro');
    }

    /**
     * Determinar si un movimiento es de inversión
     */
    private function esInversion($movimiento): bool
    {
        $concepto = strtolower($movimiento->concepto ?? '');
        
        return str_contains($concepto, 'activo') ||
               str_contains($concepto, 'equipo') ||
               str_contains($concepto, 'inmueble') ||
               str_contains($concepto, 'inversión');
    }

    /**
     * Calcular utilidad neta del período
     */
    private function calcularUtilidadNeta(Carbon $fechaInicio, Carbon $fechaFin): float
    {
        // Obtener ingresos (clase 4)
        $ingresos = PlanCuenta::where(function($query) {
                        $query->where('clase', '4')
                              ->orWhere('codigo', 'LIKE', '4%');
                    })
                    ->where('estado', true)
                    ->get()
                    ->sum(function($cuenta) use ($fechaInicio, $fechaFin) {
                        return abs($cuenta->getSaldo($fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d')));
                    });

        // Obtener gastos (clase 5)
        $gastos = PlanCuenta::where(function($query) {
                        $query->where('clase', '5')
                              ->orWhere('codigo', 'LIKE', '5%');
                    })
                    ->where('estado', true)
                    ->get()
                    ->sum(function($cuenta) use ($fechaInicio, $fechaFin) {
                        return abs($cuenta->getSaldo($fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d')));
                    });

        // Obtener costos (clase 6)
        $costos = PlanCuenta::where(function($query) {
                        $query->where('clase', '6')
                              ->orWhere('codigo', 'LIKE', '6%');
                    })
                    ->where('estado', true)
                    ->get()
                    ->sum(function($cuenta) use ($fechaInicio, $fechaFin) {
                        return abs($cuenta->getSaldo($fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d')));
                    });

        return $ingresos - $gastos - $costos;
    }

    /**
     * Agregar ajustes para método indirecto
     */
    private function agregarAjustesIndirecto(&$flujo, Carbon $fechaInicio, Carbon $fechaFin)
    {
        // Aquí se agregarían ajustes como depreciaciones, amortizaciones, etc.
        // Por ahora, agregamos un ejemplo básico
        
        $flujo['actividades_operacion']['ajustes'][] = [
            'concepto' => 'Depreciación y amortización',
            'valor' => 0 // Se calcularía según las cuentas de depreciación
        ];

        $flujo['actividades_operacion']['total_ajustes'] = 0;
    }

    /**
     * Calcular cambios en capital de trabajo
     */
    private function calcularCambiosCapitalTrabajo(&$flujo, Carbon $fechaInicio, Carbon $fechaFin)
    {
        // Cambios en cuentas por cobrar, inventarios, cuentas por pagar, etc.
        $flujo['actividades_operacion']['cambios_capital_trabajo'][] = [
            'concepto' => 'Cambios en cuentas por cobrar',
            'valor' => 0
        ];

        $flujo['actividades_operacion']['total_cambios_capital'] = 0;
    }

    /**
     * Calcular actividades de inversión y financiación
     */
    private function calcularActividadesInversionFinanciacion(&$flujo, Carbon $fechaInicio, Carbon $fechaFin)
    {
        $flujo['actividades_inversion']['total'] = 0;
        $flujo['actividades_financiacion']['total'] = 0;
    }

    /**
     * Calcular efectivo en una fecha específica
     */
    private function calcularEfectivoFecha(Carbon $fecha): float
    {
        return PlanCuenta::where('codigo', 'LIKE', '11%')
                        ->where('estado', true)
                        ->get()
                        ->sum(function($cuenta) use ($fecha) {
                            return $cuenta->getSaldo(null, $fecha->format('Y-m-d'));
                        });
    }

    /**
     * Formatear valores del flujo para mostrar
     */
    private function formatearValoresFlujo(&$flujo)
    {
        // Formatear totales generales
        $flujo['totales']['efectivo_inicio_formateado'] = number_format($flujo['totales']['efectivo_inicio'], 2, ',', '.');
        $flujo['totales']['efectivo_final_formateado'] = number_format($flujo['totales']['efectivo_final'], 2, ',', '.');
        $flujo['totales']['flujo_neto_periodo_formateado'] = number_format($flujo['totales']['flujo_neto_periodo'], 2, ',', '.');

        // Formatear actividades de operación
        if (isset($flujo['actividades_operacion']['utilidad_neta'])) {
            $flujo['actividades_operacion']['utilidad_neta_formateada'] = number_format($flujo['actividades_operacion']['utilidad_neta'], 2, ',', '.');
        }
        
        if (isset($flujo['actividades_operacion']['flujo_neto_operacion'])) {
            $flujo['actividades_operacion']['flujo_neto_operacion_formateado'] = number_format($flujo['actividades_operacion']['flujo_neto_operacion'], 2, ',', '.');
        }

        // Formatear actividades de inversión
        if (isset($flujo['actividades_inversion']['total'])) {
            $flujo['actividades_inversion']['total_formateado'] = number_format($flujo['actividades_inversion']['total'], 2, ',', '.');
        }
        
        if (isset($flujo['actividades_inversion']['flujo_neto_inversion'])) {
            $flujo['actividades_inversion']['flujo_neto_inversion_formateado'] = number_format($flujo['actividades_inversion']['flujo_neto_inversion'], 2, ',', '.');
        }

        // Formatear actividades de financiación
        if (isset($flujo['actividades_financiacion']['total'])) {
            $flujo['actividades_financiacion']['total_formateado'] = number_format($flujo['actividades_financiacion']['total'], 2, ',', '.');
        }
        
        if (isset($flujo['actividades_financiacion']['flujo_neto_financiacion'])) {
            $flujo['actividades_financiacion']['flujo_neto_financiacion_formateado'] = number_format($flujo['actividades_financiacion']['flujo_neto_financiacion'], 2, ',', '.');
        }

        // Formatear método directo
        if (isset($flujo['actividades_operacion']['total_entradas'])) {
            $flujo['actividades_operacion']['total_entradas_formateado'] = number_format($flujo['actividades_operacion']['total_entradas'], 2, ',', '.');
            $flujo['actividades_operacion']['total_salidas_formateado'] = number_format($flujo['actividades_operacion']['total_salidas'], 2, ',', '.');
        }

        // Formatear entradas y salidas individuales
        foreach (['actividades_operacion', 'actividades_inversion', 'actividades_financiacion'] as $actividad) {
            if (isset($flujo[$actividad]['entradas'])) {
                foreach ($flujo[$actividad]['entradas'] as &$entrada) {
                    $entrada['valor_formateado'] = number_format($entrada['valor'], 2, ',', '.');
                }
            }
            if (isset($flujo[$actividad]['salidas'])) {
                foreach ($flujo[$actividad]['salidas'] as &$salida) {
                    $salida['valor_formateado'] = number_format($salida['valor'], 2, ',', '.');
                }
            }
        }
    }
}
