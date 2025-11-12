<?php

namespace App\Http\Controllers;

use App\Services\DianService;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DianController extends Controller
{
    protected $dianService;

    public function __construct(DianService $dianService)
    {
        $this->dianService = $dianService;
    }

    /**
     * Enviar factura a DIAN
     */
    public function enviarFactura(Request $request, $ventaId)
    {
        try {
            $venta = Venta::with(['detalles.producto', 'cliente'])->findOrFail($ventaId);
            
            // Preparar datos para DIAN
            $facturaData = [
                'id' => $venta->id,
                'numero' => $venta->numero_factura,
                'fecha' => $venta->fecha_venta,
                'cliente' => $venta->cliente,
                'detalles' => $venta->detalles,
                'total' => $venta->total,
                'subtotal' => $venta->subtotal,
                'iva' => $venta->iva,
                'descuento' => $venta->descuento ?? 0
            ];

            $resultado = $this->dianService->enviarFacturaElectronica($facturaData);

            if ($resultado['success']) {
                // Actualizar venta con datos de DIAN
                $venta->update([
                    'cufe' => $resultado['cufe'] ?? null,
                    'estado_dian' => 'enviado',
                    'fecha_envio_dian' => now()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Factura enviada exitosamente a DIAN',
                    'cufe' => $resultado['cufe']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al enviar factura a DIAN',
                    'error' => $resultado['error']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error en DianController::enviarFactura', [
                'error' => $e->getMessage(),
                'venta_id' => $ventaId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consultar estado de factura en DIAN
     */
    public function consultarEstado($ventaId)
    {
        try {
            $venta = Venta::findOrFail($ventaId);
            
            if (!$venta->cufe) {
                return response()->json([
                    'success' => false,
                    'message' => 'La factura no ha sido enviada a DIAN'
                ], 400);
            }

            // Aquí se implementaría la consulta a DIAN
            return response()->json([
                'success' => true,
                'estado' => $venta->estado_dian,
                'cufe' => $venta->cufe,
                'fecha_envio' => $venta->fecha_envio_dian
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reenviar factura a DIAN
     */
    public function reenviarFactura($ventaId)
    {
        return $this->enviarFactura(request(), $ventaId);
    }
}
