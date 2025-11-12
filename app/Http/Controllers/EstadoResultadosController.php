<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PlanCuenta;
use App\Models\MovimientoContable;
use Carbon\Carbon;

class EstadoResultadosController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Mostrar formulario del Estado de Resultados
     */
    public function index()
    {
        return view('contabilidad.estado-resultados.index');
    }

    /**
     * Generar Estado de Resultados
     */
    public function generar(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'nivel_detalle' => 'required|integer|min:1|max:6',
            'mostrar_ceros' => 'boolean'
        ]);

        $fechaInicio = Carbon::parse($request->fecha_inicio);
        $fechaFin = Carbon::parse($request->fecha_fin);
        $nivelDetalle = $request->nivel_detalle;
        $mostrarCeros = $request->boolean('mostrar_ceros', false);

        // Obtener datos del estado de resultados
        $estadoResultados = $this->calcularEstadoResultados($fechaInicio, $fechaFin, $nivelDetalle, $mostrarCeros);

        return response()->json([
            'success' => true,
            'estado_resultados' => $estadoResultados,
            'periodo' => $fechaInicio->format('d/m/Y') . ' - ' . $fechaFin->format('d/m/Y')
        ]);
    }

    /**
     * Exportar Estado de Resultados a PDF
     */
    public function exportarPdf(Request $request)
    {
        $fechaInicio = Carbon::parse($request->fecha_inicio);
        $fechaFin = Carbon::parse($request->fecha_fin);
        $nivelDetalle = $request->nivel_detalle ?? 4;
        $mostrarCeros = $request->boolean('mostrar_ceros', false);

        $estadoResultados = $this->calcularEstadoResultados($fechaInicio, $fechaFin, $nivelDetalle, $mostrarCeros);

        $pdf = \PDF::loadView('contabilidad.estado-resultados.pdf', compact('estadoResultados', 'fechaInicio', 'fechaFin'));
        
        return $pdf->download('Estado_Resultados_' . $fechaInicio->format('Y-m-d') . '_' . $fechaFin->format('Y-m-d') . '.pdf');
    }

    /**
     * Calcular Estado de Resultados
     */
    private function calcularEstadoResultados(Carbon $fechaInicio, Carbon $fechaFin, int $nivelDetalle, bool $mostrarCeros): array
    {
        $estadoResultados = [
            'ingresos_operacionales' => [],
            'ingresos_no_operacionales' => [],
            'costos_ventas' => [],
            'gastos_operacionales' => [],
            'gastos_no_operacionales' => [],
            'totales' => [
                'total_ingresos_operacionales' => 0,
                'total_ingresos_no_operacionales' => 0,
                'total_ingresos' => 0,
                'total_costos_ventas' => 0,
                'utilidad_bruta' => 0,
                'total_gastos_operacionales' => 0,
                'utilidad_operacional' => 0,
                'total_gastos_no_operacionales' => 0,
                'utilidad_antes_impuestos' => 0,
                'utilidad_neta' => 0
            ]
        ];

        // Obtener cuentas de resultados (clases 4, 5, 6)
        $cuentas = PlanCuenta::where(function($query) {
                        $query->whereIn('clase', ['4', '5', '6'])
                              ->orWhere('codigo', 'LIKE', '4%')
                              ->orWhere('codigo', 'LIKE', '5%')
                              ->orWhere('codigo', 'LIKE', '6%');
                    })
                            ->where('nivel', '<=', $nivelDetalle)
                            ->where('estado', true)
                            ->orderBy('codigo')
                            ->get();

        foreach ($cuentas as $cuenta) {
            $saldo = $cuenta->getSaldo($fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d'));

            // Filtrar cuentas con saldo cero si no se deben mostrar
            if (!$mostrarCeros && $saldo == 0) {
                continue;
            }

            $cuentaData = [
                'codigo' => $cuenta->codigo,
                'nombre' => $cuenta->nombre,
                'nivel' => $cuenta->nivel,
                'saldo' => $saldo,
                'saldo_formateado' => number_format(abs($saldo), 2, ',', '.')
            ];

            // Clasificar según la clase y tipo de cuenta
            $this->clasificarCuentaResultados($cuenta, $cuentaData, $estadoResultados);
        }

        // Calcular totales y utilidades
        $this->calcularTotalesResultados($estadoResultados);

        return $estadoResultados;
    }

    /**
     * Clasificar cuenta en el estado de resultados
     */
    private function clasificarCuentaResultados(PlanCuenta $cuenta, array $cuentaData, array &$estadoResultados): void
    {
        $clase = $cuenta->clase ?: substr($cuenta->codigo, 0, 1);
        
        switch ($clase) {
            case '4': // Ingresos
                // Clasificar según el código: 41xx son operacionales, 42xx son no operacionales
                if (str_starts_with($cuenta->codigo, '41')) {
                    $estadoResultados['ingresos_operacionales'][] = $cuentaData;
                    $estadoResultados['totales']['total_ingresos_operacionales'] += abs($cuentaData['saldo']);
                } else {
                    $estadoResultados['ingresos_no_operacionales'][] = $cuentaData;
                    $estadoResultados['totales']['total_ingresos_no_operacionales'] += abs($cuentaData['saldo']);
                }
                break;

            case '6': // Costos de Ventas
                $estadoResultados['costos_ventas'][] = $cuentaData;
                $estadoResultados['totales']['total_costos_ventas'] += abs($cuentaData['saldo']);
                break;

            case '5': // Gastos
                // Clasificar según el código: 51xx son operacionales, 52xx son no operacionales
                if (str_starts_with($cuenta->codigo, '51')) {
                    $estadoResultados['gastos_operacionales'][] = $cuentaData;
                    $estadoResultados['totales']['total_gastos_operacionales'] += abs($cuentaData['saldo']);
                } else {
                    $estadoResultados['gastos_no_operacionales'][] = $cuentaData;
                    $estadoResultados['totales']['total_gastos_no_operacionales'] += abs($cuentaData['saldo']);
                }
                break;
        }
    }

    /**
     * Calcular totales y utilidades
     */
    private function calcularTotalesResultados(array &$estadoResultados): void
    {
        $totales = &$estadoResultados['totales'];

        // Total ingresos
        $totales['total_ingresos'] = $totales['total_ingresos_operacionales'] + $totales['total_ingresos_no_operacionales'];

        // Utilidad bruta
        $totales['utilidad_bruta'] = $totales['total_ingresos_operacionales'] - $totales['total_costos_ventas'];

        // Utilidad operacional
        $totales['utilidad_operacional'] = $totales['utilidad_bruta'] - $totales['total_gastos_operacionales'];

        // Utilidad antes de impuestos
        $totales['utilidad_antes_impuestos'] = $totales['utilidad_operacional'] + 
                                             $totales['total_ingresos_no_operacionales'] - 
                                             $totales['total_gastos_no_operacionales'];

        // Utilidad neta (por ahora igual a utilidad antes de impuestos)
        $totales['utilidad_neta'] = $totales['utilidad_antes_impuestos'];

        // Formatear todos los totales
        foreach ($totales as $key => $valor) {
            $totales[$key . '_formateado'] = number_format($valor, 2, ',', '.');
        }
    }

    /**
     * Calcular saldo de una cuenta específica en un período
     */
    private function calcularSaldoCuenta(PlanCuenta $cuenta, Carbon $fechaInicio, Carbon $fechaFin): float
    {
        // Si es cuenta de movimiento (nivel >= 4), calcular directamente
        if ($cuenta->nivel >= 4) {
            return $cuenta->getSaldo($fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d'));
        }

        // Si es cuenta padre, sumar saldos de subcuentas
        $saldoTotal = 0;
        $subcuentas = PlanCuenta::where('cuenta_padre_id', $cuenta->id)
                                ->where('estado', true)
                                ->get();

        foreach ($subcuentas as $subcuenta) {
            $saldoTotal += $this->calcularSaldoCuenta($subcuenta, $fechaInicio, $fechaFin);
        }

        return $saldoTotal;
    }

    /**
     * Análisis de márgenes
     */
    public function analisisMaxgenes(Request $request)
    {
        $fechaInicio = Carbon::parse($request->fecha_inicio);
        $fechaFin = Carbon::parse($request->fecha_fin);

        $estadoResultados = $this->calcularEstadoResultados($fechaInicio, $fechaFin, 4, false);
        $totales = $estadoResultados['totales'];

        $margenes = [];

        if ($totales['total_ingresos_operacionales'] > 0) {
            $margenes['margen_bruto'] = ($totales['utilidad_bruta'] / $totales['total_ingresos_operacionales']) * 100;
            $margenes['margen_operacional'] = ($totales['utilidad_operacional'] / $totales['total_ingresos_operacionales']) * 100;
            $margenes['margen_neto'] = ($totales['utilidad_neta'] / $totales['total_ingresos_operacionales']) * 100;
        } else {
            $margenes['margen_bruto'] = 0;
            $margenes['margen_operacional'] = 0;
            $margenes['margen_neto'] = 0;
        }

        // Formatear márgenes
        foreach ($margenes as $key => $valor) {
            $margenes[$key . '_formateado'] = number_format($valor, 2, ',', '.') . '%';
        }

        return response()->json([
            'success' => true,
            'margenes' => $margenes,
            'totales' => $totales
        ]);
    }

    /**
     * Comparativo mensual
     */
    public function comparativoMensual(Request $request)
    {
        $año = $request->año ?? now()->year;
        $comparativo = [];

        for ($mes = 1; $mes <= 12; $mes++) {
            $fechaInicio = Carbon::createFromDate($año, $mes, 1);
            $fechaFin = $fechaInicio->copy()->endOfMonth();

            $estadoResultados = $this->calcularEstadoResultados($fechaInicio, $fechaFin, 4, false);

            $comparativo[] = [
                'mes' => $fechaInicio->format('M'),
                'mes_numero' => $mes,
                'ingresos' => $estadoResultados['totales']['total_ingresos_operacionales'],
                'costos' => $estadoResultados['totales']['total_costos_ventas'],
                'gastos' => $estadoResultados['totales']['total_gastos_operacionales'],
                'utilidad_neta' => $estadoResultados['totales']['utilidad_neta']
            ];
        }

        return response()->json([
            'success' => true,
            'comparativo' => $comparativo,
            'año' => $año
        ]);
    }
}
