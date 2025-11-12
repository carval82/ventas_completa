<?php

namespace App\Http\Controllers;

use App\Models\CajaDiaria;
use App\Models\MovimientoCaja;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CajaDiariaController extends Controller
{
    /**
     * Muestra la lista de cajas diarias
     */
    public function index(Request $request)
    {
        $query = CajaDiaria::query()->orderBy('fecha_apertura', 'desc');
        
        // Filtros
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_apertura', '>=', $request->fecha_desde);
        }
        
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_apertura', '<=', $request->fecha_hasta);
        }
        
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        
        $cajas = $query->paginate(15);
        
        return view('cajas.index', compact('cajas'));
    }

    /**
     * Muestra el formulario para abrir una nueva caja
     */
    public function create()
    {
        // Verificar si ya hay una caja abierta
        if (CajaDiaria::hayUnaAbierta()) {
            return redirect()->route('cajas.index')
                ->with('error', 'Ya existe una caja abierta. Debe cerrarla antes de abrir una nueva.');
        }
        
        return view('cajas.create');
    }

    /**
     * Almacena una nueva caja en la base de datos
     */
    public function store(Request $request)
    {
        // Validar datos
        $request->validate([
            'monto_apertura' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string|max:500',
        ]);
        
        // Verificar si ya hay una caja abierta
        if (CajaDiaria::hayUnaAbierta()) {
            return redirect()->route('cajas.index')
                ->with('error', 'Ya existe una caja abierta. Debe cerrarla antes de abrir una nueva.');
        }
        
        // Crear la caja
        $caja = CajaDiaria::create([
            'fecha_apertura' => now(),
            'monto_apertura' => $request->monto_apertura,
            'observaciones' => $request->observaciones,
            'estado' => 'abierta',
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
        
        // Registrar el movimiento de apertura
        MovimientoCaja::create([
            'caja_id' => $caja->id,
            'fecha' => now(),
            'tipo' => 'ingreso',
            'concepto' => 'Apertura de caja',
            'monto' => $request->monto_apertura,
            'metodo_pago' => 'efectivo',
            'observaciones' => 'Monto inicial de apertura',
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
        
        return redirect()->route('cajas.show', $caja)
            ->with('success', 'Caja abierta correctamente con un monto inicial de $' . number_format($request->monto_apertura, 2));
    }

    /**
     * Muestra los detalles de una caja específica
     */
    public function show(CajaDiaria $caja)
    {
        // Cargar relaciones
        $caja->load(['movimientos', 'creadoPor', 'ventas']);
        
        // Calcular totales
        $totalVentas = $caja->calcularTotalVentas();
        $totalGastos = $caja->calcularTotalGastos();
        $totalPagos = $caja->calcularTotalPagos();
        
        // Calcular saldo actual
        $saldoActual = $caja->monto_apertura + $totalVentas - $totalGastos - $totalPagos;
        
        // Obtener movimientos agrupados por tipo
        $ingresos = $caja->movimientos()->where('tipo', 'ingreso')->get();
        $gastos = $caja->movimientos()->where('tipo', 'gasto')->get();
        $pagos = $caja->movimientos()->where('tipo', 'pago')->get();
        
        return view('cajas.show', compact('caja', 'totalVentas', 'totalGastos', 'totalPagos', 'saldoActual', 'ingresos', 'gastos', 'pagos'));
    }

    /**
     * Muestra el formulario para cerrar una caja
     */
    public function edit(CajaDiaria $caja)
    {
        // Verificar si la caja está abierta
        if (!$caja->estaAbierta()) {
            return redirect()->route('cajas.show', $caja)
                ->with('error', 'Esta caja ya está cerrada.');
        }
        
        // Calcular totales
        $totalVentas = $caja->calcularTotalVentas();
        $totalGastos = $caja->calcularTotalGastos();
        $totalPagos = $caja->calcularTotalPagos();
        
        // Calcular saldo teórico
        $saldoTeorico = $caja->monto_apertura + $totalVentas - $totalGastos - $totalPagos;
        
        return view('cajas.edit', compact('caja', 'totalVentas', 'totalGastos', 'totalPagos', 'saldoTeorico'));
    }

    /**
     * Cierra una caja
     */
    public function update(Request $request, CajaDiaria $caja)
    {
        // Validar datos
        $request->validate([
            'monto_cierre' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string|max:500',
        ]);
        
        // Verificar si la caja está abierta
        if (!$caja->estaAbierta()) {
            return redirect()->route('cajas.show', $caja)
                ->with('error', 'Esta caja ya está cerrada.');
        }
        
        // Calcular totales
        $totalVentas = $caja->calcularTotalVentas();
        $totalGastos = $caja->calcularTotalGastos();
        $totalPagos = $caja->calcularTotalPagos();
        
        // Calcular saldo teórico y diferencia
        $saldoTeorico = $caja->monto_apertura + $totalVentas - $totalGastos - $totalPagos;
        $diferencia = $request->monto_cierre - $saldoTeorico;
        
        // Actualizar la caja
        $caja->update([
            'fecha_cierre' => now(),
            'monto_cierre' => $request->monto_cierre,
            'total_ventas' => $totalVentas,
            'total_gastos' => $totalGastos,
            'total_pagos' => $totalPagos,
            'diferencia' => $diferencia,
            'observaciones' => $request->observaciones,
            'estado' => 'cerrada',
            'updated_by' => Auth::id(),
        ]);
        
        return redirect()->route('cajas.show', $caja)
            ->with('success', 'Caja cerrada correctamente.');
    }

    /**
     * Muestra el formulario para registrar un nuevo movimiento
     */
    public function createMovimiento()
    {
        // Verificar si hay una caja abierta
        $caja = CajaDiaria::obtenerCajaAbierta();
        
        if (!$caja) {
            return redirect()->route('cajas.index')
                ->with('error', 'No hay ninguna caja abierta. Debe abrir una caja antes de registrar movimientos.');
        }
        
        return view('cajas.movimientos.create', compact('caja'));
    }

    /**
     * Almacena un nuevo movimiento en la base de datos
     */
    public function storeMovimiento(Request $request)
    {
        // Validar datos
        $request->validate([
            'tipo' => 'required|in:ingreso,gasto,pago',
            'concepto' => 'required|string|max:255',
            'monto' => 'required|numeric|min:0.01',
            'metodo_pago' => 'required|string|max:50',
            'observaciones' => 'nullable|string|max:500',
        ]);
        
        // Verificar si hay una caja abierta
        $caja = CajaDiaria::obtenerCajaAbierta();
        
        if (!$caja) {
            return redirect()->route('cajas.index')
                ->with('error', 'No hay ninguna caja abierta. Debe abrir una caja antes de registrar movimientos.');
        }
        
        // Crear el movimiento
        $movimiento = MovimientoCaja::create([
            'caja_id' => $caja->id,
            'fecha' => now(),
            'tipo' => $request->tipo,
            'concepto' => $request->concepto,
            'monto' => $request->monto,
            'metodo_pago' => $request->metodo_pago,
            'observaciones' => $request->observaciones,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
        
        return redirect()->route('cajas.show', $caja)
            ->with('success', 'Movimiento registrado correctamente.');
    }

    /**
     * Genera un reporte de la caja
     */
    public function reporte(CajaDiaria $caja)
    {
        // Cargar relaciones
        $caja->load(['movimientos', 'creadoPor', 'ventas']);
        
        // Calcular totales
        $totalVentas = $caja->calcularTotalVentas();
        $totalGastos = $caja->calcularTotalGastos();
        $totalPagos = $caja->calcularTotalPagos();
        
        // Calcular saldo actual
        $saldoActual = $caja->monto_apertura + $totalVentas - $totalGastos - $totalPagos;
        
        // Obtener movimientos agrupados por tipo
        $ingresos = $caja->movimientos()->where('tipo', 'ingreso')->get();
        $gastos = $caja->movimientos()->where('tipo', 'gasto')->get();
        $pagos = $caja->movimientos()->where('tipo', 'pago')->get();
        
        return view('cajas.reporte', compact('caja', 'totalVentas', 'totalGastos', 'totalPagos', 'saldoActual', 'ingresos', 'gastos', 'pagos'));
    }

    /**
     * Muestra el estado actual de la caja
     */
    public function estadoActual()
    {
        // Verificar si hay una caja abierta
        $caja = CajaDiaria::obtenerCajaAbierta();
        
        if (!$caja) {
            return redirect()->route('cajas.index')
                ->with('error', 'No hay ninguna caja abierta actualmente.');
        }
        
        return redirect()->route('cajas.show', $caja);
    }
}
