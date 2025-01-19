<?php

namespace App\Http\Controllers;

use App\Models\Ubicacion;
use Illuminate\Http\Request;

class UbicacionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $ubicaciones = Ubicacion::latest()->paginate(10);
        return view('ubicaciones.index', compact('ubicaciones'));
    }

    public function create()
    {
        return view('ubicaciones.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:ubicaciones',
            'tipo' => 'required|in:bodega,mostrador',
            'descripcion' => 'nullable|string',
            'estado' => 'boolean'
        ]);

        try {
            $data = $request->all();
            $data['estado'] = $request->has('estado');
            
            Ubicacion::create($data);
            return redirect()->route('ubicaciones.index')
                ->with('success', 'Ubicación creada exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al crear la ubicación')
                ->withInput();
        }
    }

    public function edit($id)
    {
        $ubicacion = Ubicacion::with('productos')->findOrFail($id);
        return view('ubicaciones.edit', compact('ubicacion'));
    }

    public function update(Request $request, $id)
    {
        $ubicacion = Ubicacion::findOrFail($id);
        
        $request->validate([
            'nombre' => 'required|string|max:255|unique:ubicaciones,nombre,' . $id,
            'tipo' => 'required|in:bodega,mostrador',
            'descripcion' => 'nullable|string',
            'estado' => 'boolean'
        ]);

        try {
            $data = $request->all();
            $data['estado'] = $request->has('estado');
            
            $ubicacion->update($data);
            return redirect()->route('ubicaciones.index')
                ->with('success', 'Ubicación actualizada exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al actualizar la ubicación')
                ->withInput();
        }
    }

    public function destroy($id)
    {
        $ubicacion = Ubicacion::findOrFail($id);

        try {
            // Verificar si tiene productos asociados
            if ($ubicacion->productos()->exists()) {
                return back()->with('error', 'No se puede eliminar la ubicación porque tiene productos asociados');
            }

            $ubicacion->delete();
            return redirect()->route('ubicaciones.index')
                ->with('success', 'Ubicación eliminada exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar la ubicación');
        }
    }
}