<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\GeneraComprobanteContable;
use App\Models\Traits\GeneraComprobanteVentaCredito;
use App\Services\AlegraService;
use Illuminate\Support\Facades\Log;

class Venta extends Model
{
    use GeneraComprobanteContable;
    use GeneraComprobanteVentaCredito;

    protected $fillable = [
        'numero_factura', 'fecha_venta', 
        'subtotal', 'iva', 'total',
        'cliente_id', 'user_id',
        'pago',
        'devuelta',
        'metodo_pago',
        'alegra_id',
        'numero_factura_alegra',
        'url_pdf_alegra',
        'estado_dian',
        'cufe',
        'qr_code',
        'caja_id',
        'numero_factura_electronica'
    ];

    protected $casts = [
        'fecha_venta' => 'datetime'
    ];

    /**
     * Boot del modelo - Configurar eventos
     */
    protected static function boot()
    {
        parent::boot();
        
        // Cuando se elimina una venta, eliminar sus detalles automáticamente
        static::deleting(function ($venta) {
            Log::info("Eliminando venta {$venta->id} y sus detalles");
            $venta->detalles()->delete();
        });
    }

    /**
     * Obtiene el número de factura a mostrar según el tipo
     * Para facturas electrónicas, prioriza el número de Alegra
     */
    public function getNumeroFacturaMostrar()
    {
        // Si es factura electrónica y tiene número de Alegra, usar ese
        if ($this->alegra_id && $this->numero_factura_alegra) {
            return $this->numero_factura_alegra;
        }
        
        // Si no, usar el número local
        return $this->numero_factura;
    }

    /**
     * Determina si es una factura electrónica
     */
    public function esFacturaElectronica()
    {
        return !empty($this->alegra_id);
    }

    /**
     * Obtiene la URL del PDF de Alegra si existe
     */
    public function getUrlPdfAlegra()
    {
        return $this->url_pdf_alegra;
    }

    /**
     * Prepara los datos para crear una factura en Alegra
     * @return array
     */
    public function prepararFacturaAlegra()
    {
        // Asegurar que el cliente tenga ID de Alegra
        if (!$this->cliente->id_alegra) {
            $this->cliente->syncToAlegra();
        }

        // Obtener la empresa
        $empresa = app(\App\Models\Empresa::class)->first();
        $esResponsableIVA = $empresa->regimen_tributario === 'responsable_iva';
        $porcentajeIVA = floatval($empresa->porcentaje_iva ?? 19);
        
        // Log para depuración
        \Illuminate\Support\Facades\Log::info('Preparando factura Alegra', [
            'empresa_id' => $empresa->id,
            'regimen_tributario' => $empresa->regimen_tributario,
            'es_responsable_iva' => $esResponsableIVA,
            'porcentaje_iva' => $porcentajeIVA
        ]);

        // Usar el servicio de equivalencias para preparar items
        $equivalenciasService = app(\App\Services\AlegraEquivalenciasService::class);
        $items = [];
        $totalImpuestos = [];
        
        // Generar reporte de conversiones para auditoría
        $reporteConversiones = $equivalenciasService->generarReporteConversiones($this);
        \Illuminate\Support\Facades\Log::info('📊 Reporte de conversiones Alegra', $reporteConversiones);
        
        foreach ($this->detalles as $detalle) {
            // Asegurar que el producto (o su base) tenga ID de Alegra
            $productoParaAlegra = $this->obtenerProductoParaAlegra($detalle);
            if (!$productoParaAlegra->id_alegra) {
                $productoParaAlegra->syncToAlegra();
            }
            
            // Usar el servicio para convertir el detalle
            $itemData = $equivalenciasService->convertirDetalleParaAlegra($detalle);
            
            // Si es responsable de IVA, agregar la información de impuestos
            if ($esResponsableIVA) {
                // 1. Usar taxRate (el enfoque más simple y directo)
                $itemData['taxRate'] = $porcentajeIVA;
                
                // 2. Usar el campo tax con el formato específico requerido por Alegra
                $itemData['tax'] = [
                    'id' => 1, // ID 1 corresponde al IVA en Alegra
                    'name' => 'IVA',
                    'percentage' => $porcentajeIVA,
                    'value' => round(($detalle->precio_unitario * $detalle->cantidad) * ($porcentajeIVA / 100), 2)
                ];
                
                // 3. Usar el campo taxes (array de impuestos)
                $itemData['taxes'] = [
                    [
                        'id' => 1,
                        'name' => 'IVA',
                        'percentage' => $porcentajeIVA,
                        'value' => round(($detalle->precio_unitario * $detalle->cantidad) * ($porcentajeIVA / 100), 2)
                    ]
                ];
                
                // 4. Acumular impuestos para el totalTaxes a nivel de factura
                $impuestoItem = round(($detalle->precio_unitario * $detalle->cantidad) * ($porcentajeIVA / 100), 2);
                if (!isset($totalImpuestos[1])) {
                    $totalImpuestos[1] = [
                        'id' => 1,
                        'name' => 'IVA',
                        'percentage' => $porcentajeIVA,
                        'amount' => $impuestoItem
                    ];
                } else {
                    $totalImpuestos[1]['amount'] += $impuestoItem;
                }
            }
            
            $items[] = $itemData;
        }

        // Obtener el método de pago formateado para Alegra
        $alegraService = app(AlegraService::class);
        $payment = $alegraService->mapearFormaPago($this->metodo_pago);

        // Preparar datos completos de la factura
        $datos = [
            'date' => $this->fecha_venta->format('Y-m-d'),
            'dueDate' => $this->fecha_venta->format('Y-m-d'),
            'client' => [
                'id' => intval($this->cliente->id_alegra)
            ],
            'items' => $items,
            'payment' => $payment,
            'useElectronicInvoice' => true,
            
            // CLAVE: Crear directamente como OPEN para evitar problemas de apertura
            'status' => 'open'
        ];
        
        // Si es responsable de IVA, agregar el campo totalTaxes a nivel de factura
        if ($esResponsableIVA && !empty($totalImpuestos)) {
            $datos['totalTaxes'] = array_values($totalImpuestos);
        }
        
        // Log final para depuración
        \Illuminate\Support\Facades\Log::info('Datos preparados para Alegra', [
            'datos_completos' => json_encode($datos, JSON_PRETTY_PRINT)
        ]);

        return $datos;
    }

