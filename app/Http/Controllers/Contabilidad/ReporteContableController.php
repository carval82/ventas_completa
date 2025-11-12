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
use App\Services\ContabilidadQueryService;
use App\Services\RetencionesQueryService;
use App\Exports\ReporteFiscalIvaExport;
use App\Exports\ReporteFiscalRetencionesExport;
use Maatwebsite\Excel\Facades\Excel;

class ReporteContableController extends Controller
{
    protected $contabilidadQueryService;
    
    public function __construct(ContabilidadQueryService $contabilidadQueryService)
    {
        $this->contabilidadQueryService = $contabilidadQueryService;
    }
    
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
        // Usar el servicio optimizado para obtener los saldos
        return PlanCuenta::where('tipo', $tipo)
            ->where('estado', true)
            ->get()
            ->map(function ($cuenta) use ($fechaCorte) {
                // Obtener saldo usando el servicio optimizado
                $saldo = $this->contabilidadQueryService->obtenerSaldoCuenta($cuenta->id, $fechaCorte);

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
        // Obtener movimientos del período usando el servicio optimizado
        $fechaDesdeObj = Carbon::parse($fechaDesde)->startOfDay();
        $fechaHastaObj = Carbon::parse($fechaHasta)->endOfDay();
        
        return PlanCuenta::where('tipo', $tipo)
            ->where('estado', true)
            ->get()
            ->map(function ($cuenta) use ($fechaDesde, $fechaHasta, $fechaDesdeObj, $fechaHastaObj) {
                // Obtener movimientos de la cuenta en el período
                $movimientos = $this->contabilidadQueryService->obtenerMovimientosCuenta(
                    $cuenta->id, 
                    $fechaDesdeObj->format('Y-m-d'), 
                    $fechaHastaObj->format('Y-m-d')
                );
                
                // Calcular saldo
                $debitos = collect($movimientos)->sum('debito');
                $creditos = collect($movimientos)->sum('credito');
                $saldo = $debitos - $creditos;

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

    /**
     * Genera un reporte fiscal de IVA para un período específico
     * Utiliza el servicio optimizado de consultas
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function reporte_fiscal_iva(Request $request)
    {
        // Validación opcional - si no se envían fechas, usar valores por defecto
        $request->validate([
            'fecha_inicio' => 'nullable|date_format:Y-m-d',
            'fecha_fin' => 'nullable|date_format:Y-m-d|after_or_equal:fecha_inicio'
        ]);

        $fechaInicio = $request->input('fecha_inicio', date('Y-m-01'));
        $fechaFin = $request->input('fecha_fin', date('Y-m-d'));
        
        $queryService = new ContabilidadQueryService();
        $reporte = $queryService->generarReporteFiscalIva($fechaInicio, $fechaFin);
        
        // Si se solicita exportar a Excel/PDF
        if ($request->has('export')) {
            // Preparar las variables para la vista
            $resumenVentas = [
                'gravadas' => $reporte['ventas']['gravadas']['detalle'] ?? [],
                'totales' => [
                    'subtotal' => $reporte['ventas']['gravadas']['total'] - $reporte['ventas']['gravadas']['iva'],
                    'iva' => $reporte['ventas']['gravadas']['iva'],
                    'total' => $reporte['ventas']['gravadas']['total'] + $reporte['ventas']['excluidas']['total'] + $reporte['ventas']['exentas']['total']
                ]
            ];
            
            $resumenCompras = [
                'gravadas' => $reporte['compras']['gravadas']['detalle'] ?? [],
                'totales' => [
                    'subtotal' => $reporte['compras']['gravadas']['total'] - $reporte['compras']['gravadas']['iva'],
                    'iva' => $reporte['compras']['gravadas']['iva'],
                    'total' => $reporte['compras']['gravadas']['total'] + $reporte['compras']['excluidas']['total'] + $reporte['compras']['exentas']['total']
                ]
            ];
            
            $saldoPagar = $reporte['ventas']['gravadas']['iva'] - $reporte['compras']['gravadas']['iva'];
            $saldoFavor = $saldoPagar < 0 ? abs($saldoPagar) : 0;
            
            if ($request->export == 'pdf') {
                // Generar PDF
                $html = view('contabilidad.reportes.fiscal_iva_pdf', [
                    'resumenVentas' => $resumenVentas,
                    'resumenCompras' => $resumenCompras,
                    'saldoPagar' => $saldoPagar,
                    'saldoFavor' => $saldoFavor,
                    'fechaInicio' => $fechaInicio,
                    'fechaFin' => $fechaFin
                ])->render();
                
                // Crear archivo PDF usando DOMPDF
                $dompdf = new \Dompdf\Dompdf();
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                
                // Generar nombre de archivo
                $filename = 'reporte_fiscal_iva_' . date('Ymd', strtotime($fechaInicio)) . '_' . date('Ymd', strtotime($fechaFin)) . '.pdf';
                
                // Descargar PDF
                return $dompdf->stream($filename, ['Attachment' => true]);
            } else {
                // Exportar a CSV como alternativa
                $filename = 'reporte_fiscal_iva_' . date('Ymd', strtotime($fechaInicio)) . '_' . date('Ymd', strtotime($fechaFin)) . '.csv';
                $headers = [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                ];
                
                $callback = function() use ($reporte, $fechaInicio, $fechaFin) {
                    $file = fopen('php://output', 'w');
                    
                    // Encabezado del archivo
                    fputcsv($file, ['REPORTE FISCAL DE IVA']);
                    fputcsv($file, ['Período: ' . date('d/m/Y', strtotime($fechaInicio)) . ' - ' . date('d/m/Y', strtotime($fechaFin))]);
                    fputcsv($file, []);
                    
                    // Resumen de ventas
                    fputcsv($file, ['RESUMEN DE VENTAS']);
                    fputcsv($file, ['Fecha', 'Factura', 'Cliente', 'Documento', 'Base', 'IVA', 'Total']);
                    
                    foreach ($reporte['ventas']['gravadas']['detalle'] as $venta) {
                        fputcsv($file, [
                            date('d/m/Y', strtotime($venta['fecha'])),
                            $venta['numero'],
                            $venta['cliente'],
                            $venta['documento'],
                            number_format($venta['subtotal'], 2),
                            number_format($venta['iva'], 2),
                            number_format($venta['total'], 2)
                        ]);
                    }
                    
                    fputcsv($file, ['TOTALES', '', '', '', 
                        number_format($reporte['ventas']['gravadas']['total'] - $reporte['ventas']['gravadas']['iva'], 2),
                        number_format($reporte['ventas']['gravadas']['iva'], 2),
                        number_format($reporte['ventas']['gravadas']['total'], 2)
                    ]);
                    fputcsv($file, []);
                    
                    // Resumen de compras
                    fputcsv($file, ['RESUMEN DE COMPRAS']);
                    fputcsv($file, ['Fecha', 'Factura', 'Proveedor', 'NIT', 'Base', 'IVA', 'Total']);
                    
                    foreach ($reporte['compras']['gravadas']['detalle'] as $compra) {
                        fputcsv($file, [
                            date('d/m/Y', strtotime($compra['fecha'])),
                            $compra['numero'],
                            $compra['proveedor'],
                            $compra['nit'],
                            number_format($compra['subtotal'], 2),
                            number_format($compra['iva'], 2),
                            number_format($compra['total'], 2)
                        ]);
                    }
                    
                    fputcsv($file, ['TOTALES', '', '', '', 
                        number_format($reporte['compras']['gravadas']['total'] - $reporte['compras']['gravadas']['iva'], 2),
                        number_format($reporte['compras']['gravadas']['iva'], 2),
                        number_format($reporte['compras']['gravadas']['total'], 2)
                    ]);
                    fputcsv($file, []);
                    
                    // Resumen final
                    fputcsv($file, ['RESUMEN FISCAL']);
                    fputcsv($file, ['IVA Generado en Ventas', number_format($reporte['ventas']['gravadas']['iva'], 2)]);
                    fputcsv($file, ['IVA Descontable en Compras', number_format($reporte['compras']['gravadas']['iva'], 2)]);
                    
                    $saldoPagar = $reporte['ventas']['gravadas']['iva'] - $reporte['compras']['gravadas']['iva'];
                    if ($saldoPagar > 0) {
                        fputcsv($file, ['SALDO A PAGAR', number_format($saldoPagar, 2)]);
                    } else {
                        fputcsv($file, ['SALDO A FAVOR', number_format(abs($saldoPagar), 2)]);
                    }
                    
                    fclose($file);
                };
                
                return response()->stream($callback, 200, $headers);
            }
        }
        
        // Preparar las variables para la vista
        $resumenVentas = [
            'totales' => [
                'subtotal' => $reporte['ventas']['gravadas']['total'] - $reporte['ventas']['gravadas']['iva'],
                'iva' => $reporte['ventas']['gravadas']['iva'],
                'total' => $reporte['ventas']['gravadas']['total'] + $reporte['ventas']['excluidas']['total'] + $reporte['ventas']['exentas']['total']
            ],
            'gravadas' => $reporte['ventas']['gravadas']['detalle'],
            'excluidas' => $reporte['ventas']['excluidas']['detalle'],
            'exentas' => $reporte['ventas']['exentas']['detalle']
        ];
        
        $resumenCompras = [
            'totales' => [
                'subtotal' => $reporte['compras']['gravadas']['total'] - $reporte['compras']['gravadas']['iva'],
                'iva' => $reporte['compras']['gravadas']['iva'],
                'total' => $reporte['compras']['gravadas']['total'] + $reporte['compras']['excluidas']['total'] + $reporte['compras']['exentas']['total']
            ],
            'gravadas' => $reporte['compras']['gravadas']['detalle'],
            'excluidas' => $reporte['compras']['excluidas']['detalle'],
            'exentas' => $reporte['compras']['exentas']['detalle']
        ];
        
        $saldoPagar = $reporte['resumen']['saldo_a_pagar'];
        $saldoFavor = $reporte['resumen']['saldo_a_favor'];
        
        return view('contabilidad.reportes.fiscal_iva', [
            'reporte' => $reporte,
            'resumenVentas' => $resumenVentas,
            'resumenCompras' => $resumenCompras,
            'saldoPagar' => $saldoPagar,
            'saldoFavor' => $saldoFavor,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin
        ]);
    }
    
    /**
     * Genera un reporte fiscal de retenciones
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function reporte_fiscal_retenciones(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio', date('Y-m-01'));
        $fechaFin = $request->input('fecha_fin', date('Y-m-d'));
        
        // Generar reporte básico de retenciones desde movimientos contables
        $reporte = $this->generarReporteRetencionesBasico($fechaInicio, $fechaFin);
        
        return view('contabilidad.reportes.fiscal_retenciones', [
            'reporte' => $reporte,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin
        ]);
    }
    
    /**
     * Generar reporte básico de retenciones desde movimientos contables
     */
    private function generarReporteRetencionesBasico($fechaInicio, $fechaFin)
    {
        // Buscar movimientos en cuentas de retenciones (código 2365, 2367, etc.)
        $movimientosRetenciones = \App\Models\MovimientoContable::whereHas('cuenta', function($query) {
                $query->where('codigo', 'LIKE', '236%') // Cuentas de retenciones
                      ->orWhere('codigo', 'LIKE', '2365%') // Retención en la fuente
                      ->orWhere('codigo', 'LIKE', '2367%'); // Retención de IVA
            })
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->with(['cuenta', 'comprobante'])
            ->orderBy('fecha')
            ->get();
            
        $resumen = [
            'total_retenciones_fuente' => 0,
            'total_retenciones_iva' => 0,
            'cantidad_movimientos' => $movimientosRetenciones->count(),
            'movimientos' => $movimientosRetenciones,
            // Campos adicionales que espera la vista
            'total_efectuadas' => 0,
            'saldo_a_pagar' => 0,
            'total_retenciones' => 0,
            'retenciones_por_tipo' => []
        ];
        
        foreach ($movimientosRetenciones as $movimiento) {
            if (str_contains($movimiento->cuenta->codigo, '2365')) {
                $resumen['total_retenciones_fuente'] += $movimiento->credito;
            } elseif (str_contains($movimiento->cuenta->codigo, '2367')) {
                $resumen['total_retenciones_iva'] += $movimiento->credito;
            }
        }
        
        // Calcular totales
        $resumen['total_retenciones'] = $resumen['total_retenciones_fuente'] + $resumen['total_retenciones_iva'];
        $resumen['total_efectuadas'] = $resumen['total_retenciones'];
        $resumen['saldo_a_pagar'] = $resumen['total_retenciones']; // Simplificado
        
        // Agrupar por tipo para la vista
        $resumen['retenciones_por_tipo'] = [
            'Retención en la Fuente' => [
                'total' => $resumen['total_retenciones_fuente'],
                'movimientos' => $movimientosRetenciones->filter(function($mov) {
                    return str_contains($mov->cuenta->codigo, '2365');
                })
            ],
            'Retención de IVA' => [
                'total' => $resumen['total_retenciones_iva'],
                'movimientos' => $movimientosRetenciones->filter(function($mov) {
                    return str_contains($mov->cuenta->codigo, '2367');
                })
            ]
        ];
        
        return $resumen;
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
        // Verificar si el tipo existe en el array, si no, inicializarlo
        if (!isset($totales['por_tipo'][$comprobante->tipo])) {
            $totales['por_tipo'][$comprobante->tipo] = [
                'cantidad' => 0,
                'debitos' => 0,
                'creditos' => 0
            ];
        }
        
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