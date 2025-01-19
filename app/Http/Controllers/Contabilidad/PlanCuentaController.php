<?php

namespace App\Http\Controllers\Contabilidad;

use App\Http\Controllers\Controller;
use App\Models\PlanCuenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlanCuentaController extends Controller
{
    public function index()
    {
        $cuentas = PlanCuenta::orderBy('codigo')->get();
        return view('contabilidad.plan-cuentas.index', compact('cuentas'));
    }

    public function create()
    {
        $cuentasPadre = PlanCuenta::orderBy('codigo')->get();
        return view('contabilidad.plan-cuentas.create', compact('cuentasPadre'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo' => 'required|unique:plan_cuentas',
            'nombre' => 'required',
            'tipo' => 'required|in:Activo,Pasivo,Patrimonio,Ingreso,Gasto',
            'nivel' => 'required|integer|min:1',
            'cuenta_padre_id' => 'nullable|exists:plan_cuentas,id'
        ]);

        try {
            DB::beginTransaction();

            PlanCuenta::create($request->all());

            DB::commit();
            return redirect()->route('plan-cuentas.index')
                ->with('success', 'Cuenta creada exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al crear la cuenta: ' . $e->getMessage());
        }
    }

    public function edit(PlanCuenta $planCuenta)
    {
        $cuentasPadre = PlanCuenta::where('id', '!=', $planCuenta->id)
            ->orderBy('codigo')
            ->get();
        return view('contabilidad.plan-cuentas.edit', compact('planCuenta', 'cuentasPadre'));
    }

    public function update(Request $request, PlanCuenta $planCuenta)
    {
        $request->validate([
            'codigo' => 'required|unique:plan_cuentas,codigo,' . $planCuenta->id,
            'nombre' => 'required',
            'tipo' => 'required|in:Activo,Pasivo,Patrimonio,Ingreso,Gasto',
            'nivel' => 'required|integer|min:1',
            'cuenta_padre_id' => 'nullable|exists:plan_cuentas,id'
        ]);

        try {
            DB::beginTransaction();

            if ($request->cuenta_padre_id == $planCuenta->id) {
                throw new \Exception('Una cuenta no puede ser su propia cuenta padre');
            }

            $planCuenta->update($request->all());

            DB::commit();
            return redirect()->route('plan-cuentas.index')
                ->with('success', 'Cuenta actualizada exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar la cuenta: ' . $e->getMessage());
        }
    }

    public function destroy(PlanCuenta $planCuenta)
    {
        try {
            if ($planCuenta->movimientos()->exists()) {
                throw new \Exception('No se puede eliminar la cuenta porque tiene movimientos asociados');
            }

            if ($planCuenta->subcuentas()->exists()) {
                throw new \Exception('No se puede eliminar la cuenta porque tiene subcuentas asociadas');
            }

            $planCuenta->delete();
            return redirect()->route('plan-cuentas.index')
                ->with('success', 'Cuenta eliminada exitosamente');

        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar la cuenta: ' . $e->getMessage());
        }
    }

    public function buscar(Request $request)
    {
        $query = PlanCuenta::query();

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function($q) use ($search) {
                $q->where('codigo', 'LIKE', "%{$search}%")
                  ->orWhere('nombre', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        $cuentas = $query->orderBy('codigo')->get();

        if ($request->ajax()) {
            return response()->json($cuentas);
        }

        return view('contabilidad.plan-cuentas.index', compact('cuentas'));
    }
}