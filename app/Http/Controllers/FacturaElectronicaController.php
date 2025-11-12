<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Venta;
use App\Http\Services\AlegraService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Helpers\AlegraErrorHelper;

class FacturaElectronicaController extends Controller
{
    protected $alegraService;
    
    public function __construct(AlegraService $alegraService = null)
    {
        // Inicializar el servicio de Alegra sin requerir inyección de dependencias
        $this->alegraService = $alegraService ?? new AlegraService();
    }
    
    /**
     * Mostrar la lista de facturas electrónicas
     */
    public function index()
    {
        $ventas = Venta::whereNotNull('alegra_id')
            ->with('cliente')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('facturas_electronicas.index', compact('ventas'));
    }
    
    /**
     * Mostrar detalles de una factura electrónica
     */
    public function show($id)
    {
        $venta = Venta::with(['cliente', 'detalles.producto'])
            ->findOrFail($id);
            
        if (!$venta->alegra_id) {
            return redirect()->route('ventas.show', $venta->id)
                ->with('error', 'Esta venta no tiene una factura electrónica asociada');
        }
        
        // Obtener estado actual de la factura en Alegra
        $estadoAlegra = $this->alegraService->obtenerEstadoFactura($venta->alegra_id);
        
        return view('facturas_electronicas.show', compact('venta', 'estadoAlegra'));
    }
    
    /**
     * Obtener los detalles de una factura electrónica emitida en Alegra para impresión
     */
    public function obtenerDetallesParaImpresion($id)
    {
        try {
            $venta = Venta::findOrFail($id);
            
            if (!$venta->alegra_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta venta no tiene una factura electrónica asociada en Alegra'
                ]);
            }
            
            // Obtener detalles de la factura emitida en Alegra
            $detalles = $this->alegraService->obtenerDetallesFacturaEmitida($venta->alegra_id);
            
