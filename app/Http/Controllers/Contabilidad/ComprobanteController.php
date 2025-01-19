<?php

namespace App\Http\Controllers\Contabilidad;

use App\Http\Controllers\Controller;
use App\Models\Comprobante;
use App\Models\MovimientoContable;
use App\Models\PlanCuenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ComprobanteController extends Controller
{
    public function index()
    {
        $comprobantes = Comprobante::with(['creadoPor', 'aprobadoPor'])
            ->latest()
            ->paginate(10);
            
        return view('contabilidad.comprobantes.index', compact('comprobantes'));
    }

    public function create()
    {
        $cuentas = PlanCuenta::where('estado', true)
            ->orderBy('codigo')
            ->get();
            
        // Generar número de comprobante
        $ultimoNumero = Comprobante::max('numero');
        $nuevoNumero = $ultimoNumero ? intval($ultimoNumero) + 1 : 1;
        $numero = str_pad($nuevoNumero, 6, '0', STR_PAD_LEFT);

        return view('contabilidad.comprobantes.create', compact('cuentas', 'numero'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date',
            'tipo' => 'required|in:Ingreso,Egreso,Diario',
            'descripcion' => 'required',
            'movimientos' => 'required|array|min:1',
            'movimientos.*.cuenta_id' => 'required|exists:plan_cuentas,id',
            'movimientos.*.descripcion' => 'required',
            'movimientos.*.debito' => 'required|numeric|min:0',
            'movimientos.*.credito' => 'required|numeric|min:0'
        ]);

        try {
            DB::beginTransaction();

            // Calcular totales
            $totalDebito = collect($request->movimientos)->sum('debito');
            $totalCredito = collect($request->movimientos)->sum('credito');

            // Verificar que esté cuadrado
            if ($totalDebito != $totalCredito) {
                throw new \Exception('El comprobante no está cuadrado. La diferencia es de ' . 
                    abs($totalDebito - $totalCredito));
            }

            // Crear comprobante
            $comprobante = Comprobante::create([
                'numero' => $request->numero,
                'fecha' => $request->fecha,
                'tipo' => $request->tipo,
                'descripcion' => $request->descripcion,
                'estado' => 'Borrador',
                'total_debito' => $totalDebito,
                'total_credito' => $totalCredito,
                'created_by' => Auth::id()
            ]);

            // Crear movimientos
            foreach ($request->movimientos as $movimiento) {
                $comprobante->movimientos()->create([
                    'cuenta_id' => $movimiento['cuenta_id'],
                    'fecha' => $request->fecha,
                    'descripcion' => $movimiento['descripcion'],
                    'debito' => $movimiento['debito'],
                    'credito' => $movimiento['credito']
                ]);
            }

            DB::commit();
            return redirect()->route('comprobantes.show', $comprobante)
                ->with('success', 'Comprobante creado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Error al crear el comprobante: ' . $e->getMessage());
        }
    }

    public function show(Comprobante $comprobante)
    {
        $comprobante->load(['movimientos.cuenta', 'creadoPor', 'aprobadoPor']);
        return view('contabilidad.comprobantes.show', compact('comprobante'));
    }

    public function aprobar(Comprobante $comprobante)
    {
        try {
            if ($comprobante->estado != 'Borrador') {
                throw new \Exception('Solo se pueden aprobar comprobantes en estado Borrador');
            }

            $comprobante->update([
                'estado' => 'Aprobado',
                'approved_by' => Auth::id()
            ]);

            return redirect()->route('comprobantes.show', $comprobante)
                ->with('success', 'Comprobante aprobado exitosamente');

        } catch (\Exception $e) {
            return back()->with('error', 'Error al aprobar el comprobante: ' . $e->getMessage());
        }
    }

    public function anular(Comprobante $comprobante)
    {
        try {
            if ($comprobante->estado == 'Anulado') {
                throw new \Exception('El comprobante ya está anulado');
            }

            $comprobante->update([
                'estado' => 'Anulado'
            ]);

            return redirect()->route('comprobantes.show', $comprobante)
                ->with('success', 'Comprobante anulado exitosamente');

        } catch (\Exception $e) {
            return back()->with('error', 'Error al anular el comprobante: ' . $e->getMessage());
        }
    }

    public function imprimir(Comprobante $comprobante)
    {
        $comprobante->load(['movimientos.cuenta', 'creadoPor', 'aprobadoPor']);
        return view('contabilidad.comprobantes.print', compact('comprobante'));
    }

    public function buscar(Request $request)
    {
        $query = Comprobante::query();

        if ($request->filled('fecha_desde')) {
            $query->where('fecha', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha', '<=', $request->fecha_hasta);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function($q) use ($search) {
                $q->where('numero', 'LIKE', "%{$search}%")
                  ->orWhere('descripcion', 'LIKE', "%{$search}%");
            });
        }

        $comprobantes = $query->with(['creadoPor', 'aprobadoPor'])
            ->latest()
            ->paginate(10);

        if ($request->ajax()) {
            return response()->json($comprobantes);
        }

        return view('contabilidad.comprobantes.index', compact('comprobantes'));
    }
}