<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Empresa;
use App\Services\IvaValidationService;
use App\Services\ContabilidadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CompraController extends Controller
{
    public function index(Request $request)
    {
        $query = Compra::with(['proveedor', 'usuario']);

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_compra', '>=', $request->fecha_inicio);
        }
        
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_compra', '<=', $request->fecha_fin);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero_factura', 'LIKE', "%{$search}%")
                  ->orWhereHas('proveedor', function($query) use ($search) {
                      $query->where('razon_social', 'LIKE', "%{$search}%")
                            ->orWhere('nit', 'LIKE', "%{$search}%");
                  });
            });
        }

        $compras = $query->latest('fecha_compra')->paginate(10);
        
        $comprasHoy = Compra::whereDate('fecha_compra', today())->sum('total');
        $comprasMes = Compra::whereYear('fecha_compra', now()->year)
                        ->whereMonth('fecha_compra', now()->month)
                        ->sum('total');
        $comprasTotal = Compra::sum('total');

        return view('compras.index', compact('compras', 'comprasHoy', 'comprasMes', 'comprasTotal'));
    }

    public function create()
    {
        $proveedores = Proveedor::where('estado', true)->get();
        $productos = Producto::where('estado', true)->get();
        return view('compras.create', compact('proveedores', 'productos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'numero_factura' => 'required',
            'proveedor_id' => 'required|exists:proveedores,id',
            'productos' => 'required|array',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|numeric|min:0.001',
            'productos.*.unidad_medida' => 'nullable|string',
            'productos.*.factor_conversion' => 'nullable|numeric|min:0.001',
            'subtotal' => 'required|numeric|min:0',
            'iva' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0'
        ]);

        try {
            DB::transaction(function () use ($request) {
                $compra = Compra::create([
                    'numero_factura' => $request->numero_factura,
                    'fecha_compra' => now(),
                    'proveedor_id' => $request->proveedor_id,
                    'user_id' => Auth::user()->id,
                    'subtotal' => $request->subtotal,
                    'iva' => $request->iva,
                    'total' => $request->total
                ]);

                foreach ($request->productos as $item) {
                    // Obtener el producto completo para acceder a su información de IVA
                    $producto = Producto::find($item['id']);
                    
                    // Calcular cantidad real considerando unidades de conversión
                    $cantidadCompra = $item['cantidad'];
                    $unidadMedida = $item['unidad_medida'] ?? $producto->unidad_medida ?? 'UND';
                    $factorConversion = $item['factor_conversion'] ?? 1;
                    
                    // Cantidad que se agregará al stock (en unidad base del producto)
                    $cantidadStock = $cantidadCompra * $factorConversion;
                    
                    // Calcular subtotal
                    $subtotal = $cantidadCompra * $item['precio'];
                    
                    // Usar el servicio de validación para obtener y validar el IVA
                    $porcentaje_iva = IvaValidationService::obtenerPorcentajeIvaProducto($producto);
                    $tiene_iva = $porcentaje_iva > 0;
                    $valor_iva = IvaValidationService::calcularValorIva($subtotal, $porcentaje_iva);
                    $total_con_iva = $subtotal + $valor_iva;
                    
                    // Registrar información para auditoría
                    Log::info('Cálculo de IVA en detalle de compra', [
                        'producto_id' => $producto->id,
                        'producto_nombre' => $producto->nombre,
                        'subtotal' => $subtotal,
                        'tiene_iva' => $tiene_iva,
                        'porcentaje_iva' => $porcentaje_iva,
                        'valor_iva' => $valor_iva,
                        'total_con_iva' => $total_con_iva
                    ]);
                    
                    // Crear detalle de compra con información de IVA y unidades
                    $compra->detalles()->create([
                        'producto_id' => $item['id'],
                        'cantidad' => $cantidadCompra,
                        'unidad_medida' => $unidadMedida,
                        'factor_conversion' => $factorConversion,
                        'cantidad_stock' => $cantidadStock,
                        'precio_unitario' => $item['precio'],
                        'subtotal' => $subtotal,
                        'tiene_iva' => $tiene_iva,
                        'porcentaje_iva' => $porcentaje_iva,
                        'valor_iva' => $valor_iva,
                        'total_con_iva' => $total_con_iva
                    ]);

                    // Incrementar stock con la cantidad convertida
                    $producto->increment('stock', $cantidadStock);
                }
                
                // Generar comprobante contable usando el nuevo servicio
                try {
                    $contabilidadService = new ContabilidadService();
                    $comprobante = $contabilidadService->generarComprobanteCompra($compra);
                    
                    Log::info('Comprobante contable generado para compra', [
                        'compra_id' => $compra->id,
                        'comprobante_id' => $comprobante->id,
                        'prefijo' => $comprobante->prefijo,
                        'numero' => $comprobante->numero
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error al generar comprobante contable para compra', [
                        'compra_id' => $compra->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // No revertimos la transacción, la compra se registra igual
                }
            });

            return redirect()->route('compras.index')
                           ->with('success', 'Compra registrada exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al registrar la compra')->withInput();
        }
    }

    public function show(Compra $compra)
    {
        $compra->load(['detalles.producto', 'proveedor', 'usuario']);
        return view('compras.show', compact('compra'));
    }

    public function print(Compra $compra)
{
    $compra->load(['detalles.producto', 'proveedor']);
    // Obtener la primera empresa (asumiendo que solo hay una)
    $empresa = Empresa::first();
    
    if (!$empresa) {
        // Si no hay empresa configurada, crear una por defecto
        $empresa = Empresa::create([
            'nombre_comercial' => config('app.name', 'Mi Empresa'),
            'razon_social' => 'Razón Social por Defecto',
            'nit' => '000000000-0',
            'direccion' => 'Dirección por Defecto',
            'telefono' => '000000000',
            'email' => 'email@default.com',
            'regimen_tributario' => 'común'
        ]);
    }
    
    return view('compras.print', compact('compra', 'empresa'));
}
}