<?php

namespace App\Http\Controllers;


use App\Models\Venta;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\DetalleVenta;
use App\Models\StockUbicacion;
use App\Models\Empresa;
use App\Models\Credito;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Http\Services\AlegraService;
use Illuminate\Support\Facades\Http;

class VentaController extends Controller
{
    protected $alegraService;

    public function __construct(AlegraService $alegraService)
    {
        $this->alegraService = $alegraService;
    }

    public function index(Request $request)
    {
        $query = Venta::with(['cliente', 'usuario']);
        
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_venta', '>=', $request->fecha_inicio);
        }
        
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_venta', '<=', $request->fecha_fin);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero_factura', 'LIKE', "%{$search}%")
                  ->orWhereHas('cliente', function($query) use ($search) {
                      $query->where(DB::raw("CONCAT(nombres, ' ', apellidos)"), 'LIKE', "%{$search}%")
                            ->orWhere('cedula', 'LIKE', "%{$search}%");
                  });
            });
        }

        $ventas = $query->latest('fecha_venta')->paginate(10);
        
        // Calcular totales
        $ventasHoy = Venta::whereDate('fecha_venta', today())->sum('total');
        $ventasMes = Venta::whereYear('fecha_venta', now()->year)
                         ->whereMonth('fecha_venta', now()->month)
                         ->sum('total');
        $ventasTotal = Venta::sum('total');

        return view('ventas.index', compact('ventas', 'ventasHoy', 'ventasMes', 'ventasTotal'));
    }

    public function create()
    {
        $empresa = \App\Models\Empresa::first();
        
        if (!$empresa) {
            return redirect()->route('empresa.create')
                ->with('error', 'Debe configurar los datos de la empresa antes de crear ventas');
        }
        
        date_default_timezone_set('America/Bogota');
        
        // Obtener otros datos necesarios
        $clientes = Cliente::where('estado', '1')->get();
        $productos = Producto::where('estado', '1')
                            ->where('stock', '>', 0)
                            ->get();
        $ultima_venta = Venta::latest()->first();
        $ultimas_ventas = Venta::latest()->take(5)->get();
        
        // Obtener último número de factura
        $ultimo_numero = $ultima_venta ? 'F' . ($ultima_venta->id + 1) : 'F1';
        
        // Obtener últimas facturas agrupadas por tipo
        $ultimas_facturas = $ultimas_ventas->map(function($venta) {
            $tipo = ucfirst($venta->tipo_factura);
            return "<div class='mb-1'>{$venta->numero_factura} - {$tipo} - " . 
                   $venta->created_at->format('d/m/Y') . "</div>";
        })
        ->implode('');
        
        return view('ventas.create', [
            'empresa' => $empresa,
            'clientes' => $clientes,
            'productos' => $productos,
            'fecha_actual' => now()->format('d/m/Y h:i A'),
            'ultimo_numero' => $ultimo_numero,
            'ultimas_facturas' => $ultimas_facturas
        ]);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            Log::info('Iniciando venta electrónica', [
                'tipo_factura' => $request->tipo_factura,
                'request' => $request->all()
            ]);

            // Validar datos básicos
            $request->validate([
                'cliente_id' => 'required|exists:clientes,id',
                'productos' => 'required|array|min:1',
                'subtotal' => 'required|numeric|min:0',
                'iva' => 'required|numeric|min:0',
                'total' => 'required|numeric|min:0',
                'tipo_factura' => 'required|in:normal,electronica,pos',
                'plantilla_factura' => 'required_if:tipo_factura,electronica'
            ]);

            // Generar número de factura según tipo
            $ultima_venta = Venta::latest()->first();
            $prefijo = match($request->tipo_factura) {
                'normal' => 'F',
                'electronica' => 'FE',
                'pos' => 'POS',
                default => 'F'
            };
            $numero_factura = $prefijo . ($ultima_venta ? ($ultima_venta->id + 1) : 1);

            // Crear la venta
            $venta = Venta::create([
                'cliente_id' => $request->cliente_id,
                'user_id' => auth()->id(),
                'numero_factura' => $numero_factura,
                'tipo_factura' => $request->tipo_factura,
                'plantilla_factura' => $request->plantilla_factura,
                'subtotal' => $request->subtotal,
                'iva' => $request->tipo_factura === 'normal' ? $request->iva : 0,
                'total' => $request->total,
                'metodo_pago' => $request->metodo_pago ?? 'efectivo',
                'fecha_venta' => now()
            ]);

            // Procesar productos
            foreach ($request->productos as $producto) {
                $detalle = $venta->detalles()->create([
                    'producto_id' => $producto['id'],
                    'cantidad' => $producto['cantidad'],
                    'precio_unitario' => $producto['precio'],
                    'subtotal' => $producto['cantidad'] * $producto['precio']
                ]);

                // Actualizar stock
                $productoModel = Producto::find($producto['id']);
                $productoModel->stock -= $producto['cantidad'];
                $productoModel->save();
            }

            if ($request->tipo_factura === 'electronica') {
                // Preparar datos para el servicio Python
                $items = $venta->detalles->map(function($detalle) {
                    return [
                        'id' => (string)$detalle->producto_id,
                        'price' => (float)$detalle->precio_unitario,
                        'quantity' => (int)$detalle->cantidad,
                        'description' => $detalle->producto->nombre
                    ];
                })->toArray();

                $alegraData = [
                    'date' => $venta->fecha,
                    'dueDate' => $venta->fecha,
                    'client_id' => (string)$venta->cliente_id,
                    'items' => $items
                ];

                Log::info('Enviando datos a servicio Python', [
                    'alegra_data' => $alegraData
                ]);

                // Enviar a servicio Python
                $response = Http::post('http://localhost:8000/invoices', $alegraData);
                
                Log::info('Respuesta del servicio Python', [
                    'status' => $response->status(),
                    'body' => $response->json()
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Actualizar venta con datos de Alegra
                    $venta->update([
                        'alegra_id' => $data['id'],
                        'cufe' => $data['stamp']['cufe'] ?? null,
                        'qr_code' => $data['stamp']['barCodeContent'] ?? null,
                        'estado_dian' => $data['stamp']['legalStatus'] ?? null,
                        'url_pdf' => $data['numberTemplate']['fullNumber'] ?? null
                    ]);

                    DB::commit();
                    return response()->json([
                        'success' => true,
                        'message' => 'Venta y factura electrónica creadas correctamente',
                        'data' => $venta
                    ]);
                }

                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear factura electrónica',
                    'error' => $response->json()
                ], 400);
            }

            // Si no es factura electrónica, continuar normal
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Venta creada correctamente',
                'data' => $venta
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en venta', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la venta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Venta $venta)
    {
        $venta->load(['detalles.producto', 'cliente', 'usuario']);
        
        // Cargar el stock por ubicación para cada producto
        foreach ($venta->detalles as $detalle) {
            $detalle->producto->stockPorUbicacion = StockUbicacion::stockPorUbicacion($detalle->producto_id);
        }
        
        return view('ventas.show', compact('venta'));
    }

    public function print($id)
    {
        $venta = Venta::with(['detalles.producto', 'cliente'])->findOrFail($id);
        $empresa = Empresa::first();
        return view('ventas.print', compact('venta', 'empresa'));
    }

    public function enviarADian(Venta $venta)
    {
        try {
            if (!$venta->alegra_id) {
                return back()->with('error', 'La venta no tiene factura en Alegra');
            }

            $alegraService = app(AlegraService::class);
            $response = $alegraService->generarFacturaElectronica($venta);
            
            if ($response['success']) {
                return back()->with('success', 'Factura enviada a la DIAN exitosamente');
            }

            return back()->with('error', 'Error al enviar a DIAN: ' . ($response['error'] ?? 'Error desconocido'));
        } catch (\Exception $e) {
            Log::error('Error al enviar factura a DIAN', [
                'venta_id' => $venta->id,
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}