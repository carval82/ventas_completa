<?php

namespace App\Http\Controllers\Contabilidad;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\PlanCuenta;
use App\Models\MovimientoContable;
use App\Models\Comprobante;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReporteContableController extends Controller
{
    public function index()
    {
        $cuentas = PlanCuenta::orderBy('codigo')->get();
        return view('contabilidad.reportes.index', compact('cuentas'));
    }


    public function balance_general(Request $request)
{
    $fecha_corte = $request->fecha_corte ? Carbon::parse($request->fecha_corte) : Carbon::now();
    $nivel = $request->get('nivel', 1);

    // Obtener Activos
    $activos = PlanCuenta::where('tipo', 'Activo')
        ->where('nivel', '<=', $nivel)
        ->orderBy('codigo')
        ->get()
        ->map(function($cuenta) use ($fecha_corte) {
            $saldo = MovimientoContable::where('cuenta_id', $cuenta->id)
                ->whereDate('fecha', '<=', $fecha_corte)
                ->whereHas('comprobante', function($q) {
                    $q->where('estado', 'Aprobado');
                })
                ->selectRaw('SUM(debito - credito) as saldo')
                ->value('saldo') ?? 0;

            return [
                'id' => $cuenta->id,
                'codigo' => $cuenta->codigo,
                'nombre' => $cuenta->nombre,
                'nivel' => $cuenta->nivel,
                'saldo' => $saldo,
                'es_total' => $cuenta->subcuentas()->count() > 0
            ];
        })->filter(function($cuenta) {
            return $cuenta['saldo'] != 0;
        });

    // Obtener Pasivos
    $pasivos = PlanCuenta::where('tipo', 'Pasivo')
        ->where('nivel', '<=', $nivel)
        ->orderBy('codigo')
        ->get()
        ->map(function($cuenta) use ($fecha_corte) {
            $saldo = MovimientoContable::where('cuenta_id', $cuenta->id)
                ->whereDate('fecha', '<=', $fecha_corte)
                ->whereHas('comprobante', function($q) {
                    $q->where('estado', 'Aprobado');
                })
                ->selectRaw('SUM(credito - debito) as saldo')
                ->value('saldo') ?? 0;

            return [
                'id' => $cuenta->id,
                'codigo' => $cuenta->codigo,
                'nombre' => $cuenta->nombre,
                'nivel' => $cuenta->nivel,
                'saldo' => $saldo,
                'es_total' => $cuenta->subcuentas()->count() > 0
            ];
        })->filter(function($cuenta) {
            return $cuenta['saldo'] != 0;
        });

    // Obtener Patrimonio
    $patrimonio = PlanCuenta::where('tipo', 'Patrimonio')
        ->where('nivel', '<=', $nivel)
        ->orderBy('codigo')
        ->get()
        ->map(function($cuenta) use ($fecha_corte) {
            $saldo = MovimientoContable::where('cuenta_id', $cuenta->id)
                ->whereDate('fecha', '<=', $fecha_corte)
                ->whereHas('comprobante', function($q) {
                    $q->where('estado', 'Aprobado');
                })
                ->selectRaw('SUM(credito - debito) as saldo')
                ->value('saldo') ?? 0;

            return [
                'id' => $cuenta->id,
                'codigo' => $cuenta->codigo,
                'nombre' => $cuenta->nombre,
                'nivel' => $cuenta->nivel,
                'saldo' => $saldo,
                'es_total' => $cuenta->subcuentas()->count() > 0
            ];
        })->filter(function($cuenta) {
            return $cuenta['saldo'] != 0;
        });

    // Calcular totales
    $totales = [
        'activos' => $activos->sum('saldo'),
        'pasivos' => $pasivos->sum('saldo'),
        'patrimonio' => $patrimonio->sum('saldo')
    ];

    return view('contabilidad.reportes.balance_general', compact(
        'fecha_corte',
        'nivel',
        'activos',
        'pasivos',
        'patrimonio',
        'totales'
    ));
}
    
    private function getCuentasPorTipo($tipo, $fechaCorte)
    {
        return PlanCuenta::where('tipo', $tipo)
            ->where('estado', true)
            ->get()
            ->map(function ($cuenta) use ($fechaCorte) {
                $saldo = MovimientoContable::where('cuenta_id', $cuenta->id)
                    ->where('fecha', '<=', $fechaCorte)
                    ->whereHas('comprobante', function($q) {
                        $q->where('estado', 'Aprobado');
                    })
                    ->selectRaw('SUM(debito) - SUM(credito) as saldo')
                    ->value('saldo') ?? 0;

                // Ajustar saldo según tipo de cuenta
                if (in_array($cuenta->tipo, ['Pasivo', 'Patrimonio'])) {
                    $saldo *= -1;
                }

                return [
                    'codigo' => $cuenta->codigo,
                    'nombre' => $cuenta->nombre,
                    'saldo' => $saldo
                ];
            })
            ->filter(function ($cuenta) {
                return $cuenta['saldo'] != 0;
            })
            ->sortBy('codigo')
            ->values();
    }

    private function getCuentasResultado($tipo, $fechaDesde, $fechaHasta)
    {
        return PlanCuenta::where('tipo', $tipo)
            ->where('estado', true)
            ->get()
            ->map(function ($cuenta) use ($fechaDesde, $fechaHasta) {
                $saldo = MovimientoContable::where('cuenta_id', $cuenta->id)
                    ->whereBetween('fecha', [$fechaDesde, $fechaHasta])
                    ->whereHas('comprobante', function($q) {
                        $q->where('estado', 'Aprobado');
                    })
                    ->selectRaw('SUM(debito) - SUM(credito) as saldo')
                    ->value('saldo') ?? 0;

                // Ajustar saldo para ingresos
                if ($cuenta->tipo == 'Ingreso') {
                    $saldo *= -1;
                }

                return [
                    'codigo' => $cuenta->codigo,
                    'nombre' => $cuenta->nombre,
                    'saldo' => $saldo
                ];
            })
            ->filter(function ($cuenta) {
                return $cuenta['saldo'] != 0;
            })
            ->sortBy('codigo')
            ->values();
    }

    public function cierre_mensual(Request $request)
    {
        $request->validate([
            'mes' => 'required|date_format:Y-m'
        ]);

        $fecha = Carbon::createFromFormat('Y-m', $request->mes);
        $fechaInicio = $fecha->copy()->startOfMonth();
        $fechaFin = $fecha->copy()->endOfMonth();

        // Verificar si hay comprobantes pendientes
        $pendientes = Comprobante::whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->where('estado', 'Borrador')
            ->count();

        if ($pendientes > 0) {
            return back()->with('error', 'Hay comprobantes pendientes de aprobar en el período');
        }

        try {
            DB::beginTransaction();

            // Obtener totales de resultados
            $ingresos = $this->getCuentasResultado('Ingreso', $fechaInicio, $fechaFin);
            $gastos = $this->getCuentasResultado('Gasto', $fechaInicio, $fechaFin);
            $utilidad = $ingresos->sum('saldo') - $gastos->sum('saldo');

            // Crear comprobante de cierre
            $comprobante = Comprobante::create([
                'fecha' => $fechaFin,
                'tipo' => 'Diario',
                'descripcion' => 'Cierre mensual ' . $fecha->format('F Y'),
                'estado' => 'Aprobado',
                'created_by' => Auth::user()->id,
                'approved_by' => Auth::user()->id,
            ]);

            // Registrar movimientos de cierre
            // ... (aquí iría la lógica específica de tu empresa para el cierre)

            DB::commit();
            return back()->with('success', 'Cierre mensual realizado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al realizar el cierre: ' . $e->getMessage());
        }
    }
    public function libro_diario(Request $request)
{
    $request->validate([
        'fecha_desde' => 'nullable|date',
        'fecha_hasta' => 'nullable|date',
        'tipo' => 'nullable|in:Ingreso,Egreso,Diario'
    ]);

    $fecha_desde = $request->fecha_desde ? Carbon::parse($request->fecha_desde) : Carbon::now()->startOfMonth();
    $fecha_hasta = $request->fecha_hasta ? Carbon::parse($request->fecha_hasta) : Carbon::now();

    // Obtener comprobantes con sus movimientos
    $comprobantes = Comprobante::with(['movimientos' => function($query) {
            $query->with('cuenta')->orderBy('id');
        }, 'creadoPor', 'aprobadoPor'])
        ->whereBetween('fecha', [$fecha_desde, $fecha_hasta])
        ->when($request->filled('tipo'), function($query) use ($request) {
            return $query->where('tipo', $request->tipo);
        })
        ->when(!$request->incluir_anulados, function($query) {
            return $query->where('estado', 'Aprobado');
        })
        ->orderBy('fecha')
        ->orderBy('numero')
        ->get();

    // Calcular totales por tipo de comprobante
    $totales = [
        'por_tipo' => [
            'Ingreso' => ['cantidad' => 0, 'debitos' => 0, 'creditos' => 0],
            'Egreso' => ['cantidad' => 0, 'debitos' => 0, 'creditos' => 0],
            'Diario' => ['cantidad' => 0, 'debitos' => 0, 'creditos' => 0]
        ],
        'general' => [
            'cantidad' => 0,
            'debitos' => 0,
            'creditos' => 0
        ]
    ];

    foreach ($comprobantes as $comprobante) {
        $totales['por_tipo'][$comprobante->tipo]['cantidad']++;
        $totales['por_tipo'][$comprobante->tipo]['debitos'] += $comprobante->total_debito;
        $totales['por_tipo'][$comprobante->tipo]['creditos'] += $comprobante->total_credito;

        $totales['general']['cantidad']++;
        $totales['general']['debitos'] += $comprobante->total_debito;
        $totales['general']['creditos'] += $comprobante->total_credito;
    }

    // Remover tipos sin movimientos
    $totales['por_tipo'] = array_filter($totales['por_tipo'], function($total) {
        return $total['cantidad'] > 0;
    });

    // Obtener datos para los filtros
    $tipos_comprobante = [
        'Ingreso' => 'Comprobantes de Ingreso',
        'Egreso' => 'Comprobantes de Egreso',
        'Diario' => 'Comprobantes de Diario'
    ];

    return view('contabilidad.reportes.libro_diario', compact(
        'comprobantes',
        'totales',
        'tipos_comprobante',
        'fecha_desde',
        'fecha_hasta'
    ));
}
public function dashboard()
{
    $fecha_actual = Carbon::now();
    $primer_dia_mes = $fecha_actual->copy()->startOfMonth();
    
    // Estadísticas de comprobantes del mes
    $stats_comprobantes = [
        'total' => Comprobante::whereBetween('fecha', [$primer_dia_mes, $fecha_actual])->count(),
        'por_tipo' => Comprobante::whereBetween('fecha', [$primer_dia_mes, $fecha_actual])
            ->selectRaw('tipo, COUNT(*) as total, SUM(total_debito) as monto')
            ->groupBy('tipo')
            ->get(),
        'por_estado' => Comprobante::whereBetween('fecha', [$primer_dia_mes, $fecha_actual])
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->get()
    ];

    // Movimientos por tipo de cuenta
    $movimientos_por_tipo = PlanCuenta::withSum(['movimientos' => function($query) use ($primer_dia_mes, $fecha_actual) {
        $query->whereBetween('fecha', [$primer_dia_mes, $fecha_actual]);
    }], 'debito')
        ->withSum(['movimientos' => function($query) use ($primer_dia_mes, $fecha_actual) {
            $query->whereBetween('fecha', [$primer_dia_mes, $fecha_actual]);
        }], 'credito')
        ->selectRaw('tipo, COUNT(*) as total_cuentas')
        ->groupBy('tipo')
        ->get();

    // Últimos comprobantes
    $ultimos_comprobantes = Comprobante::with(['creadoPor', 'aprobadoPor'])
        ->latest('fecha')
        ->take(5)
        ->get();

    // Cuentas más utilizadas
    $cuentas_frecuentes = PlanCuenta::withCount(['movimientos' => function($query) use ($primer_dia_mes, $fecha_actual) {
        $query->whereBetween('fecha', [$primer_dia_mes, $fecha_actual]);
    }])
        ->orderByDesc('movimientos_count')
        ->take(5)
        ->get();

    return view('contabilidad.dashboard', compact(
        'stats_comprobantes',
        'movimientos_por_tipo',
        'ultimos_comprobantes',
        'cuentas_frecuentes'
    ));
}

public function libro_mayor(Request $request) {
    $request->validate([
        'cuenta_id' => 'nullable|exists:plan_cuentas,id',
        'fecha_desde' => 'nullable|date',
        'fecha_hasta' => 'nullable|date'
    ]);

    $fecha_desde = $request->fecha_desde ? Carbon::parse($request->fecha_desde) : Carbon::now()->startOfMonth();
    $fecha_hasta = $request->fecha_hasta ? Carbon::parse($request->fecha_hasta) : Carbon::now();
    $cuentas = PlanCuenta::orderBy('codigo')->get();
    
    // Inicializar variables
    $cuenta = null;
    $movimientos = collect();
    $saldo_anterior = 0;
    $totales = [
        'debitos' => 0,
        'creditos' => 0,
        'saldo_inicial' => 0,
        'saldo_final' => 0
    ];

    if ($request->cuenta_id) {
        $cuenta = PlanCuenta::findOrFail($request->cuenta_id);
        
        // Calcular saldo anterior
        $saldo_anterior = MovimientoContable::where('cuenta_id', $cuenta->id)
            ->whereDate('fecha', '<', $fecha_desde)
            ->whereHas('comprobante', function($q) {
                $q->where('estado', 'Aprobado');
            })
            ->selectRaw('SUM(debito - credito) as saldo')
            ->value('saldo') ?? 0;

        // Obtener movimientos del período
        $movimientos = MovimientoContable::with(['comprobante'])
            ->where('cuenta_id', $cuenta->id)
            ->whereBetween('fecha', [$fecha_desde, $fecha_hasta])
            ->whereHas('comprobante', function($q) {
                $q->where('estado', 'Aprobado');
            })
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        // Calcular totales
        $totales = [
            'debitos' => $movimientos->sum('debito'),
            'creditos' => $movimientos->sum('credito'),
            'saldo_inicial' => $saldo_anterior,
            'saldo_final' => $saldo_anterior + $movimientos->sum('debito') - $movimientos->sum('credito')
        ];

        // Calcular saldos progresivos
        $saldo = $saldo_anterior;
        foreach ($movimientos as $movimiento) {
            $saldo += $movimiento->debito - $movimiento->credito;
            $movimiento->saldo = $saldo;
        }
    }

    return view('contabilidad.reportes.libro_mayor', compact(
        'cuenta',
        'cuentas',
        'movimientos',
        'saldo_anterior',
        'totales',
        'fecha_desde',
        'fecha_hasta'
    ));
}

public function estado_resultados(Request $request)
{
    $request->validate([
        'fecha_desde' => 'nullable|date',
        'fecha_hasta' => 'nullable|date',
        'nivel' => 'nullable|integer|min:1|max:5'
    ]);

    $fecha_desde = $request->fecha_desde ? Carbon::parse($request->fecha_desde) : Carbon::now()->startOfMonth();
    $fecha_hasta = $request->fecha_hasta ? Carbon::parse($request->fecha_hasta) : Carbon::now();
    $nivel = $request->get('nivel', 1);

    // Obtener Ingresos
    $ingresos = PlanCuenta::where('tipo', 'Ingreso')
        ->where('nivel', '<=', $nivel)
        ->orderBy('codigo')
        ->get()
        ->map(function($cuenta) use ($fecha_desde, $fecha_hasta) {
            $saldo = MovimientoContable::where('cuenta_id', $cuenta->id)
                ->whereBetween('fecha', [$fecha_desde, $fecha_hasta])
                ->whereHas('comprobante', function($q) {
                    $q->where('estado', 'Aprobado');
                })
                ->selectRaw('SUM(credito - debito) as saldo')
                ->value('saldo') ?? 0;

            return [
                'id' => $cuenta->id,
                'codigo' => $cuenta->codigo,
                'nombre' => $cuenta->nombre,
                'nivel' => $cuenta->nivel,
                'saldo' => $saldo,
                'es_total' => $cuenta->subcuentas()->count() > 0
            ];
        })->filter(function($cuenta) {
            return $cuenta['saldo'] != 0;
        });

    // Obtener Costos
    $costos = PlanCuenta::where('tipo', 'Gasto')
        ->where('codigo', 'LIKE', '6%') // Asumiendo que los costos empiezan con 6
        ->where('nivel', '<=', $nivel)
        ->orderBy('codigo')
        ->get()
        ->map(function($cuenta) use ($fecha_desde, $fecha_hasta) {
            $saldo = MovimientoContable::where('cuenta_id', $cuenta->id)
                ->whereBetween('fecha', [$fecha_desde, $fecha_hasta])
                ->whereHas('comprobante', function($q) {
                    $q->where('estado', 'Aprobado');
                })
                ->selectRaw('SUM(debito - credito) as saldo')
                ->value('saldo') ?? 0;

            return [
                'id' => $cuenta->id,
                'codigo' => $cuenta->codigo,
                'nombre' => $cuenta->nombre,
                'nivel' => $cuenta->nivel,
                'saldo' => $saldo,
                'es_total' => $cuenta->subcuentas()->count() > 0
            ];
        })->filter(function($cuenta) {
            return $cuenta['saldo'] != 0;
        });

    // Obtener Gastos
    $gastos = PlanCuenta::where('tipo', 'Gasto')
        ->where('codigo', 'NOT LIKE', '6%') // Gastos que no son costos
        ->where('nivel', '<=', $nivel)
        ->orderBy('codigo')
        ->get()
        ->map(function($cuenta) use ($fecha_desde, $fecha_hasta) {
            $saldo = MovimientoContable::where('cuenta_id', $cuenta->id)
                ->whereBetween('fecha', [$fecha_desde, $fecha_hasta])
                ->whereHas('comprobante', function($q) {
                    $q->where('estado', 'Aprobado');
                })
                ->selectRaw('SUM(debito - credito) as saldo')
                ->value('saldo') ?? 0;

            return [
                'id' => $cuenta->id,
                'codigo' => $cuenta->codigo,
                'nombre' => $cuenta->nombre,
                'nivel' => $cuenta->nivel,
                'saldo' => $saldo,
                'es_total' => $cuenta->subcuentas()->count() > 0
            ];
        })->filter(function($cuenta) {
            return $cuenta['saldo'] != 0;
        });

    // Calcular totales y utilidad
    $totales = [
        'ingresos' => $ingresos->sum('saldo'),
        'costos' => $costos->sum('saldo'),
        'gastos' => $gastos->sum('saldo'),
        'utilidad_bruta' => $ingresos->sum('saldo') - $costos->sum('saldo'),
        'utilidad_neta' => $ingresos->sum('saldo') - $costos->sum('saldo') - $gastos->sum('saldo')
    ];

    return view('contabilidad.reportes.estado_resultados', compact(
        'fecha_desde',
        'fecha_hasta',
        'nivel',
        'ingresos',
        'costos',
        'gastos',
        'totales'
    ));
}
}