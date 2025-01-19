<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProveedorController extends Controller
{
    public function index(Request $request)
    {
        $query = Proveedor::query();

        if($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('razon_social', 'LIKE', "%{$search}%")
                  ->orWhere('nit', 'LIKE', "%{$search}%")
                  ->orWhere('contacto', 'LIKE', "%{$search}%");
            });
        }

        if($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $proveedores = $query->latest()->paginate(10);
        return view('proveedores.index', compact('proveedores'));
    }

    public function create()
    {
        Log::info('Accediendo al formulario de creación de proveedor');
        return view('proveedores.create');
    }

    public function store(Request $request)
    {
        Log::info('Iniciando creación de proveedor', ['request_data' => $request->all()]);

        try {
            $validated = $request->validate([
                'nit' => 'required|unique:proveedores',
                'razon_social' => 'required',
                'regimen' => 'nullable',
                'tipo_identificacion' => 'nullable',
                'direccion' => 'required',
                'ciudad' => 'nullable',
                'telefono' => 'required',
                'celular' => 'nullable',
                'fax' => 'nullable',
                'correo_electronico' => 'nullable|email',
                'contacto' => 'nullable'
            ]);

            Log::info('Datos validados correctamente', ['validated_data' => $validated]);

            $proveedor = Proveedor::create($validated);
            
            Log::info('Proveedor creado exitosamente', ['proveedor_id' => $proveedor->id]);

            return redirect()->route('proveedores.index')
                           ->with('success', 'Proveedor registrado exitosamente');
        } catch(\Illuminate\Validation\ValidationException $e) {
            Log::error('Error de validación', [
                'errors' => $e->errors(),
                'message' => $e->getMessage()
            ]);
            return back()->withErrors($e->errors())->withInput();
        } catch(\Exception $e) {
            Log::error('Error al crear proveedor', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Error al registrar el proveedor: ' . $e->getMessage())->withInput();
        }
    }

    public function show(Proveedor $proveedor)
    {
        return view('proveedores.show', compact('proveedor'));
    }

    public function edit(Proveedor $proveedor)
    {
        return view('proveedores.edit', compact('proveedor'));
    }

    public function update(Request $request, Proveedor $proveedor)
    {
        $request->validate([
            'nit' => 'required|unique:proveedores,nit,' . $proveedor->id,
            'razon_social' => 'required',
            'regimen' => 'nullable',
            'tipo_identificacion' => 'nullable',
            'direccion' => 'required',
            'ciudad' => 'nullable',
            'telefono' => 'required',
            'celular' => 'nullable',
            'fax' => 'nullable',
            'correo_electronico' => 'nullable|email',
            'contacto' => 'nullable'
        ]);

        try {
            $proveedor->update($request->all());
            return redirect()->route('proveedores.index')
                           ->with('success', 'Proveedor actualizado exitosamente');
        } catch(\Exception $e) {
            return back()->with('error', 'Error al actualizar el proveedor')->withInput();
        }
    }

    public function destroy(Proveedor $proveedor)
    {
        try {
            $proveedor->update(['estado' => false]);
            return redirect()->route('proveedores.index')
                           ->with('success', 'Proveedor desactivado exitosamente');
        } catch(\Exception $e) {
            return back()->with('error', 'Error al desactivar el proveedor');
        }
    }
}