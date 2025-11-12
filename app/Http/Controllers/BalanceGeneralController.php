<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PlanCuenta;
use App\Models\MovimientoContable;
use Carbon\Carbon;

class BalanceGeneralController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Mostrar formulario del Balance General
     */
    public function index()
    {
        return view('contabilidad.balance-general.index');
    }

    /**
     * Generar Balance General
     */
    public function generar(Request $request)
    {
        $request->validate([
            'fecha_corte' => 'required|date',
            'nivel_detalle' => 'required|integer|min:1|max:6',
            'mostrar_ceros' => 'boolean'
        ]);

        $fechaCorte = Carbon::parse($request->fecha_corte);
        $nivelDetalle = $request->nivel_detalle;
        $mostrarCeros = $request->boolean('mostrar_ceros', false);

        // Obtener datos del balance
        $balance = $this->calcularBalance($fechaCorte, $nivelDetalle, $mostrarCeros);

        return response()->json([
            'success' => true,
            'balance' => $balance,
            'fecha_corte' => $fechaCorte->format('d/m/Y')
        ]);
    }

    /**
     * Exportar Balance General a PDF
     */
    public function exportarPdf(Request $request)
    {
        $fechaCorte = Carbon::parse($request->fecha_corte);
        $nivelDetalle = $request->nivel_detalle ?? 4;
        $mostrarCeros = $request->boolean('mostrar_ceros', false);

        $balance = $this->calcularBalance($fechaCorte, $nivelDetalle, $mostrarCeros);

        $pdf = \PDF::loadView('contabilidad.balance-general.pdf', compact('balance', 'fechaCorte'));
        
        return $pdf->download('Balance_General_' . $fechaCorte->format('Y-m-d') . '.pdf');
    }

    /**
     * Calcular Balance General
     */
    private function calcularBalance(Carbon $fechaCorte, int $nivelDetalle, bool $mostrarCeros): array
    {
        // Obtener cuentas según el nivel de detalle específico
        $cuentas = $this->obtenerCuentasPorNivel($nivelDetalle, $mostrarCeros, $fechaCorte);

        $balance = [
            'activos' => [],
            'pasivos' => [],
            'patrimonio' => [],
            'totales' => [
                'total_activos' => 0,
                'total_pasivos' => 0,
                'total_patrimonio' => 0,
            ]
        ];

        foreach ($cuentas as $cuenta) {
            $saldo = $this->calcularSaldoCuenta($cuenta, $fechaCorte);
            
            // Solo filtrar cuentas con saldo cero si se especifica explícitamente
            // Para el balance, siempre mostrar cuentas con movimientos o subcuentas
            if (!$mostrarCeros && $saldo == 0 && !$cuenta->movimientos()->exists() && !$this->tieneSubcuentasConMovimientos($cuenta)) {
                continue;
            }

            $cuentaData = [
                'codigo' => $cuenta->codigo,
                'nombre' => $cuenta->nombre,
                'nivel' => $cuenta->nivel,
                'saldo' => $saldo,
                'saldo_formateado' => number_format(abs($saldo), 2, ',', '.')
            ];

            // Clasificar según la clase de cuenta o el código si no tiene clase
            $clase = $cuenta->clase ?: substr($cuenta->codigo, 0, 1);
            
            switch ($clase) {
                case '1': // Activos
                    $balance['activos'][] = $cuentaData;
                    $balance['totales']['total_activos'] += $saldo;
                    break;
                    
                case '2': // Pasivos
                    $balance['pasivos'][] = $cuentaData;
                    $balance['totales']['total_pasivos'] += abs($saldo);
                    break;
                    
                case '3': // Patrimonio
                    $balance['patrimonio'][] = $cuentaData;
                    $balance['totales']['total_patrimonio'] += abs($saldo);
                    break;
            }
        }

        // Calcular totales finales
        $balance['totales']['total_pasivo_patrimonio'] = $balance['totales']['total_pasivos'] + $balance['totales']['total_patrimonio'];

        // Formatear totales para mostrar
        $balance['totales']['total_activos_formateado'] = number_format($balance['totales']['total_activos'], 2, ',', '.');
        $balance['totales']['total_pasivos_formateado'] = number_format($balance['totales']['total_pasivos'], 2, ',', '.');
        $balance['totales']['total_patrimonio_formateado'] = number_format($balance['totales']['total_patrimonio'], 2, ',', '.');
        $balance['totales']['total_pasivo_patrimonio_formateado'] = number_format($balance['totales']['total_pasivo_patrimonio'], 2, ',', '.');

        return $balance;
    }

    /**
     * Obtener cuentas según el nivel de detalle
     */
    private function obtenerCuentasPorNivel(int $nivelDetalle, bool $mostrarCeros, Carbon $fechaCorte)
    {
        $query = PlanCuenta::where('estado', true)
                          ->where(function($query) {
                              $query->whereIn('clase', ['1', '2', '3'])
                                    ->orWhere('codigo', 'LIKE', '1%')
                                    ->orWhere('codigo', 'LIKE', '2%')
                                    ->orWhere('codigo', 'LIKE', '3%');
                          });

        switch ($nivelDetalle) {
            case 1: // Solo clases (1, 2, 3)
                $cuentas = $query->where('nivel', 1)->orderBy('codigo')->get();
                break;
                
            case 2: // Grupos (11, 12, 21, 31, etc.)
                $cuentas = $query->where('nivel', '<=', 2)->orderBy('codigo')->get();
                break;
                
            case 3: // Cuentas (1105, 1110, 2105, etc.)
                $cuentas = $query->where('nivel', '<=', 3)->orderBy('codigo')->get();
                break;
                
            case 4: // Subcuentas (110501, 111001, etc.)
            default:
                $cuentas = $query->where('nivel', '<=', 4)->orderBy('codigo')->get();
                break;
        }

        // Si el nivel es mayor a 1, agregar cuentas padre que tengan hijos con movimientos
        if ($nivelDetalle > 1) {
            $cuentasConMovimientos = $this->agregarCuentasPadre($cuentas, $nivelDetalle, $fechaCorte);
            $cuentas = $cuentas->merge($cuentasConMovimientos)->unique('id')->sortBy('codigo');
        }

        // Filtrar por saldos si es necesario
        if (!$mostrarCeros) {
            $cuentas = $cuentas->filter(function($cuenta) use ($fechaCorte) {
                $saldo = $this->calcularSaldoCuenta($cuenta, $fechaCorte);
                return $saldo != 0 || $cuenta->movimientos()->exists() || $this->tieneSubcuentasConMovimientos($cuenta);
            });
        }

        return $cuentas;
    }

    /**
     * Agregar cuentas padre que tengan hijos con movimientos
     */
    private function agregarCuentasPadre($cuentas, int $nivelDetalle, Carbon $fechaCorte)
    {
        $cuentasAdicionales = collect();
        
        // Obtener todas las cuentas con movimientos
        $cuentasConMovimientos = PlanCuenta::whereHas('movimientos')
                                          ->where('estado', true)
                                          ->where(function($query) {
                                              $query->whereIn('clase', ['1', '2', '3'])
                                                    ->orWhere('codigo', 'LIKE', '1%')
                                                    ->orWhere('codigo', 'LIKE', '2%')
                                                    ->orWhere('codigo', 'LIKE', '3%');
                                          })
                                          ->get();

        foreach ($cuentasConMovimientos as $cuenta) {
            // Agregar cuentas padre hasta el nivel solicitado
            $codigoCuenta = $cuenta->codigo;
            
            for ($nivel = $cuenta->nivel - 1; $nivel >= 1 && $nivel <= $nivelDetalle; $nivel--) {
                $codigoPadre = substr($codigoCuenta, 0, $nivel);
                
                $cuentaPadre = PlanCuenta::where('codigo', $codigoPadre)
                                        ->where('nivel', $nivel)
                                        ->where('estado', true)
                                        ->first();
                
                if ($cuentaPadre && !$cuentas->contains('id', $cuentaPadre->id)) {
                    $cuentasAdicionales->push($cuentaPadre);
                }
            }
        }

        return $cuentasAdicionales;
    }

    /**
     * Calcular saldo de una cuenta específica
     */
    private function calcularSaldoCuenta(PlanCuenta $cuenta, Carbon $fechaCorte): float
    {
        // Si la cuenta tiene movimientos directos, usar su saldo
        if ($cuenta->movimientos()->exists()) {
            return $cuenta->getSaldo(null, $fechaCorte->format('Y-m-d'));
        }

        // Si es cuenta padre, sumar saldos de subcuentas
        $saldoTotal = 0;
        $subcuentas = PlanCuenta::where('codigo', 'LIKE', $cuenta->codigo . '%')
                               ->where('codigo', '!=', $cuenta->codigo)
                               ->where('estado', true)
                               ->get();

        foreach ($subcuentas as $subcuenta) {
            $saldoTotal += $subcuenta->getSaldo(null, $fechaCorte->format('Y-m-d'));
        }

        return $saldoTotal;
    }

    /**
     * Verificar si una cuenta tiene subcuentas con movimientos
     */
    private function tieneSubcuentasConMovimientos(PlanCuenta $cuenta): bool
    {
        return PlanCuenta::where('codigo', 'LIKE', $cuenta->codigo . '%')
                        ->where('codigo', '!=', $cuenta->codigo)
                        ->where('estado', true)
                        ->whereHas('movimientos')
                        ->exists();
    }

    /**
     * Obtener detalle de movimientos de una cuenta
     */
    public function detalleCuenta(Request $request)
    {
        $cuentaId = $request->cuenta_id;
        $fechaCorte = Carbon::parse($request->fecha_corte);

        $cuenta = PlanCuenta::findOrFail($cuentaId);
        
        $movimientos = MovimientoContable::where('cuenta_id', $cuentaId)
                                       ->where('fecha', '<=', $fechaCorte)
                                       ->with(['comprobante'])
                                       ->orderBy('fecha', 'desc')
                                       ->limit(100)
                                       ->get();

        return response()->json([
            'success' => true,
            'cuenta' => $cuenta,
            'movimientos' => $movimientos,
            'saldo_total' => $cuenta->getSaldo(null, $fechaCorte->format('Y-m-d'))
        ]);
    }

    /**
     * Comparativo de balances por períodos
     */
    public function comparativo(Request $request)
    {
        $request->validate([
            'fecha_inicial' => 'required|date',
            'fecha_final' => 'required|date|after:fecha_inicial',
            'periodicidad' => 'required|in:mensual,trimestral,anual'
        ]);

        $fechaInicial = Carbon::parse($request->fecha_inicial);
        $fechaFinal = Carbon::parse($request->fecha_final);
        $periodicidad = $request->periodicidad;

        $periodos = $this->generarPeriodos($fechaInicial, $fechaFinal, $periodicidad);
        $comparativo = [];

        foreach ($periodos as $periodo) {
            $balance = $this->calcularBalance($periodo['fecha_fin'], 4, false);
            $comparativo[] = [
                'periodo' => $periodo['nombre'],
                'fecha_fin' => $periodo['fecha_fin'],
                'totales' => $balance['totales']
            ];
        }

        return response()->json([
            'success' => true,
            'comparativo' => $comparativo
        ]);
    }

    /**
     * Generar períodos para comparativo
     */
    private function generarPeriodos(Carbon $fechaInicial, Carbon $fechaFinal, string $periodicidad): array
    {
        $periodos = [];
        $fechaActual = $fechaInicial->copy();

        while ($fechaActual <= $fechaFinal) {
            $fechaFinPeriodo = $fechaActual->copy();

            switch ($periodicidad) {
                case 'mensual':
                    $fechaFinPeriodo->endOfMonth();
                    $nombre = $fechaActual->format('M Y');
                    $fechaActual->addMonth();
                    break;
                    
                case 'trimestral':
                    $fechaFinPeriodo->addMonths(2)->endOfMonth();
                    $nombre = 'Q' . $fechaActual->quarter . ' ' . $fechaActual->year;
                    $fechaActual->addMonths(3);
                    break;
                    
                case 'anual':
                    $fechaFinPeriodo->endOfYear();
                    $nombre = $fechaActual->year;
                    $fechaActual->addYear();
                    break;
            }

            if ($fechaFinPeriodo > $fechaFinal) {
                $fechaFinPeriodo = $fechaFinal;
            }

            $periodos[] = [
                'nombre' => $nombre,
                'fecha_inicio' => $fechaActual->copy()->startOfMonth(),
                'fecha_fin' => $fechaFinPeriodo
            ];

            if ($fechaFinPeriodo >= $fechaFinal) {
                break;
            }
        }

        return $periodos;
    }
}
