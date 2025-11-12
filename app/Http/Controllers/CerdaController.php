<?php

namespace App\Http\Controllers;

use App\Models\Cerda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CerdaController extends Controller
{
    /**
     * Mostrar listado de cerdas
     */
    public function index()
    {
        $cerdas = Cerda::orderBy('codigo')->paginate(10);
        return view('cerdos.cerdas.index', compact('cerdas'));
    }

    /**
     * Mostrar formulario para crear nueva cerda
     */
    public function create()
    {
        return view('cerdos.cerdas.create');
    }

    /**
     * Almacenar nueva cerda
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'codigo' => 'required|unique:cerdas,codigo',
            'nombre' => 'nullable|string|max:255',
            'fecha_nacimiento' => 'required|date',
            'raza' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        Cerda::create($request->all());

        return redirect()->route('cerdas.index')
            ->with('success', 'Cerda registrada correctamente');
    }

    /**
     * Mostrar detalle de cerda
     */
    public function show(Cerda $cerda)
    {
        $camadas = $cerda->camadas()->orderBy('fecha_parto', 'desc')->get();
        return view('cerdos.cerdas.show', compact('cerda', 'camadas'));
    }

    /**
     * Mostrar formulario para editar cerda
     */
    public function edit(Cerda $cerda)
    {
        return view('cerdos.cerdas.edit', compact('cerda'));
    }

    /**
     * Actualizar cerda
     */
    public function update(Request $request, Cerda $cerda)
    {
        $validator = Validator::make($request->all(), [
            'codigo' => 'required|unique:cerdas,codigo,' . $cerda->id,
            'nombre' => 'nullable|string|max:255',
            'fecha_nacimiento' => 'required|date',
            'raza' => 'nullable|string|max:255',
            'estado' => 'required|in:activa,inactiva,vendida',
            'observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $cerda->update($request->all());

        return redirect()->route('cerdas.index')
            ->with('success', 'Cerda actualizada correctamente');
    }

    /**
     * Eliminar cerda
     */
    public function destroy(Cerda $cerda)
    {
        // Verificar si tiene camadas
        if ($cerda->camadas()->count() > 0) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar la cerda porque tiene camadas registradas');
        }

        $cerda->delete();

        return redirect()->route('cerdas.index')
            ->with('success', 'Cerda eliminada correctamente');
    }
}
