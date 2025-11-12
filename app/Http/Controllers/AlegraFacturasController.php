<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Venta;
use App\Services\Alegra\AlegraService;

class AlegraFacturasController extends Controller
{
    protected $alegraService;
    
    public function __construct()
    {
        $this->middleware('auth');
        // Intentamos usar el servicio existente de Alegra
        try {
            $this->alegraService = app(AlegraService::class);
        } catch (\Exception $e) {
            Log::error('Error al inicializar AlegraService: ' . $e->getMessage());
        }
    }
    
    /**
     * Muestra la lista de facturas generadas en Alegra
     */
    public function index(Request $request)
    {
        // Registrar acceso a la función
        Log::info('Accediendo a AlegraFacturasController@index');
        try {
            // Depuración: Verificar información de la empresa
            $empresa = \App\Models\Empresa::first();
            if ($empresa) {
                Log::info('Empresa encontrada: ' . $empresa->nombre_comercial);
                Log::info('Credenciales Alegra - Email: ' . $empresa->alegra_email . ', Token: ' . (empty($empresa->alegra_token) ? 'No disponible' : 'Disponible'));
            } else {
                Log::warning('No se encontró información de empresa en la base de datos');
            }
            // Obtener filtros de fecha
            $fechaInicio = $request->input('fecha_inicio', date('Y-m-d', strtotime('-30 days')));
            $fechaFin = $request->input('fecha_fin', date('Y-m-d'));
            
            // Asegurar formato correcto de fechas (YYYY-MM-DD)
            $fechaInicio = date('Y-m-d', strtotime($fechaInicio));
            $fechaFin = date('Y-m-d', strtotime($fechaFin));
            
            // Registrar filtros para depuración
            Log::info('Filtros aplicados:', [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'estado' => $request->input('estado')
            ]);
            
            // Obtener facturas de Alegra con parámetros correctos
            $parametrosAlegra = [
                'start' => $fechaInicio,
                'end' => $fechaFin,
                'limit' => 30, // Limitar cantidad de resultados por página (máximo permitido por Alegra)
                'totalLimit' => 90, // Obtener hasta 90 facturas en total (3 páginas)
                'orderDirection' => 'DESC', // Más recientes primero
                'orderField' => 'date' // Ordenar por fecha
            ];
            
            // Forzar limpieza de caché para asegurar datos frescos
            \Cache::forget('alegra_invoices_' . md5(json_encode($parametrosAlegra)));
            
            // Agregar filtro de estado si está especificado
            if (!empty($request->input('estado'))) {
                $parametrosAlegra['status'] = $request->input('estado');
            }
            
            // Obtener facturas de Alegra
            $respuestaAlegra = $this->alegraService->getInvoices($parametrosAlegra);
            
            // Procesar la respuesta para asegurar que tenga el formato esperado
            $facturas = [];
            
            // Verificar si la respuesta es un array asociativo o un array de facturas
            if (!empty($respuestaAlegra)) {
                // Si es un array asociativo con una clave 'data', usar esa clave
                if (isset($respuestaAlegra['data'])) {
                    $facturas = $respuestaAlegra['data'];
                } 
                // Si es un array indexado, usarlo directamente
                else if (isset($respuestaAlegra[0])) {
                    $facturas = $respuestaAlegra;
                }
                // Si es una única factura (array asociativo sin 'data'), envolverla en un array
                else if (isset($respuestaAlegra['id'])) {
                    $facturas = [$respuestaAlegra];
                }
            }
            
            // Verificar si hay ventas vinculadas a estas facturas
            if (!empty($facturas)) {
                $alegraIds = array_column($facturas, 'id');
                $ventasVinculadas = Venta::whereIn('alegra_id', $alegraIds)->get()->keyBy('alegra_id');
                
                // Agregar información de ventas vinculadas a cada factura
                foreach ($facturas as &$factura) {
                    if (isset($ventasVinculadas[$factura['id']])) {
                        $factura['venta_id'] = $ventasVinculadas[$factura['id']]->id;
                    }
                }
            }
            
            // Si no hay facturas, mostrar datos de ejemplo para fines de desarrollo
            if (empty($facturas)) {
                Log::warning('No se encontraron facturas en Alegra con los filtros aplicados. Mostrando datos de ejemplo para desarrollo.');
                
                // Datos de ejemplo para desarrollo y pruebas
                $facturas = [
                    [
                        'id' => 'demo-001',
                        'number' => '001',
                        'date' => date('Y-m-d'),
                        'dueDate' => date('Y-m-d', strtotime('+30 days')),
                        'status' => 'open',
                        'total' => 1500000,
                        'client' => ['name' => 'Cliente Demo 1'],
                        'numberTemplate' => ['prefix' => 'FE-'],
                        'demo' => true // Marcador para identificar facturas de demo
                    ],
                    [
                        'id' => 'demo-002',
                        'number' => '002',
                        'date' => date('Y-m-d', strtotime('-5 days')),
                        'dueDate' => date('Y-m-d', strtotime('+25 days')),
                        'status' => 'closed',
                        'total' => 2300000,
                        'client' => ['name' => 'Cliente Demo 2'],
                        'numberTemplate' => ['prefix' => 'FE-'],
                        'demo' => true
                    ],
                    [
                        'id' => 'demo-003',
                        'number' => '003',
                        'date' => date('Y-m-d', strtotime('-10 days')),
                        'dueDate' => date('Y-m-d', strtotime('+20 days')),
                        'status' => 'voided',
                        'total' => 980000,
                        'client' => ['name' => 'Cliente Demo 3'],
                        'numberTemplate' => ['prefix' => 'FE-'],
                        'demo' => true
                    ]
                ];
            }
            
            // Registrar en el log para depuración
            Log::info('Facturas obtenidas de Alegra: ' . count($facturas));
            
            // Registrar la estructura exacta de la primera factura para depuración
            if (!empty($facturas)) {
                Log::info('Estructura de la primera factura: ' . json_encode($facturas[0], JSON_PRETTY_PRINT));
            }
            
            // Obtener ventas locales para vincular
            $ventas = Venta::whereNull('alegra_id')
                ->whereBetween('fecha_venta', [$fechaInicio, $fechaFin])
                ->get();
            
            // Preparar filtros para la vista
            $filtros = [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'estado' => $request->input('estado', '')
            ];
            
            return view('alegra.facturas.index', compact('facturas', 'filtros', 'ventas'));
        } catch (\Exception $e) {
            Log::error('Error al obtener facturas de Alegra: ' . $e->getMessage());
            return back()->with('error', 'Error al obtener facturas de Alegra: ' . $e->getMessage());
        }
    }
    