    /**
     * Obtiene el producto que se debe usar para Alegra (base o equivalente)
     * 
     * @param DetalleVenta $detalle
     * @return Producto
     */
    private function obtenerProductoParaAlegra($detalle)
    {
        $producto = $detalle->producto;
        
        // Si es un producto equivalente, usar el producto base para Alegra
        if ($producto->es_producto_base === false && !is_null($producto->producto_base_id)) {
            return $producto->productoBase;
        }
        
        return $producto;
    }

    /**
     * Crea una factura electrónica en Alegra
     * @return array
     */
    public function crearFacturaElectronica()
    {
        try {
            $datos = $this->prepararFacturaAlegra();
            $alegraService = app(AlegraService::class);
            $resultado = $alegraService->crearFactura($datos);

            if ($resultado['success']) {
                $facturaId = $resultado['data']['id'];
                
                // Guardar ID de Alegra en la venta
                $this->update([
                    'alegra_id' => $facturaId,
                    'estado_dian' => $resultado['data']['status'] ?? 'Pendiente'
                ]);

                Log::info('Factura electrónica creada exitosamente', [
                    'venta_id' => $this->id,
                    'alegra_id' => $this->alegra_id
                ]);
                
                // NUEVO: Enviar automáticamente a DIAN
                Log::info('Enviando automáticamente a DIAN', [
                    'venta_id' => $this->id,
                    'alegra_id' => $facturaId
                ]);
                
                $resultadoDian = $alegraService->enviarFacturaADian($facturaId);
                
                if ($resultadoDian['success']) {
                    Log::info('Factura enviada a DIAN automáticamente', [
                        'venta_id' => $this->id,
                        'alegra_id' => $facturaId,
                        'resultado_dian' => $resultadoDian['data']
                    ]);
                    
                    // Actualizar estado DIAN
                    $this->update([
                        'estado_dian' => 'Enviada'
                    ]);
                    
                    // NUEVO: Enviar automáticamente al cliente por email
                    if (!empty($this->cliente->email)) {
                        Log::info('Enviando factura al cliente por email', [
                            'venta_id' => $this->id,
                            'alegra_id' => $facturaId,
                            'cliente_email' => $this->cliente->email
                        ]);
                        
                        // Esperar un momento para que el PDF esté disponible
                        sleep(2);
                        
                        $resultadoEmail = $alegraService->enviarFacturaPorEmail(
                            $facturaId, 
                            $this->cliente->email,
                            "Estimado/a {$this->cliente->nombre},\n\nAdjunto encontrará su factura electrónica.\n\nGracias por su compra."
                        );
                        
                        if ($resultadoEmail['success']) {
                            Log::info('Factura enviada al cliente exitosamente', [
                                'venta_id' => $this->id,
                                'alegra_id' => $facturaId,
                                'cliente_email' => $this->cliente->email
                            ]);
                        } else {
                            Log::warning('Error enviando factura al cliente', [
                                'venta_id' => $this->id,
                                'alegra_id' => $facturaId,
                                'cliente_email' => $this->cliente->email,
                                'error' => $resultadoEmail['error']
                            ]);
                        }
                    } else {
                        Log::info('Cliente sin email, no se envía factura automáticamente', [
                            'venta_id' => $this->id,
                            'cliente_id' => $this->cliente->id
                        ]);
                    }
                } else {
                    Log::warning('Error al enviar automáticamente a DIAN', [
                        'venta_id' => $this->id,
                        'alegra_id' => $facturaId,
                        'error' => $resultadoDian['error']
                    ]);
                }
            } else {
                Log::error('Error al crear factura electrónica', [
                    'venta_id' => $this->id,
                    'error' => $resultado['error']
                ]);
            }

            return $resultado;
        } catch (\Exception $e) {
            Log::error('Excepción al crear factura electrónica', [
                'venta_id' => $this->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function credito()
    {
        return $this->hasOne(Credito::class);
    }

    public function caja()
    {
        return $this->belongsTo(CajaDiaria::class, 'caja_id');
    }

    protected static function booted()
    {
        static::created(function($venta) {
            Log::info('Venta creada - Verificando método de pago', [
                'venta_id' => $venta->id,
                'metodo_pago' => $venta->metodo_pago,
                'traits_loaded' => class_uses_recursive($venta),
                'tiene_metodo' => method_exists($venta, 'generarComprobanteVentaCredito')
            ]);

            // Evitar que se genere comprobante normal para ventas a crédito
            if ($venta->metodo_pago === 'credito') {
                Log::info('Venta a crédito detectada - No generando comprobante normal');
                return;
            }

            Log::info('Generando comprobante de venta normal');
            $venta->generarComprobanteVenta();
        });
    }
}
