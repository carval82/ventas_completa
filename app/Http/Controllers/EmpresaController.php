<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Services\AlegraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EmpresaController extends Controller
{
    protected $alegraService;

    public function __construct(AlegraService $alegraService = null)
    {
        $this->middleware('auth');
        
        try {
            $this->alegraService = $alegraService ?? new AlegraService();
        } catch (\Exception $e) {
            Log::error('Error al inicializar AlegraService: ' . $e->getMessage());
            // Continuamos sin el servicio de Alegra
        }
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
            'regimen_tributario' => 'required|in:responsable_iva,no_responsable_iva,regimen_simple',
            'resolucion_facturacion' => 'nullable|string|max:255',
            'fecha_resolucion' => 'nullable|date',
            'factura_electronica_habilitada' => 'nullable|boolean'
        ]);

        try {
            $data = $request->except('logo');
            
            if ($request->hasFile('logo')) {
                $data['logo'] = $request->file('logo')->store('logos', 'public');
            }

            // Asegurarse de que el checkbox se maneje correctamente
            $data['factura_electronica_habilitada'] = $request->has('factura_electronica_habilitada');

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
            'regimen_tributario' => 'required|in:responsable_iva,no_responsable_iva,regimen_simple',
            'resolucion_facturacion' => 'nullable|string|max:255',
            'fecha_resolucion' => 'nullable|date',
            'factura_electronica_habilitada' => 'nullable|boolean'
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

            // Asegurarse de que el checkbox se maneje correctamente
            $data['factura_electronica_habilitada'] = $request->has('factura_electronica_habilitada');

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

    public function obtenerResolucionAlegra(Request $request)
    {
        try {
            Log::info('Iniciando solicitud de resolución Alegra');
            
            $resolucion = $this->alegraService->obtenerResolucionFacturacion();
            
            Log::info('Respuesta del servicio Alegra recibida', [
                'respuesta' => $resolucion
            ]);
            
            if ($resolucion['success']) {
                Log::info('Resolución obtenida correctamente', [
                    'data' => $resolucion['data']
                ]);
                
                return response()->json([
                    'success' => true,
                    'resolucion' => $resolucion['data']['number'],
                    'fecha' => $resolucion['data']['date']
                ]);
            }

            Log::warning('No se pudo obtener la resolución', [
                'error' => $resolucion['error']
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo obtener la resolución de Alegra: ' . 
                            ($resolucion['error'] ?? 'Error desconocido')
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error en el controlador al obtener resolución', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener resolución: ' . $e->getMessage()
            ], 500);
        }
    }

    public function verificarFacturacionElectronica()
    {
        try {
            $alegraService = app(AlegraService::class);
            $response = $alegraService->verificarFacturacionElectronica();
            
            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Error al verificar FE', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}