    /**
     * Muestra los detalles de una factura específica
     */
    public function show($id)
    {
        try {
            // Obtener detalles de la factura de Alegra
            $factura = $this->alegraService->getInvoice($id);
            
            // Verificar si esta factura está vinculada a una venta local
            $venta = Venta::where('alegra_id', $id)->first();
            
            return view('alegra.facturas.show', compact('factura', 'venta'));
        } catch (\Exception $e) {
            Log::error('Error al obtener detalles de factura: ' . $e->getMessage());
            return back()->with('error', 'Error al obtener detalles de factura: ' . $e->getMessage());
        }
    }
    
    /**
     * Vincula una factura de Alegra con una venta local
     */
    public function vincular(Request $request)
    {
        try {
            $request->validate([
                'alegra_id' => 'required',
                'venta_id' => 'required|exists:ventas,id',
                'numero_factura' => 'required'
            ]);
            
            // Buscar la venta
            $venta = Venta::findOrFail($request->venta_id);
            
            // Actualizar con los datos de Alegra
            $venta->alegra_id = $request->alegra_id;
            $venta->numero_factura_electronica = $request->numero_factura;
            $venta->save();
            
            return redirect()->route('alegra.facturas.index')
                ->with('success', 'Factura vinculada correctamente con la venta #' . $venta->id);
        } catch (\Exception $e) {
            Log::error('Error al vincular factura: ' . $e->getMessage());
            return back()->with('error', 'Error al vincular factura: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtiene el estado de una factura electrónica
     */
    public function estado($id)
    {
        try {
            $estado = $this->alegraService->getElectronicInvoiceStatus($id);
            return response()->json($estado);
        } catch (\Exception $e) {
            Log::error('Error al obtener estado de factura: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
