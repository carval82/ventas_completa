<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
   public function index(Request $request)
   {
       $query = Cliente::query();

       if ($request->filled('search')) {
           $search = $request->search;
           $query->where(function($q) use ($search) {
               $q->where('nombres', 'LIKE', "%{$search}%")
                 ->orWhere('apellidos', 'LIKE', "%{$search}%")
                 ->orWhere('cedula', 'LIKE', "%{$search}%");
           });
       }

       $clientes = $query->latest()->paginate(10)->appends($request->query());
       return view('clientes.index', compact('clientes'));
   }

   public function create()
   {
       return view('clientes.create');
   }

   public function store(Request $request)
   {
       $request->validate([
           'nombres' => 'required',
           'apellidos' => 'required',
           'cedula' => 'required|unique:clientes',
           'telefono' => 'nullable',
           'email' => 'nullable|email|unique:clientes',
           'direccion' => 'nullable',
           'ciudad' => 'nullable',
           'departamento' => 'nullable',
           'tipo_documento' => 'nullable',
           'tipo_persona' => 'nullable|in:PERSON_ENTITY,LEGAL_ENTITY',
           'regimen' => 'nullable|in:SIMPLIFIED_REGIME,COMMON_REGIME,SPECIAL_REGIME,NATIONAL_CONSUMPTION_TAX'
       ]);

       try {
           $cliente = Cliente::create($request->all());
           
           // Intentar sincronizar con Alegra si la facturación electrónica está habilitada
           $empresa = \App\Models\Empresa::first();
           if ($empresa && $empresa->factura_electronica_habilitada) {
               $resultado = $cliente->syncToAlegra();
               
               if (!$resultado['success']) {
                   \Log::warning('No se pudo sincronizar el cliente con Alegra', [
                       'cliente_id' => $cliente->id,
                       'error' => $resultado['error'] ?? 'Error desconocido'
                   ]);
               }
           }
           
           return redirect()->route('clientes.index')
                          ->with('success', 'Cliente registrado exitosamente');
       } catch (\Exception $e) {
           \Log::error('Error al registrar cliente', [
               'error' => $e->getMessage(),
               'trace' => $e->getTraceAsString()
           ]);
           return back()->with('error', 'Error al registrar el cliente: ' . $e->getMessage())->withInput();
       }
   }

   public function show(Cliente $cliente)
   {
       $cliente->load('ventas');
       return view('clientes.show', compact('cliente'));
   }

   public function edit(Cliente $cliente)
   {
       return view('clientes.edit', compact('cliente'));
   }

   public function update(Request $request, Cliente $cliente)
   {
       $request->validate([
           'nombres' => 'required',
           'apellidos' => 'required',
           'cedula' => 'required|unique:clientes,cedula,' . $cliente->id,
           'telefono' => 'nullable',
           'email' => 'nullable|email|unique:clientes,email,' . $cliente->id,
           'direccion' => 'nullable',
           'ciudad' => 'nullable',
           'departamento' => 'nullable',
           'tipo_documento' => 'nullable',
           'tipo_persona' => 'nullable|in:PERSON_ENTITY,LEGAL_ENTITY',
           'regimen' => 'nullable|in:SIMPLIFIED_REGIME,COMMON_REGIME,SPECIAL_REGIME,NATIONAL_CONSUMPTION_TAX',
           'estado' => 'required|boolean'
       ]);

       try {
           $cliente->update($request->all());
           
           // Intentar sincronizar con Alegra si la facturación electrónica está habilitada
           $empresa = \App\Models\Empresa::first();
           if ($empresa && $empresa->factura_electronica_habilitada) {
               $resultado = $cliente->syncToAlegra();
               
               if (!$resultado['success']) {
                   \Log::warning('No se pudo sincronizar el cliente con Alegra', [
                       'cliente_id' => $cliente->id,
                       'error' => $resultado['error'] ?? 'Error desconocido'
                   ]);
               }
           }
           
           return redirect()->route('clientes.index')
                          ->with('success', 'Cliente actualizado exitosamente');
       } catch (\Exception $e) {
           \Log::error('Error al actualizar cliente', [
               'error' => $e->getMessage(),
               'trace' => $e->getTraceAsString()
           ]);
           return back()->with('error', 'Error al actualizar el cliente: ' . $e->getMessage())->withInput();
       }
   }

   public function destroy(Cliente $cliente)
   {
       try {
           $cliente->delete();
           return redirect()->route('clientes.index')
                          ->with('success', 'Cliente eliminado exitosamente');
       } catch (\Exception $e) {
           return back()->with('error', 'Error al eliminar el cliente');
       }
   }
}