            if (!$detalles['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al obtener detalles de la factura: ' . $detalles['message']
                ]);
            }
            
            // Actualizar el estado DIAN en la venta si está disponible
            if (isset($detalles['data']['dian_status'])) {
                $venta->dian_status = $detalles['data']['dian_status'];
                $venta->save();
                
                Log::info('Estado DIAN actualizado', [
                    'venta_id' => $venta->id,
                    'dian_status' => $venta->dian_status
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Detalles de factura obtenidos correctamente',
                'data' => $detalles['data']
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener detalles para impresión: ' . $e->getMessage(), [
                'venta_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener detalles para impresión: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Mostrar la vista de impresión de una factura electrónica
     */
    public function imprimirFactura($id)
    {
        $venta = Venta::with(['cliente', 'detalles.producto'])
            ->findOrFail($id);
            
        if (!$venta->alegra_id) {
            return redirect()->route('ventas.show', $venta->id)
                ->with('error', 'Esta venta no tiene una factura electrónica asociada');
        }
        
        // Obtener detalles de la factura emitida en Alegra
        $detalles = $this->alegraService->obtenerDetallesFacturaEmitida($venta->alegra_id);
        
        if (!$detalles['success']) {
            return redirect()->route('facturas_electronicas.show', $venta->id)
                ->with('error', 'Error al obtener detalles de la factura: ' . $detalles['message']);
        }
        
        return view('facturas_electronicas.imprimir', [
            'venta' => $venta,
            'detalles' => $detalles['data']
        ]);
    }
    
    /**
     * Descargar PDF de la factura electrónica
     */
    public function descargarPDF($id)
    {
        $venta = Venta::with(['cliente', 'detalles.producto', 'usuario'])->findOrFail($id);
        
        if (!$venta->alegra_id) {
            return redirect()->back()
                ->with('error', 'Esta venta no tiene una factura electrónica asociada');
        }
        
        // Obtener datos completos de la factura desde Alegra
        $detallesAlegra = $this->alegraService->obtenerDetalleFacturaCompleto($venta->alegra_id);
        
        if (!$detallesAlegra['success']) {
            return redirect()->back()
                ->with('error', 'No se pudieron obtener los detalles de la factura de Alegra');
        }
        
        // Obtener datos de la empresa
        $empresa = \App\Models\Empresa::first();
        
        // Generar nuestro propio PDF con todos los datos
        return $this->generarPDFPropio($venta, $empresa, $detallesAlegra);
    }
    
    /**
     * Genera un PDF propio con toda la información de Alegra
     */
    private function generarPDFPropio($venta, $empresa, $detallesAlegra)
    {
        // Determinar el nombre del archivo
        $numeroFactura = $venta->getNumeroFacturaMostrar();
        $nombreArchivo = 'factura_electronica_' . $numeroFactura . '.pdf';
        
        // Generar QR code si existe la información
        $qrCodeBase64 = null;
        if (isset($detallesAlegra['data']['stamp']['barCodeContent'])) {
            $qrCodeBase64 = $this->generarQRCode($detallesAlegra['data']['stamp']['barCodeContent']);
        }
        
        // Crear vista HTML para el PDF usando la vista optimizada
        $html = view('facturas.pdf_electronica_optimizada', [
            'venta' => $venta,
            'empresa' => $empresa,
            'detallesAlegra' => $detallesAlegra,
            'numeroFactura' => $numeroFactura,
            'qrCodeBase64' => $qrCodeBase64
        ])->render();
        
        // Generar PDF usando DomPDF (si está instalado) o HTML simple
        if (class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            return $pdf->download($nombreArchivo);
        } else {
            // Fallback: devolver HTML como respuesta para impresión
            return response($html)
                ->header('Content-Type', 'text/html')
                ->header('Content-Disposition', 'inline; filename="' . $nombreArchivo . '"');
        }
    }
    
    /**
     * Genera un QR code como imagen base64
     */
    private function generarQRCode($data)
    {
        try {
            // Usar una librería simple para generar QR
            // Si no está disponible, usar API externa como fallback
            if (class_exists('SimpleSoftwareIO\QrCode\Facades\QrCode')) {
                $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                    ->size(100)
                    ->generate($data);
                return base64_encode($qrCode);
            } else {
                // Fallback: generar usando API externa y convertir a base64
                $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=' . urlencode($data);
                $qrContent = file_get_contents($qrUrl);
                if ($qrContent !== false) {
                    return base64_encode($qrContent);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error generando QR code: ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Enviar factura a la DIAN
     */
    public function enviarADian($id)
    {
        $venta = Venta::findOrFail($id);
        
        if (!$venta->alegra_id) {
            return redirect()->back()
                ->with('error', 'Esta venta no tiene una factura electrónica asociada');
        }
        
        $resultado = $this->alegraService->enviarFacturaADian($venta->alegra_id);
        
        if (!$resultado['success']) {
            // Corregimos el acceso a la información de error
            $errorMessage = isset($resultado['message']) ? $resultado['message'] : 'Error desconocido';
            
            // Si hay detalles adicionales, los incluimos
            if (isset($resultado['error_details'])) {
                $errorDetails = is_array($resultado['error_details']) 
                    ? json_encode($resultado['error_details']) 
                    : $resultado['error_details'];
                $errorMessage .= " - Detalles: " . $errorDetails;
            }
            
            return redirect()->back()
                ->with('error', 'Error al enviar a DIAN: ' . $errorMessage);
        }
        
        // Actualizar estado de la factura
        $venta->update([
            'estado_dian' => $resultado['data']['status'] ?? 'Enviado',
            'cufe' => $resultado['data']['cufe'] ?? null,
            'qr_code' => $resultado['data']['qrCode'] ?? null,
        ]);
        
        return redirect()->back()
            ->with('success', 'Factura enviada a la DIAN correctamente');
    }
    
    /**
     * Abrir y emitir factura electrónica en un solo paso
     */
    public function abrirYEmitir($id)
    {
        $venta = Venta::findOrFail($id);
        
        if (!$venta->alegra_id) {
            return redirect()->back()
                ->with('error', 'Esta venta no tiene una factura electrónica asociada');
        }
        
        // Ejecutar el script unificado para abrir y enviar a DIAN en un solo paso
        $resultado = $this->alegraService->enviarFacturaADian($venta->alegra_id);
        
        Log::info('Resultado de abrir y emitir factura electrónica', [
            'venta_id' => $venta->id,
            'alegra_id' => $venta->alegra_id,
            'resultado' => $resultado
        ]);
        
        if ($resultado['success']) {
            // Actualizar estado de la factura
            $venta->update([
                'estado_dian' => $resultado['data']['status'] ?? 'Emitida',
                'cufe' => $resultado['data']['cufe'] ?? null,
                'qr_code' => $resultado['data']['qrCode'] ?? null,
            ]);
            
            return redirect()->back()
                ->with('success', 'Factura abierta y emitida correctamente');
        }
        
        $errorMessage = AlegraErrorHelper::parseErrorResponse($resultado['error'] ?? 'Error desconocido');
            
        return redirect()->back()
            ->with('error', 'Error al abrir y emitir factura: ' . $errorMessage);
    }
    
    /**
     * Verificar estado de la factura en Alegra
     */
    public function verificarEstado($id)
    {
        $venta = Venta::findOrFail($id);
        
        Log::info('Verificando estado de factura electrónica', [
            'id' => $id,
            'alegra_id' => $venta->alegra_id
        ]);
        
        if (!$venta->alegra_id) {
            Log::warning('Intento de verificar estado de factura sin ID de Alegra', [
                'id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Esta venta no tiene una factura electrónica asociada'
            ]);
        }
        
        $resultado = $this->alegraService->obtenerEstadoFactura($venta->alegra_id);
        
        Log::info('Resultado de verificación de estado', [
            'id' => $id,
            'alegra_id' => $venta->alegra_id,
            'resultado' => $resultado
        ]);
        
        if (!$resultado['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar estado: ' . ($resultado['error'] ?? 'Error desconocido')
            ]);
        }
        
        // Actualizar estado de la factura en la base de datos
        $datosActualizacion = [
            'estado_dian' => $resultado['data']['status'] ?? 'Desconocido'
        ];
        
        // Si hay CUFE, actualizar
        if (!empty($resultado['data']['cufe'])) {
            $datosActualizacion['cufe'] = $resultado['data']['cufe'];
        }
        
        // Si hay QR Code, actualizar
        if (!empty($resultado['data']['qrCode'])) {
            $datosActualizacion['qr_code'] = $resultado['data']['qrCode'];
        }
        
        // Si hay estado legal de DIAN, usar ese como estado principal
        if (!empty($resultado['data']['legalStatus'])) {
            $datosActualizacion['estado_dian'] = $resultado['data']['legalStatus'];
        }
        
        $venta->update($datosActualizacion);
        
        return response()->json([
            'success' => true,
            'data' => $resultado['data'],
            'message' => 'Estado actualizado correctamente'
        ]);
    }
    
    /**
     * Cambiar estado de la factura (abrir borrador)
     */
    public function abrirFactura($id)
    {
        $venta = Venta::findOrFail($id);
        
        Log::info('Iniciando apertura de factura electrónica', [
            'id' => $id,
            'alegra_id' => $venta->alegra_id
        ]);
        
        if (!$venta->alegra_id) {
            Log::warning('Intento de abrir factura sin ID de Alegra', [
                'id' => $id
            ]);
            
            return redirect()->back()
                ->with('error', 'Esta venta no tiene una factura electrónica asociada');
        }
        
        // Intentar abrir la factura directamente primero
        $resultado = $this->alegraService->abrirFacturaDirecto($venta->alegra_id);
        
        Log::info('Resultado de abrir factura electrónica', [
            'venta_id' => $venta->id,
            'alegra_id' => $venta->alegra_id,
            'resultado' => $resultado
        ]);
        
        if (!$resultado['success']) {
            $errorMessage = AlegraErrorHelper::parseErrorResponse($resultado['error'] ?? 'Error desconocido');
            Log::error('Error al abrir factura', [
                'id' => $id,
                'alegra_id' => $venta->alegra_id,
                'error' => $errorMessage,
                'resultado' => $resultado
            ]);
            
            return redirect()->back()
                ->with('error', 'Error al abrir la factura: ' . $errorMessage);
        }
        
        // Actualizar estado de la factura con la información recibida de Alegra
        $venta->update([
            'estado_dian' => $resultado['data']['status'] ?? 'open'
        ]);
        
        Log::info('Factura abierta correctamente', [
            'id' => $id,
            'alegra_id' => $venta->alegra_id,
            'nuevo_estado' => $resultado['data']['status'] ?? 'open'
        ]);
        
        return redirect()->back()
            ->with('success', 'Factura abierta correctamente. Estado actual: ' . ($resultado['data']['status'] ?? 'open'));
    }
    
    /**
     * Imprimir factura electrónica en formato tirilla
     */
    public function imprimirTirilla($id)
    {
        $venta = Venta::with(['cliente', 'detalles.producto', 'usuario'])->findOrFail($id);
        
        if (!$venta->alegra_id) {
            return redirect()->back()
                ->with('error', 'Esta venta no tiene una factura electrónica asociada');
        }
        
        // Verificar que la factura esté abierta (no en draft)
        if ($venta->estado_dian === 'draft' || !$venta->estado_dian) {
            return redirect()->back()
                ->with('error', 'La factura debe estar abierta en Alegra antes de imprimir. Use "Abrir" primero.');
        }
        
        // Obtener datos completos de la factura desde Alegra
        $detallesAlegra = $this->alegraService->obtenerDetalleFacturaCompleto($venta->alegra_id);
        
        if (!$detallesAlegra['success']) {
            return redirect()->back()
                ->with('error', 'No se pudieron obtener los detalles de la factura de Alegra');
        }
        
        // Debug: Log de los datos para verificar estructura
        Log::info('Datos para tirilla', [
            'venta_id' => $venta->id,
            'venta_cufe' => $venta->cufe ? substr($venta->cufe, 0, 20) . '...' : 'No presente',
            'venta_qr' => $venta->qr_code ? 'Presente (' . strlen($venta->qr_code) . ' chars)' : 'No presente',
            'venta_estado' => $venta->estado_dian,
            'alegra_structure' => array_keys($detallesAlegra['data'] ?? []),
            'alegra_stamp' => isset($detallesAlegra['data']['stamp']) ? 'Presente' : 'No presente',
            'alegra_cufe' => isset($detallesAlegra['data']['stamp']['cufe']) ? substr($detallesAlegra['data']['stamp']['cufe'], 0, 20) . '...' : 'No encontrado',
            'alegra_qr' => isset($detallesAlegra['data']['stamp']['barCodeContent']) ? 'Presente (' . strlen($detallesAlegra['data']['stamp']['barCodeContent']) . ' chars)' : 'No presente'
        ]);
        
        // Obtener datos de la empresa
        $empresa = \App\Models\Empresa::first();
        
        // Generar PDF en formato tirilla
        return $this->generarPDFTirilla($venta, $empresa, $detallesAlegra);
    }
    
    /**
     * Genera un PDF en formato tirilla para facturas electrónicas
     */
    private function generarPDFTirilla($venta, $empresa, $detallesAlegra)
    {
        // Procesar QR Code y guardarlo como archivo temporal para DomPDF
        $qrImagePath = null;
        
        if ($venta->qr_code) {
            // Decodificar base64 y guardar temporalmente
            $qrImagePath = storage_path('app/temp/qr_' . $venta->id . '.png');
            
            // Crear directorio temp si no existe
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }
            
            // Guardar imagen decodificada
            file_put_contents($qrImagePath, base64_decode($venta->qr_code));
            
            Log::info('QR guardado temporalmente', [
                'path' => $qrImagePath,
                'exists' => file_exists($qrImagePath),
                'size' => file_exists($qrImagePath) ? filesize($qrImagePath) : 0
            ]);
        }
        
        $pdf = \PDF::loadView('facturas_electronicas.pdf_tirilla', [
            'venta' => $venta,
            'empresa' => $empresa,
            'detallesAlegra' => $detallesAlegra['data'],
            'cliente' => $venta->cliente,
            'detalles' => $venta->detalles,
            'usuario' => $venta->usuario,
            'qrImagePath' => $qrImagePath, // Pasar ruta del archivo temporal
        ]);
        
        // Configurar para formato tirilla (80mm de ancho)
        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait'); // 80mm x 297mm
        
        $numeroFactura = $detallesAlegra['data']['numberTemplate']['fullNumber'] ?? $venta->numero_factura;
        
        // Generar PDF
        $pdfOutput = $pdf->download("Factura_Tirilla_{$numeroFactura}.pdf");
        
        // Limpiar archivo temporal
        if ($qrImagePath && file_exists($qrImagePath)) {
            unlink($qrImagePath);
        }
        
        return $pdfOutput;
    }
    
    /**
     * Mostrar formulario de emisión directa
     */
    public function mostrarEmisionDirecta($id)
    {
        $venta = Venta::with(['cliente', 'detalles.producto', 'usuario'])->findOrFail($id);
        
        // Verificar que no sea una factura electrónica ya emitida
        if ($venta->alegra_id) {
            return redirect()->back()
                ->with('warning', 'Esta venta ya tiene una factura electrónica asociada');
        }
        
        return view('facturas_electronicas.emitir_directa', compact('venta'));
    }
    
    /**
     * Procesar emisión directa de factura electrónica
     */
    public function procesarEmisionDirecta(Request $request, $id)
    {
        $venta = Venta::with(['cliente', 'detalles.producto', 'usuario'])->findOrFail($id);
        
        try {
            DB::beginTransaction();
            
            // Validar datos del formulario
            $request->validate([
                'productos' => 'required|array|min:1',
                'productos.*.cantidad' => 'required|numeric|min:0.01',
                'productos.*.precio' => 'required|numeric|min:0',
                'productos.*.descuento' => 'nullable|numeric|min:0|max:100',
                'productos.*.iva' => 'nullable|numeric|min:0|max:100',
            ]);
            
            // Actualizar la venta con los datos del formulario
            $this->actualizarVentaDesdeFormulario($venta, $request->input('productos'));
            
            // Preparar datos para Alegra usando la misma lógica que el PDF
            $datosFactura = $this->prepararDatosParaAlegra($venta, $request);
            
            // Crear factura en Alegra
            $resultado = $this->alegraService->crearFactura($datosFactura);
            
            if (!$resultado['success']) {
                throw new \Exception('Error al crear factura en Alegra: ' . ($resultado['message'] ?? 'Error desconocido'));
            }
            
            // Actualizar venta con datos de Alegra
            $venta->update([
                'alegra_id' => $resultado['data']['id'],
                'numero_factura_alegra' => $resultado['data']['numberTemplate']['fullNumber'] ?? null,
                'estado_dian' => $resultado['data']['status'] ?? 'draft',
                'url_pdf_alegra' => $resultado['data']['pdf']['url'] ?? null,
            ]);
            
            // Si se solicitó envío automático a DIAN
            if ($request->has('enviar_dian') && $request->enviar_dian) {
                $resultadoDian = $this->alegraService->enviarFacturaADian($venta->alegra_id);
                if ($resultadoDian['success']) {
                    $venta->update(['estado_dian' => 'sent']);
                }
            }
            
            DB::commit();
            
            return redirect()->route('ventas.index')
                ->with('success', 'Factura electrónica emitida exitosamente: ' . ($venta->numero_factura_alegra ?? $venta->numero_factura));
                
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Error en emisión directa de factura', [
                'venta_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al emitir factura: ' . $e->getMessage());
        }
    }
    
    /**
     * Actualizar venta con datos del formulario
     */
    private function actualizarVentaDesdeFormulario($venta, $productos)
    {
        $subtotal = 0;
        $totalDescuentos = 0;
        $totalIva = 0;
        
        // Actualizar detalles de la venta
        foreach ($productos as $index => $productoData) {
            $detalle = $venta->detalles[$index] ?? null;
            if ($detalle) {
                $cantidad = floatval($productoData['cantidad']);
                $precio = floatval($productoData['precio']);
                $descuento = floatval($productoData['descuento'] ?? 0);
                $iva = floatval($productoData['iva'] ?? 0);
                
                $subtotalLinea = $cantidad * $precio;
                $descuentoLinea = $subtotalLinea * ($descuento / 100);
                $baseImponible = $subtotalLinea - $descuentoLinea;
                $ivaLinea = $baseImponible * ($iva / 100);
                
                $detalle->update([
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'descuento' => $descuento,
                    'iva_porcentaje' => $iva,
                    'subtotal' => $subtotalLinea,
                    'total' => $baseImponible + $ivaLinea
                ]);
                
                $subtotal += $subtotalLinea;
                $totalDescuentos += $descuentoLinea;
                $totalIva += $ivaLinea;
            }
        }
        
        // Actualizar totales de la venta
        $venta->update([
            'subtotal' => $subtotal,
            'descuento' => $totalDescuentos,
            'iva' => $totalIva,
            'total' => $subtotal - $totalDescuentos + $totalIva
        ]);
    }
    
    /**
     * Preparar datos para enviar a Alegra
     */
    private function prepararDatosParaAlegra($venta, $request)
    {
        $empresa = \App\Models\Empresa::first();
        
        // Preparar items
        $items = [];
        foreach ($venta->detalles as $detalle) {
            $items[] = [
                'name' => $detalle->producto->nombre,
                'description' => $detalle->producto->descripcion ?? '',
                'price' => $detalle->precio,
                'quantity' => $detalle->cantidad,
                'discount' => $detalle->descuento ?? 0,
                'tax' => [
                    [
                        'name' => 'IVA',
                        'percentage' => $detalle->iva_porcentaje ?? 0
                    ]
                ]
            ];
        }
        
        // Preparar datos del cliente
        $clienteData = [
            'name' => $venta->cliente->nombres,
            'identification' => $venta->cliente->numero_documento,
            'email' => $venta->cliente->email,
            'phonePrimary' => $venta->cliente->telefono,
            'address' => [
                'address' => $venta->cliente->direccion ?? '',
                'city' => $venta->cliente->ciudad ?? 'Bogotá'
            ]
        ];
        
        return [
            'date' => $venta->fecha_venta->format('Y-m-d'),
            'dueDate' => $venta->fecha_venta->format('Y-m-d'),
            'client' => $clienteData,
            'items' => $items,
            'observations' => $request->input('observaciones', ''),
            'sendEmail' => $request->has('enviar_email') && $request->enviar_email,
            
            // Información de pago requerida por Alegra
            'paymentForm' => 'CASH', // Forma de pago: efectivo
            'paymentMethod' => 'CASH', // Método de pago: efectivo
            'term' => 'De contado', // Término de pago
            'payment' => [
                'paymentMethod' => [
                    'id' => 1 // Cambiar a ID 1 que es más común
                ],
                'account' => [
                    'id' => 1 // ID de la cuenta principal
                ]
            ],
            
            // Configuración para factura electrónica
            'useElectronicInvoice' => true,
            
            // CLAVE: Crear directamente como OPEN para evitar problemas de apertura
            'status' => 'open'
        ];
    }
}
