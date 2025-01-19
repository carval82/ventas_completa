<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmpresaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $empresa = Empresa::first();
        return view('configuracion.empresa.index', compact('empresa'));
    }

    public function create()
    {
        return view('configuracion.empresa.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre_comercial' => 'required|string|max:255',
            'razon_social' => 'required|string|max:255',
            'nit' => 'required|string|unique:empresas,nit',
            'direccion' => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'sitio_web' => 'nullable|url|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:1024',
            'regimen_tributario' => 'required|in:comun,simplificado'
        ]);

        try {
            $data = $request->except('logo');

            if ($request->hasFile('logo')) {
                $data['logo'] = $request->file('logo')->store('logos', 'public');
            }

            Empresa::create($data);

            return redirect()->route('empresa.index')
                           ->with('success', 'Información de la empresa registrada exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al registrar la información de la empresa')
                        ->withInput();
        }
    }

    public function show(Empresa $empresa)
    {
        return view('configuracion.empresa.show', compact('empresa'));
    }

    public function edit(Empresa $empresa)
    {
        return view('configuracion.empresa.edit', compact('empresa'));
    }

    public function update(Request $request, Empresa $empresa)
    {
        $request->validate([
            'nombre_comercial' => 'required|string|max:255',
            'razon_social' => 'required|string|max:255',
            'nit' => 'required|string|unique:empresas,nit,' . $empresa->id,
            'direccion' => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'sitio_web' => 'nullable|url|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:1024',
            'regimen_tributario' => 'required|in:comun,simplificado'
        ]);

        try {
            $data = $request->except('logo');

            if ($request->hasFile('logo')) {
                // Eliminar logo anterior
                if ($empresa->logo && Storage::disk('public')->exists($empresa->logo)) {
                    Storage::disk('public')->delete($empresa->logo);
                }
                $data['logo'] = $request->file('logo')->store('logos', 'public');
            }

            $empresa->update($data);

            return redirect()->route('empresa.index')
                           ->with('success', 'Información de la empresa actualizada exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al actualizar la información de la empresa')
                        ->withInput();
        }
    }

    public function destroy(Empresa $empresa)
    {
        try {
            if ($empresa->logo && Storage::disk('public')->exists($empresa->logo)) {
                Storage::disk('public')->delete($empresa->logo);
            }
            
            $empresa->delete();
            return redirect()->route('empresa.index')
                           ->with('success', 'Información de la empresa eliminada exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar la información de la empresa');
        }
    }
}