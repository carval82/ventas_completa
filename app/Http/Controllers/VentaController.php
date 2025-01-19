<?php

namespace App\Http\Controllers;


use App\Models\Venta;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\DetalleVenta;
use App\Models\StockUbicacion;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class VentaController extends Controller
{
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
       date_default_timezone_set('America/Bogota');
       
       $clientes = Cliente::where('estado', true)->get();
       $productos = Producto::where('estado', true)
                          ->where('stock', '>', 0)
                          ->get();
       
       return view('ventas.create', [
           'clientes' => $clientes,
           'productos' => $productos,
           'fecha_actual' => now()->format('d/m/Y h:i A')
       ]);
   }

   public function store(Request $request)
   {
       $request->validate([
           'cliente_id' => 'required|exists:clientes,id',
           'productos' => 'required|array',
           'productos.*.id' => 'required|exists:productos,id',
           'productos.*.cantidad' => 'required|integer|min:1',
           'subtotal' => 'required|numeric|min:0',
           'iva' => 'required|numeric|min:0',
           'total' => 'required|numeric|min:0'
       ]);

       try {
           DB::transaction(function () use ($request) {
               $venta = Venta::create([
                   'numero_factura' => 'F' . time(),
                   'fecha_venta' => now(),
                   'cliente_id' => $request->cliente_id,
                   'user_id' => Auth::id(),
                   'subtotal' => $request->subtotal,
                   'iva' => $request->iva,
                   'total' => $request->total
               ]);

               foreach ($request->productos as $item) {
                   $producto = Producto::findOrFail($item['id']);
                   
                   if ($producto->stock < $item['cantidad']) {
                       throw new \Exception("Stock insuficiente para {$producto->nombre}");
                   }

                   $venta->detalles()->create([
                       'producto_id' => $item['id'],
                       'cantidad' => $item['cantidad'],
                       'precio_unitario' => $item['precio'],
                       'subtotal' => $item['cantidad'] * $item['precio']
                   ]);

                   $producto->decrement('stock', $item['cantidad']);
               }
           });

           return redirect()->route('ventas.index')
                          ->with('success', 'Venta registrada exitosamente');

       } catch (\Exception $e) {
           return back()->with('error', $e->getMessage());
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
}