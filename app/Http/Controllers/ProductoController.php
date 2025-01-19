<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = Producto::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('codigo', 'LIKE', "%{$search}%")
                  ->orWhere('nombre', 'LIKE', "%{$search}%");
            });
        }

        $productos = $query->latest()->paginate(10);
        return view('productos.index', compact('productos'));
    }

    public function create(Request $request)
    {
        try {
            // Guardar en sesión el origen de la petición
            if ($request->has('return_to')) {
                session(['return_to' => $request->return_to]);
                Log::info('Origen de la petición guardado:', ['return_to' => $request->return_to]);
            }
            
            return view('productos.create', [
                'fromCompras' => session('return_to') === 'compras',
                'fromVentas' => session('return_to') === 'ventas'
            ]);
        } catch (\Exception $e) {
            Log::error('Error en create:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    public function store(Request $request)
    {
        try {
            Log::info('Iniciando creación de producto:', [
                'request_data' => $request->all()
            ]);
    
            // Determinar regla de stock según origen
            $stockRule = session('return_to') === 'ventas' ? 'required|numeric|min:1' : 'required|numeric|min:0';
            
            // Validar datos
            $validated = $request->validate([
                'codigo' => 'required|unique:productos',
                'nombre' => 'required',
                'descripcion' => 'required',
                'precio_compra' => 'required|numeric|min:0',
                'precio_venta' => 'required|numeric|min:0',
                'stock_minimo' => 'required|numeric|min:0',
                                'stock' => $stockRule
            ]);
    
            Log::info('Datos validados correctamente:', $validated);
    
            // Asegurar estado activo
            $validated['estado'] = true;
    
            // Crear el producto
            $producto = Producto::create($validated);
            
            Log::info('Producto creado exitosamente:', [
                'producto_id' => $producto->id,
                'producto_data' => $producto->toArray()
            ]);
    
            // Verificar origen usando la sesión
            $returnTo = session('return_to');
            Log::info('Return to:', ['return_to' => $returnTo]);
    
            if ($returnTo) {
                session()->forget('return_to'); // Limpiar la sesión
                Log::info('Redirigiendo a:', ['return_to' => $returnTo]);
                
                if ($returnTo === 'compras') {
                    return redirect()->route('compras.create')
                        ->with('producto_creado', $producto->toJson());
                } elseif ($returnTo === 'ventas') {
                    return redirect()->route('ventas.create')
                        ->with('producto_creado', $producto->toJson());
                }
            }
    
            Log::info('Redirigiendo al índice de productos');
            return redirect()->route('productos.index')
                ->with('success', 'Producto creado exitosamente');
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Error de validación:', [
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ]);
            return back()->withErrors($e->errors())->withInput();
    
        } catch (\Exception $e) {
            Log::error('Error creando producto:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return back()->with('error', 'Error al crear el producto: ' . $e->getMessage())
                ->withInput();
        }
    }


    public function show(Producto $producto)
    {
        $producto->load('detalleVentas.venta');
        return view('productos.show', compact('producto'));
    }

    public function edit(Producto $producto)
    {
        // Obtener proveedores activos con sus datos completos
        $proveedores = Proveedor::where('estado', true)
                               ->select('id', 'razon_social', 'nit')
                               ->get();

        // Obtener los proveedores ya asignados al producto
        $proveedoresAsignados = $producto->proveedores;

        return view('productos.edit', compact('producto', 'proveedores', 'proveedoresAsignados'));
    }

    public function update(Request $request, Producto $producto)
    {
        $request->validate([
            'codigo' => 'required|unique:productos,codigo,' . $producto->id,
            'nombre' => 'required',
            'descripcion' => 'required',
            'precio_compra' => 'required|numeric|min:0',
            'precio_venta' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'estado' => 'required|boolean'
        ]);

        try {
            $producto->update($request->all());
            return redirect()->route('productos.index')
                           ->with('success', 'Producto actualizado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error actualizando producto: ' . $e->getMessage());
            return back()->with('error', 'Error al actualizar el producto')
                        ->withInput();
        }
    }

    public function destroy(Producto $producto)
    {
        try {
            $producto->delete();
            return redirect()->route('productos.index')
                           ->with('success', 'Producto eliminado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error eliminando producto: ' . $e->getMessage());
            return back()->with('error', 'Error al eliminar el producto');
        }
    }

    /**
     * Almacena un nuevo producto vía API
     */
    public function apiStore(Request $request)
    {
        try {
            Log::info('Datos recibidos:', $request->all());
    
            $validated = $request->validate([
                'codigo' => 'required|unique:productos',
                'nombre' => 'required',
                'descripcion' => 'required',
                'precio_compra' => 'required|numeric|min:0',
                'precio_venta' => 'required|numeric|min:0',
                'stock_minimo' => 'required|numeric|min:0'
            ]);
    
            $producto = Producto::create($validated);
            Log::info('Producto creado:', $producto->toArray());
    
            return response()->json($producto, 201);
        } catch (\Exception $e) {
            Log::error('Error en apiStore:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return response()->json([
                'message' => 'Error al crear el producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function imprimirCodigoBarras($id)
    {
        $producto = Producto::findOrFail($id);
        return view('productos.barcode', compact('producto'));
    }

    public function asignarProveedor(Request $request, Producto $producto)
    {
        $request->validate([
            'proveedor_id' => 'required|exists:proveedores,id',
            'precio_compra' => 'required|numeric|min:0',
            'codigo_proveedor' => 'nullable|string|max:255'
        ]);

        try {
            // Intentar crear la asignación directamente
            $producto->proveedores()->attach($request->proveedor_id, [
                'precio_compra' => $request->precio_compra,
                'codigo_proveedor' => $request->codigo_proveedor
            ]);

            return redirect()->back()->with('success', 'Proveedor asignado correctamente');

        } catch (\Exception $e) {
            Log::error('Error al asignar proveedor:', [
                'producto_id' => $producto->id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Error al asignar el proveedor');
        }
    }

    public function removeProveedor(Request $request, Producto $producto)
    {
        try {
            $producto->proveedores()->detach($request->proveedor_id);
            return redirect()->back()->with('success', 'Proveedor eliminado correctamente');
        } catch (\Exception $e) {
            Log::error('Error al eliminar proveedor:', [
                'producto_id' => $producto->id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Error al eliminar el proveedor');
        }
    }

    public function search(Request $request)
    {
        $term = $request->input('q');
        
        $productos = Producto::where('estado', true)
            ->where(function($query) use ($term) {
                $query->where('codigo', 'LIKE', "%{$term}%")
                      ->orWhere('nombre', 'LIKE', "%{$term}%");
            })
            ->with(['stocks.ubicacion'])
            ->limit(10)
            ->get();

        return response()->json([
            'items' => $productos->map(function($producto) {
                return [
                    'id' => $producto->id,
                    'codigo' => $producto->codigo,
                    'nombre' => $producto->nombre,
                    'precio_compra' => $producto->precio_compra,
                    'stocks' => $producto->stocks->map(function($stock) {
                        return [
                            'ubicacion' => [
                                'id' => $stock->ubicacion->id,
                                'nombre' => $stock->ubicacion->nombre
                            ],
                            'stock' => $stock->stock
                        ];
                    })
                ];
            })
        ]);
    }

    public function searchApi(Request $request)
    {
        $query = $request->get('q');
        $productos = Producto::where('codigo', 'LIKE', "%{$query}%")
                            ->orWhere('nombre', 'LIKE', "%{$query}%")
                            ->get();
                            
        return response()->json(['items' => $productos]);
    }

}