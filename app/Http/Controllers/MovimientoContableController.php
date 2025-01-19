<?php

namespace App\Http\Controllers;

use App\Models\MovimientoContable;
use App\Models\PlanCuenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MovimientoContableController extends Controller
{
    public function index(Request $request)
    {
        $query = MovimientoContable::with(['comprobante', 'cuenta']);

        // Filtros
        if ($request->filled('fecha_desde')) {
            $query->where('fecha', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha', '<=', $request->fecha_hasta);
        }

        if ($request->filled('cuenta_id')) {
            $query->where('cuenta_id', $request->cuenta_id);
        }

        if ($request->filled('tipo_comprobante')) {
            $query->whereHas('comprobante', function($q) use ($request) {
                $q->where('tipo', $request->tipo_comprobante);
            });
        }

        $movimientos = $query->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        $cuentas = PlanCuenta::orderBy('codigo')->get();

        return view('contabilidad.movimientos.index', compact('movimientos', 'cuentas'));
    }

    public function libro_diario(Request $request)
    {
        $query = MovimientoContable::with(['comprobante', 'cuenta'])
            ->whereHas('comprobante', function($q) {
                $q->where('estado', 'Aprobado');
            });

        if ($request->filled('fecha_desde')) {
            $query->where('fecha', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha', '<=', $request->fecha_hasta);
        }

        $movimientos = $query->orderBy('fecha')
            ->orderBy('comprobante_id')
            ->get();

        $totales = [
            'debito' => $movimientos->sum('debito'),
            'credito' => $movimientos->sum('credito')
        ];

        return view('contabilidad.movimientos.libro_diario', 
            compact('movimientos', 'totales'));
    }

    public function libro_mayor(Request $request)
    {
        $request->validate([
            'cuenta_id' => 'required|exists:plan_cuentas,id',
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde'
        ]);

        $cuenta = PlanCuenta::findOrFail($request->cuenta_id);

        // Saldo anterior
        $saldoAnterior = MovimientoContable::where('cuenta_id', $cuenta->id)
            ->where('fecha', '<', $request->fecha_desde)
            ->selectRaw('SUM(debito) - SUM(credito) as saldo')
            ->value('saldo') ?? 0;

        // Movimientos del período
        $movimientos = MovimientoContable::with(['comprobante'])
            ->where('cuenta_id', $cuenta->id)
            ->whereBetween('fecha', [$request->fecha_desde, $request->fecha_hasta])
            ->whereHas('comprobante', function($q) {
                $q->where('estado', 'Aprobado');
            })
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        // Calcular saldos progresivos
        $saldo = $saldoAnterior;
        foreach ($movimientos as $movimiento) {
            $saldo += $movimiento->debito - $movimiento->credito;
            $movimiento->saldo = $saldo;
        }

        $totales = [
            'saldo_anterior' => $saldoAnterior,
            'debitos' => $movimientos->sum('debito'),
            'creditos' => $movimientos->sum('credito'),
            'saldo_final' => $saldo
        ];

        return view('contabilidad.movimientos.libro_mayor', 
            compact('cuenta', 'movimientos', 'totales'));
    }

    public function balance_comprobacion(Request $request)
    {
        $request->validate([
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde'
        ]);

        $cuentas = PlanCuenta::where('estado', true)->get();
        $balances = [];

        foreach ($cuentas as $cuenta) {
            // Saldo anterior
            $saldoAnterior = MovimientoContable::where('cuenta_id', $cuenta->id)
                ->where('fecha', '<', $request->fecha_desde)
                ->selectRaw('SUM(debito) as total_debito, SUM(credito) as total_credito')
                ->first();

            // Movimientos del período
            $movimientos = MovimientoContable::where('cuenta_id', $cuenta->id)
                ->whereBetween('fecha', [$request->fecha_desde, $request->fecha_hasta])
                ->whereHas('comprobante', function($q) {
                    $q->where('estado', 'Aprobado');
                })
                ->selectRaw('SUM(debito) as total_debito, SUM(credito) as total_credito')
                ->first();

            $debitos = ($saldoAnterior->total_debito ?? 0) + ($movimientos->total_debito ?? 0);
            $creditos = ($saldoAnterior->total_credito ?? 0) + ($movimientos->total_credito ?? 0);
            $saldo = $debitos - $creditos;

            if ($debitos != 0 || $creditos != 0) {
                $balances[] = [
                    'cuenta' => $cuenta,
                    'debitos' => $debitos,
                    'creditos' => $creditos,
                    'saldo' => $saldo
                ];
            }
        }

        $totales = [
            'debitos' => array_sum(array_column($balances, 'debitos')),
            'creditos' => array_sum(array_column($balances, 'creditos'))
        ];

        return view('contabilidad.movimientos.balance_comprobacion', 
            compact('balances', 'totales'));
    }

    public function auxiliar(Request $request)
    {
        $request->validate([
            'cuenta_id' => 'required|exists:plan_cuentas,id',
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde'
        ]);

        $cuenta = PlanCuenta::findOrFail($request->cuenta_id);
        
        $movimientos = MovimientoContable::with(['comprobante'])
            ->where('cuenta_id', $cuenta->id)
            ->whereBetween('fecha', [$request->fecha_desde, $request->fecha_hasta])
            ->whereHas('comprobante', function($q) {
                $q->where('estado', 'Aprobado');
            })
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        return view('contabilidad.movimientos.auxiliar', 
            compact('cuenta', 'movimientos'));
    }
}