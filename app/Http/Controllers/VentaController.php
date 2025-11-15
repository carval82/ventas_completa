<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\DetalleVenta;
use App\Models\StockUbicacion;
use App\Models\Empresa;
use App\Models\Credito;
use App\Models\CajaDiaria;
use App\Models\MovimientoCaja;
use App\Services\ContabilidadService;
use App\Services\IvaValidationService;
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

    public function __construct(AlegraService $alegraService = null)
    {
        // Inicializar el servicio de Alegra sin requerir inyección de dependencias
        $this->alegraService = $alegraService ?? new AlegraService();
    }

    /**
     * Vista de impresión de factura usando el formato configurado en la empresa
     */
    public function print($id)
    {
        $venta = Venta::with(['cliente', 'detalles.producto', 'usuario'])->findOrFail($id);
        
        // Obtener datos de la empresa
        $empresa = \App\Models\Empresa::first();
        
        // Generar código QR si existe el texto pero no la imagen
        if ($venta->qr_code && !str_starts_with($venta->qr_code, 'iVBOR')) {
            // El QR es texto plano, convertirlo a imagen
            Log::info('Generando QR para impresión', [
                'venta_id' => $venta->id,
                'qr_text_length' => strlen($venta->qr_code)
            ]);
            
            $qrImage = $this->generarQRImagen($venta->qr_code);
            $venta->qr_code_image = $qrImage;
            
            Log::info('QR generado', [
                'venta_id' => $venta->id,
                'qr_image_generated' => $qrImage !== null,
                'qr_image_length' => $qrImage ? strlen($qrImage) : 0
            ]);
        } else {
            Log::info('QR no generado', [
                'venta_id' => $venta->id,
                'tiene_qr_code' => $venta->qr_code !== null,
                'empieza_con_iVBOR' => $venta->qr_code ? str_starts_with($venta->qr_code, 'iVBOR') : false
            ]);
        }
        
        // Si es factura electrónica O la empresa tiene activado el formato electrónico, usar el nuevo diseño
        if ($venta->esFacturaElectronica() || ($empresa && $empresa->usar_formato_electronico)) {
            return view('ventas.print_factura_electronica', compact('venta', 'empresa'));
        }
        
        // Determinar la vista según el formato configurado
        $formato = $empresa->formato_impresion ?? '80mm';
        
        $vistas = [
            '58mm' => 'ventas.print_58mm',
            '80mm' => 'ventas.print',
            'media_carta' => 'ventas.print_media_carta'
        ];
        
        $vista = $vistas[$formato] ?? 'ventas.print';
        
        return view($vista, compact('venta', 'empresa'));
    }
    
    /**
     * Genera imagen QR en SVG (sin base64, inline)
     */
    private function generarQRImagen($texto)
    {
        try {
            // Generar SVG - retornar el SVG directo, no base64
            $svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(300)
                ->margin(1)
                ->generate($texto);
            
            Log::info('QR SVG generado', ['svg_length' => strlen($svg)]);
            
            // Retornar el SVG directamente (no base64)
            return $svg;
            
        } catch (\Exception $e) {
            Log::error('Error al generar QR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
    
    /**
     * Vista de impresión de factura en formato 58mm (forzado)
     */
    public function print58mm($id)
    {
        $venta = Venta::with(['cliente', 'detalles.producto', 'usuario'])->findOrFail($id);
        $empresa = \App\Models\Empresa::first();
        
        // Generar código QR si existe el texto
        if ($venta->qr_code && !str_starts_with($venta->qr_code, 'iVBOR')) {
            $qrImage = $this->generarQRImagen($venta->qr_code);
            $venta->qr_code_image = $qrImage;
        }
        
        // Si es factura electrónica O la empresa tiene activado el formato electrónico, usar el nuevo diseño
        if ($venta->esFacturaElectronica() || ($empresa && $empresa->usar_formato_electronico)) {
            return view('ventas.print_factura_electronica', compact('venta', 'empresa'));
        }
        
        return view('ventas.print_58mm', compact('venta', 'empresa'));
    }
    
    /**
     * Vista de impresión de factura en formato 80mm (forzado)
     */
    public function print80mm($id)
    {
        $venta = Venta::with(['cliente', 'detalles.producto', 'usuario'])->findOrFail($id);
        $empresa = \App\Models\Empresa::first();
        
        // Generar código QR si existe el texto
        if ($venta->qr_code && !str_starts_with($venta->qr_code, 'iVBOR')) {
            $qrImage = $this->generarQRImagen($venta->qr_code);
            $venta->qr_code_image = $qrImage;
        }
        
        // Si es factura electrónica O la empresa tiene activado el formato electrónico, usar el nuevo diseño
        if ($venta->esFacturaElectronica() || ($empresa && $empresa->usar_formato_electronico)) {
            return view('ventas.print_factura_electronica', compact('venta', 'empresa'));
        }
        
        return view('ventas.print', compact('venta', 'empresa'));
    }

    /**
     * Vista de impresión de factura en formato media carta
     */
    public function printMediaCarta($id)
    {
        $venta = Venta::with(['cliente', 'detalles.producto', 'usuario'])->findOrFail($id);
        
        // Obtener datos de la empresa
        $empresa = \App\Models\Empresa::first();
        
        // Generar código QR si existe el texto
        if ($venta->qr_code && !str_starts_with($venta->qr_code, 'iVBOR')) {
            $qrImage = $this->generarQRImagen($venta->qr_code);
            $venta->qr_code_image = $qrImage;
        }
        
        // Si es factura electrónica O la empresa tiene activado el formato electrónico, usar el nuevo diseño
        if ($venta->esFacturaElectronica() || ($empresa && $empresa->usar_formato_electronico)) {
            return view('ventas.print_factura_electronica', compact('venta', 'empresa'));
        }
        
        return view('ventas.print_media_carta', compact('venta', 'empresa'));
    }

    /**
     * Determina si un producto es un servicio basado en su nombre
     */
    private function esServicioPorNombre($nombre)
    {
        $nombreLower = strtolower($nombre);
        $palabrasServicio = [
            'servicio', 'instalacion', 'instalación', 'mantenimiento', 
            'reparacion', 'reparación', 'soporte', 'configuracion', 
            'configuración', 'mano de obra', 'licencia', 'internet', 
            'kaspersky', 'office', 'windows', 'implementacion', 
            'implementación', 'revision', 'revisión', 'reubicacion',
            'reubicación', 'desinstalacion', 'desinstalación'
        ];
        
        foreach ($palabrasServicio as $palabra) {
            if (strpos($nombreLower, $palabra) !== false) {
                return true;
            }
        }
        
        return false;
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
        
        // Verificar si hay una caja abierta
        $cajaAbierta = CajaDiaria::obtenerCajaAbierta();
        
        if (!$cajaAbierta) {
            return redirect()->route('cajas.create')
                ->with('error', 'Debe abrir una caja antes de realizar ventas');
        }
        
        date_default_timezone_set('America/Bogota');
        
        // Obtener otros datos necesarios
        $clientes = Cliente::where('estado', '1')->get();
        
        // Obtener productos y asegurarse de que tengan precio_final correcto
        $productos = Producto::where('estado', '1')
                            ->where('stock', '>', 0)
                            ->get()
                            ->map(function($producto) {
                                // Si no tiene precio_final, calcularlo a partir del precio_venta y el IVA
                                if (!$producto->precio_final || $producto->precio_final == 0) {
                                    $producto->precio_final = $producto->precio_venta * (1 + ($producto->iva / 100));
                                }
                                return $producto;
                            });
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
        
        // Determinar qué vista usar según el régimen de la empresa
        $data = [
            'empresa' => $empresa,
            'clientes' => $clientes,
            'productos' => $productos,
            'fecha_actual' => now()->format('d/m/Y h:i A'),
            'ultimo_numero' => $ultimo_numero,
            'ultimas_facturas' => $ultimas_facturas
        ];
        
        // Verificar si la empresa es responsable de IVA
        $regimen = $empresa->regimen_tributario ?? 'no_responsable_iva';
        
        // Si la empresa es responsable de IVA, usar el formulario con IVA
        if ($regimen === 'responsable_iva') {
            return view('ventas.create_iva', $data);
        } else {
            return view('ventas.create', $data);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            Log::info('Iniciando venta', [
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
                'iva' => $request->iva, // Siempre guardar el IVA, independientemente del tipo de factura
                'total' => $request->total,
                'metodo_pago' => $request->metodo_pago ?? 'efectivo',
                'pago' => $request->pago ?? 0,
                'devuelta' => $request->devuelta ?? 0,
                'fecha_venta' => now(),
                'caja_id' => CajaDiaria::obtenerCajaAbierta()->id
            ]);

            // Procesar productos
            foreach ($request->productos as $producto) {
                // Para servicios, usar el precio editado si está disponible
                if (isset($producto['es_servicio']) && $producto['es_servicio'] == 1) {
                    // Para servicios, usar el precio editado (precio_final si fue modificado, o precio original)
                    $precio_unitario = isset($producto['precio_final']) ? $producto['precio_final'] : $producto['precio'];
                    
                    Log::info('Procesando servicio con precio editado', [
                        'producto_id' => $producto['id'],
                        'precio_original' => $producto['precio_original'] ?? 'No disponible',
                        'precio_editado' => $precio_unitario,
                        'es_servicio' => true
                    ]);
                } else {
                    // Para productos físicos, usar precio_final si está disponible, de lo contrario usar precio
                    $precio_unitario = isset($producto['precio_final']) ? $producto['precio_final'] : $producto['precio'];
                }
                
                $subtotal = $producto['cantidad'] * $precio_unitario;
                
                // Obtener el producto para acceder a su IVA
                $productoModel = Producto::find($producto['id']);
                
                // Usar el servicio de validación para obtener y validar el IVA
                $porcentajeIva = IvaValidationService::obtenerPorcentajeIvaProducto($productoModel);
                $tieneIva = $porcentajeIva > 0;
                $valorIva = IvaValidationService::calcularValorIva($subtotal, $porcentajeIva);
                
                // Verificar si el cálculo es correcto
                $verificacion = IvaValidationService::verificarCalculoIva($subtotal, $porcentajeIva, $valorIva);
                
                if (!$verificacion) {
                    Log::warning('Cálculo de IVA incorrecto en venta, se ha recalculado', [
                        'producto_id' => $productoModel->id,
                        'nombre_producto' => $productoModel->nombre,
                        'subtotal' => $subtotal,
                        'porcentaje_iva' => $porcentajeIva
                    ]);
                }
                
                // Registrar información para auditoría
                Log::info('Cálculo de IVA en detalle de venta', [
                    'producto_id' => $productoModel->id,
                    'producto_nombre' => $productoModel->nombre,
                    'subtotal' => $subtotal,
                    'tiene_iva' => $tieneIva,
                    'porcentaje_iva' => $porcentajeIva,
                    'valor_iva' => $valorIva,
                    'total_con_iva' => $subtotal + $valorIva
                ]);
                
                // Crear el detalle de venta con campos básicos
                $detalle = $venta->detalles()->create([
                    'producto_id' => $producto['id'],
                    'cantidad' => $producto['cantidad'],
                    'precio_unitario' => $precio_unitario,
                    'subtotal' => $subtotal
                ]);
                
                // Registrar en el log para seguimiento
                Log::info('Detalle de venta creado con IVA detallado', [
                    'venta_id' => $venta->id,
                    'detalle_id' => $detalle->id,
                    'producto_id' => $producto['id'],
                    'subtotal' => $subtotal,
                    'tiene_iva' => $tieneIva,
                    'porcentaje_iva' => $porcentajeIva,
                    'valor_iva' => $valorIva,
                    'total_con_iva' => $subtotal + $valorIva
                ]);

                // Actualizar stock solo para productos físicos, no para servicios
                if (!isset($producto['es_servicio']) || $producto['es_servicio'] != 1) {
                    $productoModel->stock -= $producto['cantidad'];
                    $productoModel->save();
                    
                    Log::info('Stock actualizado para producto físico', [
                        'producto_id' => $productoModel->id,
                        'stock_anterior' => $productoModel->stock + $producto['cantidad'],
                        'cantidad_vendida' => $producto['cantidad'],
                        'stock_nuevo' => $productoModel->stock
                    ]);
                } else {
                    Log::info('Stock no actualizado para servicio', [
                        'producto_id' => $productoModel->id,
                        'nombre' => $productoModel->nombre,
                        'es_servicio' => true
                    ]);
                }
            }

            // Registrar el movimiento en la caja
            $caja = CajaDiaria::obtenerCajaAbierta();
            if ($caja) {
                MovimientoCaja::create([
                    'caja_id' => $caja->id,
                    'fecha' => now(),
                    'tipo' => 'ingreso',
                    'concepto' => 'Venta #' . $venta->numero_factura,
                    'referencia_id' => $venta->id,
                    'referencia_tipo' => 'App\\Models\\Venta',
                    'monto' => $venta->total,
                    'metodo_pago' => $venta->metodo_pago,
                    'observaciones' => 'Venta registrada automáticamente',
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }

            // Generar comprobante contable usando el nuevo servicio
            try {
                $contabilidadService = new ContabilidadService();
                $comprobante = $contabilidadService->generarComprobanteVenta($venta);
                
                Log::info('Comprobante contable generado para venta', [
                    'venta_id' => $venta->id,
                    'comprobante_id' => $comprobante->id,
                    'prefijo' => $comprobante->prefijo,
                    'numero' => $comprobante->numero
                ]);
            } catch (\Exception $e) {
                Log::error('Error al generar comprobante contable para venta', [
                    'venta_id' => $venta->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // No revertimos la transacción, la venta se registra igual
            }
            
            // Generar QR local si está activado en empresa y NO es factura electrónica (sin alegra_id)
            try {
                $empresa = \App\Models\Empresa::first();
                
                Log::info('Verificando QR local', [
                    'venta_id' => $venta->id,
                    'empresa_existe' => $empresa ? 'Sí' : 'No',
                    'generar_qr_local' => $empresa ? ($empresa->generar_qr_local ? 'Sí' : 'No') : 'N/A',
                    'alegra_id' => $venta->alegra_id ?? 'NULL'
                ]);
                
                if ($empresa && $empresa->generar_qr_local && !$venta->alegra_id) {
                    $qrService = new \App\Services\QRLocalService();
                    $qrData = $qrService->generarCUFEyQR($venta, $empresa);
                    
                    // Asignar valores directamente y guardar
                    $venta->cufe_local = $qrData['cufe'];
                    $venta->qr_local = $qrData['qr'];
                    $venta->save();
                    
                    Log::info('QR local generado para venta', [
                        'venta_id' => $venta->id,
                        'cufe_generado' => substr($qrData['cufe'], 0, 20) . '...',
                        'qr_generado' => $qrData['qr'] ? 'Sí' : 'No',
                        'qr_length' => $qrData['qr'] ? strlen($qrData['qr']) : 0
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error al generar QR local', [
                    'venta_id' => $venta->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // No revertimos la transacción, la venta se registra igual
            }
            
            // Completar la venta normal primero
            DB::commit();
            
            // Si es factura electrónica, procesar en un paso separado
            if ($request->tipo_factura === 'electronica' && $request->has('generar_fe')) {
                try {
                    return $this->generarFacturaElectronica($venta);
                } catch (\Exception $e) {
                    Log::error('Error al generar factura electrónica', [
                        'venta_id' => $venta->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    // La venta ya se completó, solo devolvemos el error de FE
                    return response()->json([
                        'success' => true,
                        'fe_success' => false,
                        'message' => 'Venta creada correctamente, pero hubo un error al generar la factura electrónica',
                        'error' => $e->getMessage(),
                        'data' => $venta,
                        'print_url' => route('ventas.print', $venta->id),
                        'redirect_url' => route('ventas.create'),
                        'show_url' => route('ventas.show', $venta->id)
                    ]);
                }
            }

            // Si no es factura electrónica o no se solicitó generarla, devolver éxito
            return response()->json([
                'success' => true,
                'message' => 'Venta creada correctamente',
                'data' => $venta,
                'print_url' => route('ventas.print', $venta->id),
                'redirect_url' => route('ventas.create')
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

    /**
     * Genera la factura electrónica para una venta existente
     */
    public function generarFacturaElectronica(Venta $venta)
    {
        try {
            Log::info('Iniciando generación de factura electrónica', [
                'venta_id' => $venta->id
            ]);
            
            // Sincronizar cliente con Alegra si no tiene id_alegra
            $cliente = Cliente::find($venta->cliente_id);
            if (!$cliente->id_alegra) {
                $resultadoSync = $cliente->syncToAlegra();
                if (!$resultadoSync['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error al sincronizar cliente con Alegra',
                        'error' => isset($resultadoSync['error']) ? $resultadoSync['error'] : 'Error desconocido'
                    ], 400);
                }
                
                Log::info('Cliente sincronizado con Alegra', [
                    'cliente_id' => $cliente->id,
                    'id_alegra' => $cliente->id_alegra
                ]);
            }
            
            // Preparar items y sincronizar productos con Alegra
            // Obtener solo los detalles con productos válidos
            $detalles = $venta->detalles()->whereHas('producto')->get();
            
            Log::info('📥 DETALLES OBTENIDOS DE LA BD', [
                'venta_id' => $venta->id,
                'total_detalles_bd' => count($detalles),
                'detalles_bd' => $detalles->map(function($d) {
                    return [
                        'detalle_id' => $d->id,
                        'producto_id' => $d->producto_id,
                        'producto_nombre' => $d->producto->nombre ?? 'Sin nombre',
                        'cantidad' => $d->cantidad,
                        'precio' => $d->precio_unitario,
                        'fecha_creacion' => $d->created_at
                    ];
                })->toArray()
            ]);
            
            // Array para agrupar detalles por producto_id y precio
            $detallesAgrupados = [];
            $productosLog = [];
            $productosYaAgregados = []; // Registro de productos ya procesados para evitar duplicados
            
            // Primero, registramos todos los productos para depuración
            foreach ($detalles as $detalle) {
                $productoId = $detalle->producto_id;
                $producto = $detalle->producto;
                
                // Registrar para depuración
                $productosLog[] = [
                    'id' => $productoId,
                    'nombre' => $producto->nombre,
                    'cantidad' => $detalle->cantidad,
                    'precio' => $detalle->precio_unitario,
                    'iva_producto' => null // IVA se maneja por régimen tributario
                ];
            }
            
            // Ahora agrupamos los detalles con una lógica más estricta
            foreach ($detalles as $detalle) {
                $productoId = $detalle->producto_id;
                $producto = $detalle->producto;
                $precioUnitario = (float)$detalle->precio_unitario;
                
                // Crear una clave única que combine producto_id y precio formateado para evitar problemas de precisión
                $precioFormateado = number_format($precioUnitario, 2, '.', '');
                $claveUnica = $productoId . '_' . $precioFormateado;
                
                // Verificar si ya existe este producto con este precio
                if (!isset($detallesAgrupados[$claveUnica])) {
                    $detallesAgrupados[$claveUnica] = [
                        'producto' => $producto,
                        'cantidad' => $detalle->cantidad,
                        'precio_unitario' => $precioUnitario
                    ];
                    
                    // Registrar que este producto+precio ya fue agregado
                    $productosYaAgregados[$claveUnica] = true;
                } else {
                    // Sumar cantidades para el mismo producto con el mismo precio
                    $detallesAgrupados[$claveUnica]['cantidad'] += $detalle->cantidad;
                }
            }
            
            // Registrar los productos encontrados para depuración
            Log::info('Productos en la venta antes de agrupar', [
                'venta_id' => $venta->id,
                'productos' => $productosLog,
                'total_productos' => count($productosLog)
            ]);
            
            // Mantener el array asociativo para preservar las claves únicas y evitar duplicados
            // NO convertir a array indexado para no perder las claves únicas
            $detallesAgrupados = array_map(function($detalle) {
                return [
                    'producto' => $detalle['producto'],
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario'],
                    'iva' => 0 // IVA se calcula según régimen tributario
                ];
            }, $detallesAgrupados);
            
            Log::info('Detalles agrupados para Alegra', [
                'venta_id' => $venta->id,
                'total_agrupados' => count($detallesAgrupados),
                'detalles_agrupados' => array_map(function($clave, $detalle) {
                    return [
                        'clave_unica' => $clave,
                        'producto_id' => $detalle['producto']->id,
                        'nombre' => $detalle['producto']->nombre,
                        'cantidad' => $detalle['cantidad'],
                        'precio' => $detalle['precio_unitario']
                    ];
                }, array_keys($detallesAgrupados), $detallesAgrupados)
            ]);
            
            $items = [];
            
            // LOG CRÍTICO: Ver detalles agrupados ANTES de procesarlos
            Log::info('🔍 DETALLES AGRUPADOS ANTES DE PROCESAR', [
                'total_detalles_agrupados' => count($detallesAgrupados),
                'claves' => array_keys($detallesAgrupados),
                'detalles_completos' => array_map(function($clave, $detalle) {
                    return [
                        'clave' => $clave,
                        'producto_id' => $detalle['producto']->id,
                        'producto_nombre' => $detalle['producto']->nombre,
                        'precio' => $detalle['precio_unitario'],
                        'cantidad' => $detalle['cantidad']
                    ];
                }, array_keys($detallesAgrupados), $detallesAgrupados)
            ]);
            
            // Procesar los detalles agrupados - Mantener las claves únicas
            foreach ($detallesAgrupados as $claveUnica => $detalleAgrupado) {
                $producto = $detalleAgrupado['producto'];
                
                Log::info('🔄 PROCESANDO DETALLE', [
                    'clave_unica' => $claveUnica,
                    'producto_id' => $producto->id,
                    'producto_nombre' => $producto->nombre,
                    'id_alegra' => $producto->id_alegra
                ]);
                
                // Verificar que el producto tenga un ID en Alegra
                if (!$producto->id_alegra) {
                    $resultadoSync = $producto->syncToAlegra();
                    if (!$resultadoSync['success']) {
                        Log::warning('Error al sincronizar producto con Alegra', [
                            'producto_id' => $producto->id,
                            'nombre_producto' => $producto->nombre,
                            'error' => isset($resultadoSync['error']) ? $resultadoSync['error'] : 'Error desconocido'
                        ]);
                        // Continuamos con el siguiente producto
                        continue;
                    }
                    
                    Log::info('Producto sincronizado con Alegra', [
                        'producto_id' => $producto->id,
                        'nombre_producto' => $producto->nombre,
                        'id_alegra' => $producto->id_alegra
                    ]);
                }
                
                // Obtener el precio con IVA del detalle agrupado
                $precioConIVA = (float)$detalleAgrupado['precio_unitario'];
                
                // Obtener el IVA según el régimen tributario de la empresa
                $empresa = \App\Models\Empresa::first();
                $ivaProducto = 0; // Por defecto para no responsables de IVA
                
                // Solo aplicar IVA si la empresa es responsable de IVA
                if ($empresa && $empresa->esResponsableIva()) {
                    $ivaProducto = 19.0; // 19% para responsables de IVA
                    
                    // Si el producto tiene un valor_iva específico, usarlo
                    if ($producto->valor_iva && $producto->valor_iva > 0) {
                        // Calcular porcentaje desde valor_iva si está disponible
                        $precioSinIva = $producto->precio_venta - $producto->valor_iva;
                        if ($precioSinIva > 0) {
                            $ivaProducto = ($producto->valor_iva / $precioSinIva) * 100;
                        }
                    }
                }
                
                Log::info('IVA calculado según régimen tributario', [
                    'producto_id' => $producto->id,
                    'nombre_producto' => $producto->nombre,
                    'regimen_tributario' => $empresa->regimen_tributario ?? 'no_definido',
                    'iva_aplicado' => $ivaProducto
                ]);
                
                // Calcular precio sin IVA para Alegra (como lo espera Alegra)
                $precioConIVA = (float)$detalleAgrupado['precio_unitario'];
                $ivaRedondeado = round($ivaProducto, 0); // Redondear a entero para evitar problemas con decimales
                
                // Verificar si es un servicio para logging especial
                $esServicio = $this->esServicioPorNombre($producto->nombre);
                if ($esServicio) {
                    Log::info('Enviando servicio con precio editado a Alegra', [
                        'producto_id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'precio_con_iva' => $precioConIVA,
                        'precio_original_producto' => $producto->precio_venta,
                        'es_servicio' => true,
                        'precio_fue_editado' => $precioConIVA != $producto->precio_final,
                        'regimen_iva' => $empresa->regimen_tributario ?? 'no_definido'
                    ]);
                }
                
                // Calcular el precio sin IVA según el régimen
                if ($empresa && $empresa->esResponsableIva() && $ivaProducto > 0) {
                    // Para responsables de IVA: dividir por (1 + IVA/100)
                    $precioSinIVA = round($precioConIVA / (1 + ($ivaProducto/100)), 2);
                } else {
                    // Para no responsables de IVA: el precio ya está sin IVA
                    $precioSinIVA = $precioConIVA;
                }
                
                // Crear el item con precio SIN IVA como lo espera Alegra
                $itemData = [
                    'id' => (int)$producto->id_alegra,
                    'price' => $precioSinIVA, // Precio SIN IVA para Alegra
                    'quantity' => (float)$detalleAgrupado['cantidad']
                ];
                
                // Solo agregar impuestos si la empresa es responsable de IVA
                if ($empresa && $empresa->esResponsableIva() && $ivaProducto > 0) {
                    // Calcular el valor del IVA para este ítem
                    $valorIVA = round($precioSinIVA * $detalleAgrupado['cantidad'] * ($ivaRedondeado/100), 2);
                    
                    // Añadir impuestos de tres formas diferentes para asegurar compatibilidad con Alegra
                    $itemData['taxes'] = [
                        [
                            'id' => 1, // ID estándar del IVA en Alegra
                            'percentage' => (float)$ivaRedondeado,
                            'value' => $valorIVA
                        ]
                    ];
                    
                    // También añadir el campo tax (singular) que algunas versiones de Alegra usan
                    $itemData['tax'] = [
                        [
                            'id' => 1,
                            'percentage' => (float)$ivaRedondeado,
                            'value' => $valorIVA
                        ]
                    ];
                    
                    // Añadir el campo taxRate para mayor compatibilidad
                    $itemData['taxRate'] = (float)$ivaRedondeado;
                } else {
                    // Para no responsables de IVA, no agregar impuestos
                    $valorIVA = 0;
                    $itemData['taxes'] = [];
                    $itemData['tax'] = [];
                    $itemData['taxRate'] = 0;
                }
                
                // Registrar en log para depuración
                Log::info('Añadiendo producto a factura Alegra', [
                    'producto_id' => $producto->id,
                    'nombre_producto' => $producto->nombre,
                    'clave_unica' => $claveUnica,
                    'iva_porcentaje' => (float)$ivaProducto,
                    'precio_con_iva' => $precioConIVA,
                    'precio_sin_iva' => $precioSinIVA,
                    'cantidad' => (float)$detalleAgrupado['cantidad'],
                    'item_data' => $itemData
                ]);
                
                $items[] = $itemData;
                
                Log::info('✅ ITEM AGREGADO AL ARRAY', [
                    'total_items_ahora' => count($items),
                    'ultimo_item_agregado' => [
                        'id_alegra' => $itemData['id'],
                        'producto_nombre' => $producto->nombre,
                        'precio' => $itemData['price'],
                        'cantidad' => $itemData['quantity']
                    ]
                ]);
            }
            
            if (empty($items)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay productos válidos para generar la factura electrónica'
                ], 400);
            }

            // Obtener la plantilla de factura electrónica
            $plantillaId = $venta->plantilla_factura ?: 19; // Usar la plantilla por defecto si no se especifica

            // Calcular totales de impuestos para la factura completa
            $totalImpuestos = [];
            $totalIVA = 0;
            
            foreach ($items as $item) {
                if (isset($item['taxes']) && !empty($item['taxes'])) {
                    foreach ($item['taxes'] as $tax) {
                        if ($tax['id'] == 1) { // IVA
                            $totalIVA += isset($tax['value']) ? $tax['value'] : ($item['price'] * $item['quantity'] * ($tax['percentage']/100));
                        }
                    }
                }
            }
            
            // Preparar datos para la factura electrónica
            $alegraData = [
                'client' => [
                    'id' => (int)$cliente->id_alegra
                ],
                'items' => $items,
                'date' => date('Y-m-d', strtotime($venta->fecha_venta)),
                'dueDate' => date('Y-m-d', strtotime($venta->fecha_venta)),
                'paymentForm' => 'CASH',
                'paymentMethod' => 'CASH',
                'payment' => [
                    'paymentMethod' => ['id' => 10],  // 10 = Efectivo según DIAN
                    'account' => ['id' => 1]          // Cuenta por defecto
                ],
                'numberTemplate' => [
                    'id' => (int)$plantillaId
                ],
                // Añadir información de impuestos a nivel de factura
                'totalTaxes' => [
                    [
                        'id' => 1,
                        'name' => 'IVA',
                        'percentage' => 19,
                        'amount' => round($totalIVA, 2)
                    ]
                ],
                // Incluir el subtotal, IVA y total para mayor claridad
                'subtotal' => round($venta->subtotal, 2),
                'total' => round($venta->total, 2),
                'tax' => round($venta->iva, 2)
            ];

            Log::info('🚀 DATOS FINALES PARA ALEGRA', [
                'total_items' => count($items),
                'items' => array_map(function($item, $index) {
                    return [
                        'posicion' => $index + 1,
                        'id_alegra' => $item['id'],
                        'precio' => $item['price'],
                        'cantidad' => $item['quantity']
                    ];
                }, $items, array_keys($items)),
                'subtotal_venta' => $venta->subtotal,
                'total_venta' => $venta->total
            ]);
            
            Log::info('Preparando datos para Alegra', [
                'alegra_data' => $alegraData
            ]);

            Log::info('Preparando para enviar factura a Alegra usando AlegraService');
            
            // Usar el servicio AlegraService para crear la factura
            $alegraService = app(\App\Http\Services\AlegraService::class);
            
            // Enviar la solicitud a Alegra directamente
            $result = $alegraService->crearFactura($alegraData);
            
            Log::info('Respuesta de Alegra recibida', [
                'success' => $result['success'],
                'data' => isset($result['data']) ? json_encode($result['data']) : null,
                'error' => $result['error'] ?? null
            ]);
            
            // Formatear la respuesta para mantener compatibilidad con el código existente
            $response = [
                'success' => $result['success'],
                'data' => $result['data'] ?? null,
                'error' => $result['error'] ?? null
            ];
            
            Log::info('Procesando respuesta de Alegra', [
                'response' => $response
            ]);

            if (isset($response['success']) && $response['success']) {
                $data = $response['data'];
                
                // Actualizar venta con datos iniciales de Alegra
                $venta->update([
                    'alegra_id' => $data['id'],
                    'numero_factura_alegra' => $data['numberTemplate']['fullNumber'] ?? ($data['numberTemplate']['prefix'] ?? '') . ($data['numberTemplate']['formattedNumber'] ?? '') ?? null,
                    'url_pdf_alegra' => isset($data['pdfUrl']) ? $data['pdfUrl'] : null,
                    'cufe' => isset($data['stamp']) ? ($data['stamp']['cufe'] ?? null) : null,
                    'qr_code' => isset($data['stamp']) ? ($data['stamp']['barCodeContent'] ?? null) : null,
                    'estado_dian' => isset($data['stamp']) ? ($data['stamp']['legalStatus'] ?? null) : null
                ]);
                
                // Si no hay QR en la respuesta inicial, consultar la factura para obtenerlo
                if (!isset($data['stamp']['barCodeContent']) || !$data['stamp']['barCodeContent']) {
                    Log::info('QR no disponible en respuesta inicial, consultando factura...', [
                        'venta_id' => $venta->id,
                        'alegra_id' => $data['id']
                    ]);
                    
                    // Esperar 2 segundos para que Alegra procese el stamp
                    sleep(2);
                    
                    // Consultar la factura para obtener el QR
                    $facturaCompleta = $alegraService->obtenerFactura($data['id']);
                    
                    if (isset($facturaCompleta['success']) && $facturaCompleta['success']) {
                        $facturaData = $facturaCompleta['data'];
                        
                        if (isset($facturaData['stamp']['barCodeContent'])) {
                            $venta->update([
                                'cufe' => $facturaData['stamp']['cufe'] ?? null,
                                'qr_code' => $facturaData['stamp']['barCodeContent'] ?? null,
                                'estado_dian' => $facturaData['stamp']['legalStatus'] ?? null
                            ]);
                            
                            Log::info('QR obtenido exitosamente de consulta posterior', [
                                'venta_id' => $venta->id,
                                'cufe' => substr($facturaData['stamp']['cufe'] ?? '', 0, 20) . '...'
                            ]);
                        }
                    }
                }

                // Intentar enviar automáticamente la factura a la DIAN para obtener CUFE y QR definitivos
                try {
                    Log::info('Enviando factura electrónica a la DIAN automáticamente', [
                        'venta_id' => $venta->id,
                        'alegra_id' => $venta->alegra_id
                    ]);

                    $resultadoDian = $alegraService->enviarFacturaADian($venta->alegra_id);

                    if ($resultadoDian['success']) {
                        $datosDian = $resultadoDian['data'] ?? [];

                        $venta->update([
                            'estado_dian' => $datosDian['status'] ?? $venta->estado_dian,
                            'cufe' => $datosDian['cufe'] ?? $venta->cufe,
                            'qr_code' => $datosDian['qrCode'] ?? $venta->qr_code,
                        ]);

                        Log::info('Factura electrónica enviada a DIAN correctamente desde generarFacturaElectronica', [
                            'venta_id' => $venta->id,
                            'estado_dian' => $venta->estado_dian,
                            'cufe_present' => !empty($venta->cufe),
                            'qr_present' => !empty($venta->qr_code)
                        ]);

                        // Intentar varias veces obtener CUFE/QR desde Alegra antes de responder al frontend
                        if (empty($venta->cufe) || empty($venta->qr_code)) {
                            Log::info('CUFE/QR no presentes tras envío a DIAN, iniciando polling de detalles completos de factura', [
                                'venta_id' => $venta->id,
                                'alegra_id' => $venta->alegra_id
                            ]);

                            $intentosMaximos = 6; // 6 intentos
                            $esperaSegundos = 5; // 5 segundos entre intentos (30s máx)

                            for ($intento = 1; $intento <= $intentosMaximos; $intento++) {
                                Log::info('Intento de actualización de datos DIAN', [
                                    'venta_id' => $venta->id,
                                    'alegra_id' => $venta->alegra_id,
                                    'intento' => $intento
                                ]);

                                $detallesCompleto = $alegraService->obtenerDetalleFacturaCompleto($venta->alegra_id);

                                if ($detallesCompleto['success']) {
                                    $cufeFinal = $detallesCompleto['cufe'] ?? null;
                                    $qrFinal = $detallesCompleto['qrCode'] ?? null;

                                    if ($cufeFinal || $qrFinal) {
                                        $venta->update([
                                            'cufe' => $cufeFinal ?: $venta->cufe,
                                            'qr_code' => $qrFinal ?: $venta->qr_code,
                                        ]);

                                        Log::info('CUFE/QR actualizados desde obtenerDetalleFacturaCompleto durante polling', [
                                            'venta_id' => $venta->id,
                                            'intento' => $intento,
                                            'cufe_present' => !empty($venta->cufe),
                                            'qr_present' => !empty($venta->qr_code)
                                        ]);
                                        // En cuanto tengamos datos, salimos del bucle
                                        break;
                                    } else {
                                        Log::info('obtenerDetalleFacturaCompleto aún no devuelve CUFE/QR, reintentando si hay intentos restantes', [
                                            'venta_id' => $venta->id,
                                            'alegra_id' => $venta->alegra_id,
                                            'intento' => $intento
                                        ]);
                                    }
                                } else {
                                    Log::warning('Error al obtener detalles completos de factura durante polling', [
                                        'venta_id' => $venta->id,
                                        'alegra_id' => $venta->alegra_id,
                                        'intento' => $intento,
                                        'error' => $detallesCompleto['error'] ?? 'Error desconocido'
                                    ]);
                                }

                                // Si no es el último intento, esperar antes de volver a intentar
                                if ($intento < $intentosMaximos) {
                                    sleep($esperaSegundos);
                                }
                            }
                        }
                    } else {
                        Log::warning('No se pudo enviar automáticamente la factura a DIAN', [
                            'venta_id' => $venta->id,
                            'alegra_id' => $venta->alegra_id,
                            'message' => $resultadoDian['message'] ?? 'Error desconocido'
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Excepción al intentar enviar factura a DIAN automáticamente', [
                        'venta_id' => $venta->id,
                        'alegra_id' => $venta->alegra_id,
                        'error' => $e->getMessage()
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'fe_success' => true,
                    'message' => 'Venta y factura electrónica creadas correctamente',
                    'data' => $venta,
                    // URLs para distintas formas de impresión
                    'html_url' => route('facturas.electronicas.imprimir', $venta->id),
                    'pdf_url' => route('facturas.electronicas.descargar-pdf', $venta->id),
                    'tirilla_url' => route('facturas.electronicas.imprimir-tirilla', $venta->id),
                    // print_url se mantiene por compatibilidad (apunta al HTML)
                    'print_url' => route('facturas.electronicas.imprimir', $venta->id),
                    'redirect_url' => route('ventas.create'),
                    'show_url' => route('ventas.show', $venta->id)
                ]);
            }

            // Determinar un mensaje de error más específico
            $errorMessage = 'Error al generar la factura electrónica';
            $errorDetail = isset($response['error']) ? $response['error'] : 'Error desconocido';
            
            // Analizar el tipo de error para dar mensajes más específicos
            if (isset($response['data']) && is_array($response['data'])) {
                if (isset($response['data']['message'])) {
                    $errorDetail = $response['data']['message'];
                }
                
                // Detectar errores comunes de Alegra
                if (strpos($errorDetail, 'authentication') !== false) {
                    $errorMessage = 'Error de autenticación con Alegra. Verifique sus credenciales.';
                } elseif (strpos($errorDetail, 'client') !== false) {
                    $errorMessage = 'Error con los datos del cliente en Alegra.';
                } elseif (strpos($errorDetail, 'item') !== false || strpos($errorDetail, 'product') !== false) {
                    $errorMessage = 'Error con los productos en Alegra.';
                }
            }
            
            Log::warning('Error en factura electrónica', [
                'mensaje' => $errorMessage,
                'detalle' => $errorDetail,
                'venta_id' => $venta->id
            ]);
            
            return response()->json([
                'success' => true,
                'fe_success' => false,
                'message' => 'Venta creada correctamente, pero hubo un error al generar la factura electrónica',
                'error_message' => $errorMessage,
                'error_detail' => $errorDetail,
                'data' => $venta,
                'print_url' => route('ventas.print', $venta->id),
                'redirect_url' => route('ventas.create'),
                'show_url' => route('ventas.show', $venta->id)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al generar factura electrónica', [
                'venta_id' => $venta->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Emitir una factura electrónica a la DIAN para una venta
     * 
     * @param Request $request
     * @param int $id ID de la venta
     * @return \Illuminate\Http\JsonResponse
     */
    public function emitirFacturaElectronicaDIAN(Request $request, $id)
    {
        try {
            // Obtener la venta
            $venta = Venta::with(['detalles.producto', 'cliente', 'usuario'])->findOrFail($id);
            
            // Verificar que la venta tenga una factura en Alegra
            if (!$venta->id_factura_alegra) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta venta no tiene una factura en Alegra. Primero debe generar la factura.'
                ], 400);
            }
            
            // Instanciar el servicio de Alegra
            $alegraService = new \App\Http\Services\AlegraService();
            
            // Emitir la factura electrónica a la DIAN
            $resultado = $alegraService->emitirFacturaElectronica($venta->id_factura_alegra);
            
            if ($resultado['success']) {
                // Actualizar el estado de la factura electrónica en la venta
                $venta->estado_fe = 'enviada_dian';
                $venta->fecha_envio_dian = now();
                $venta->save();
                
                Log::info('Factura electrónica emitida correctamente a la DIAN', [
                    'venta_id' => $venta->id,
                    'factura_alegra_id' => $venta->id_factura_alegra,
                    'resultado' => $resultado
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Factura electrónica emitida correctamente a la DIAN',
                    'data' => $resultado['data'] ?? null
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => $resultado['mensaje'] ?? 'Error al emitir factura electrónica a la DIAN',
                'error' => $resultado['error'] ?? 'Error desconocido'
            ], 400);
            
        } catch (\Exception $e) {
            Log::error('Error al emitir factura electrónica a la DIAN', [
                'venta_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al emitir factura electrónica a la DIAN',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Verificar el estado de una factura electrónica en la DIAN
     * 
     * @param Request $request
     * @param int $id ID de la venta
     * @return \Illuminate\Http\JsonResponse
     */
    public function verificarEstadoFacturaElectronicaDIAN(Request $request, $id)
    {
        try {
            // Obtener la venta
            $venta = Venta::findOrFail($id);
            
            // Verificar que la venta tenga una factura en Alegra
            if (!$venta->id_factura_alegra) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta venta no tiene una factura en Alegra.'
                ], 400);
            }
            
            // Instanciar el servicio de Alegra
            $alegraService = new \App\Http\Services\AlegraService();
            
            // Verificar el estado de la factura electrónica en la DIAN
            $resultado = $alegraService->verificarEstadoFacturaElectronica($venta->id_factura_alegra);
            
            if ($resultado['success']) {
                // Actualizar el estado de la factura electrónica en la venta si es necesario
                if (isset($resultado['estado']) && $resultado['estado'] !== $venta->estado_fe) {
                    $venta->estado_fe = $resultado['estado'];
                    $venta->save();
                    
                    Log::info('Estado de factura electrónica actualizado', [
                        'venta_id' => $venta->id,
                        'factura_alegra_id' => $venta->id_factura_alegra,
                        'estado_anterior' => $venta->estado_fe,
                        'estado_nuevo' => $resultado['estado']
                    ]);
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Estado de factura electrónica obtenido correctamente',
                    'estado' => $resultado['estado'],
                    'data' => $resultado['factura'] ?? null
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => $resultado['mensaje'] ?? 'Error al verificar estado de factura electrónica',
                'error' => $resultado['error'] ?? 'Error desconocido'
            ], 400);
            
        } catch (\Exception $e) {
            Log::error('Error al verificar estado de factura electrónica', [
                'venta_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar estado de factura electrónica',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function show($id)
    {
        $venta = Venta::with(['detalles.producto', 'cliente', 'usuario'])->findOrFail($id);
        
        // Cargar el stock por ubicación para cada producto
        $stockPorProducto = [];
        foreach ($venta->detalles as $detalle) {
            $stockPorProducto[$detalle->producto_id] = StockUbicacion::stockPorUbicacion($detalle->producto_id);
        }
        
        return view('ventas.show', compact('venta', 'stockPorProducto'));
    }

    public function enviarADian(Venta $venta)
    {
        try {
            if (!$venta->id_factura_alegra) {
                return back()->with('error', 'La venta no tiene factura en Alegra');
            }

            $alegraService = app(AlegraService::class);
            // Usar el nuevo método que cambia el estado de la factura antes de enviarla a la DIAN
            $response = $alegraService->emitirFacturaElectronica($venta->id_factura_alegra);
            
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

    /**
     * Emitir factura electrónica desde la interfaz de ventas
     */
    public function emitirFacturaElectronica(Venta $venta)
    {
        try {
            // Verificar que la venta no tenga ya una factura electrónica
            if ($venta->alegra_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta venta ya tiene una factura electrónica emitida'
                ]);
            }

            // Inicializar servicio de Alegra
            $alegraService = new \App\Services\AlegraService();
            
            // Usar el método completo de procesamiento de factura electrónica
            $datosFactura = $venta->prepararFacturaAlegra();
            $resultado = $alegraService->procesarFacturaElectronica($datosFactura);
            
            if ($resultado['success']) {
                // Actualizar la venta con los datos de la factura electrónica
                $venta->update([
                    'alegra_id' => $resultado['id_factura'],
                    'numero_factura_electronica' => $resultado['numero_factura_electronica'] ?? null,
                    'cufe' => $resultado['cufe'] ?? null,
                    'estado_dian' => $resultado['estado_dian'] ?? 'procesando',
                    'qr_code' => $resultado['qr_code'] ?? null
                ]);

                Log::info('Factura electrónica emitida exitosamente', [
                    'venta_id' => $venta->id,
                    'alegra_id' => $resultado['id_factura'],
                    'cufe' => $resultado['cufe'] ?? null
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Factura electrónica emitida exitosamente',
                    'alegra_id' => $resultado['id_factura'],
                    'numero_factura_electronica' => $resultado['numero_factura_electronica'] ?? null,
                    'cufe' => $resultado['cufe'] ?? null,
                    'estado_dian' => $resultado['estado_dian'] ?? 'procesando'
                ]);
            } else {
                Log::error('Error al emitir factura electrónica', [
                    'venta_id' => $venta->id,
                    'error' => $resultado['message'],
                    'etapa' => $resultado['etapa'] ?? 'desconocida'
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $resultado['message'],
                    'etapa' => $resultado['etapa'] ?? 'desconocida'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Excepción al emitir factura electrónica', [
                'venta_id' => $venta->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Sincronizar QRs de facturas electrónicas pendientes
     */
    public function sincronizarQRs()
    {
        try {
            $alegraService = new \App\Http\Services\AlegraService();
            
            // Buscar ventas con alegra_id pero sin qr_code
            $ventasSinQR = Venta::whereNotNull('alegra_id')
                ->whereNull('qr_code')
                ->get();
            
            $actualizadas = 0;
            $errores = 0;
            
            foreach ($ventasSinQR as $venta) {
                Log::info('Sincronizando QR para venta', [
                    'venta_id' => $venta->id,
                    'alegra_id' => $venta->alegra_id
                ]);
                
                // Consultar factura en Alegra
                $resultado = $alegraService->obtenerFactura($venta->alegra_id);
                
                if (isset($resultado['success']) && $resultado['success']) {
                    $data = $resultado['data'];
                    
                    if (isset($data['stamp']['barCodeContent'])) {
                        $venta->update([
                            'cufe' => $data['stamp']['cufe'] ?? null,
                            'qr_code' => $data['stamp']['barCodeContent'],
                            'estado_dian' => $data['stamp']['legalStatus'] ?? null,
                            'url_pdf_alegra' => $data['stamp']['pdfUrl'] ?? null
                        ]);
                        
                        $actualizadas++;
                    } else {
                        $errores++;
                    }
                } else {
                    $errores++;
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "QRs sincronizados: {$actualizadas} actualizados, {$errores} pendientes",
                'actualizadas' => $actualizadas,
                'errores' => $errores
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al sincronizar QRs', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar QRs: ' . $e->getMessage()
            ]);
        }
    }
}