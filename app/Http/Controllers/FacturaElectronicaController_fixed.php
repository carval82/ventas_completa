<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Services\AlegraService;
use App\Helpers\AlegraErrorHelper;
use Illuminate\Http\Request;

class FacturaElectronicaController extends Controller
{
    protected $alegraService;

    public function __construct(AlegraService $alegraService)
    {
        $this->alegraService = $alegraService;
    }

    /**
     * Vista principal de facturas electrónicas
     */
    public function index()
    {
        $ventas = Venta::with('cliente')->orderBy('created_at', 'desc')->paginate(10);
        return view('facturas_electronicas.index', compact('ventas'));
    }

    /**
     * Crear factura electrónica
     */
    public function create($id)
    {
        $venta = Venta::with('cliente', 'detalles.producto')->findOrFail($id);
        
        if ($venta->alegra_id) {
            return redirect()->back()
                ->with('error', 'Esta venta ya tiene una factura electrónica asociada');
        }
        
        // Verificar si la venta ya está pagada
        if ($venta->saldo > 0) {
            return redirect()->back()
                ->with('error', 'La venta debe estar completamente pagada antes de generar una factura electrónica');
        }
        
        return view('facturas_electronicas.create', compact('venta'));
    }

    /**
     * Almacenar factura electrónica
     */
    public function store(Request $request, $id)
    {
        $venta = Venta::findOrFail($id);
        
        if ($venta->alegra_id) {
            return redirect()->back()
                ->with('error', 'Esta venta ya tiene una factura electrónica asociada');
        }
        
        $resultado = $this->alegraService->crearFacturaElectronica($venta);
        
        if (!$resultado['success']) {
            $errorMessage = AlegraErrorHelper::parseErrorResponse($resultado['error']);
            return redirect()->back()
                ->with('error', 'Error al crear factura electrónica: ' . $errorMessage);
        }
        
        // Actualizar venta con ID de Alegra
        $venta->update([
            'alegra_id' => $resultado['data']['id'],
            'estado_dian' => 'Pendiente'
        ]);
        
        return redirect()->route('facturas.electronicas.index')
            ->with('success', 'Factura electrónica creada correctamente');
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
     * Ver detalles de la factura
     */
    public function show($id)
    {
        $venta = Venta::with('cliente', 'detalles.producto')->findOrFail($id);
        
        if (!$venta->alegra_id) {
            return redirect()->back()
                ->with('error', 'Esta venta no tiene una factura electrónica asociada');
        }
        
        // Obtener detalles de la factura desde Alegra
        $facturaAlegra = $this->alegraService->obtenerFactura($venta->alegra_id);
        
        if (!$facturaAlegra['success']) {
            return redirect()->back()
                ->with('error', 'Error al obtener detalles de la factura desde Alegra');
        }
        
        return view('facturas_electronicas.show', compact('venta', 'facturaAlegra'));
    }
}
