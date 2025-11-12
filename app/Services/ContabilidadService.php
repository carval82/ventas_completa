<?php

namespace App\Services;

use App\Models\Comprobante;
use App\Models\MovimientoContable;
use App\Models\ConfiguracionContable;
use App\Models\Venta;
use App\Models\Compra;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Services\PlantillaComprobanteService;

class ContabilidadService
{
    /**
     * Genera un comprobante contable
     *
     * @param string $tipo Tipo de comprobante (Ingreso, Egreso, Diario)
     * @param array $datos Datos del comprobante
     * @param array $movimientos Movimientos contables
     * @return Comprobante
     */
    public function generarComprobante($tipo, $datos, $movimientos)
    {
        try {
            Log::info('Iniciando generación de comprobante', [
                'tipo' => $tipo,
                'datos' => $datos
            ]);

            $comprobante = null;

            DB::transaction(function () use ($tipo, $datos, $movimientos, &$comprobante) {
                // Generar número de comprobante
                $prefijo = $datos['prefijo'] ?? $this->obtenerPrefijoPorTipo($tipo);
                $numero = $this->generarNumeroComprobante($prefijo);

                // Calcular totales
                $totalDebito = collect($movimientos)->sum('debito');
                $totalCredito = collect($movimientos)->sum('credito');

                // Verificar que esté cuadrado
                if (abs($totalDebito - $totalCredito) > 0.01) {
                    throw new \Exception('El comprobante no está cuadrado. La diferencia es de ' . 
                        abs($totalDebito - $totalCredito));
                }

                // Crear comprobante
                $comprobante = Comprobante::create([
                    'fecha' => $datos['fecha'] ?? Carbon::now(),
                    'tipo' => $tipo,
                    'prefijo' => $prefijo,
                    'numero' => $numero,
                    'descripcion' => $datos['descripcion'] ?? "Comprobante {$prefijo}-{$numero}",
                    'estado' => $datos['estado'] ?? 'Aprobado',
                    'created_by' => Auth::id(),
                    'total_debito' => $totalDebito,
                    'total_credito' => $totalCredito,
                    'approved_by' => $datos['estado'] === 'Aprobado' ? Auth::id() : null
                ]);

                // Crear movimientos
                foreach ($movimientos as $movimiento) {
                    MovimientoContable::create([
                        'comprobante_id' => $comprobante->id,
                        'cuenta_id' => $movimiento['cuenta_id'],
                        'fecha' => $datos['fecha'] ?? Carbon::now(),
                        'descripcion' => $movimiento['descripcion'] ?? $comprobante->descripcion,
                        'debito' => $movimiento['debito'] ?? 0,
                        'credito' => $movimiento['credito'] ?? 0,
                        'referencia' => $movimiento['referencia'] ?? null,
                        'referencia_tipo' => $movimiento['referencia_tipo'] ?? null
                    ]);
                }

                Log::info('Comprobante generado exitosamente', [
                    'comprobante_id' => $comprobante->id,
                    'prefijo' => $prefijo,
                    'numero' => $numero
                ]);
            });

            return $comprobante;
        } catch (\Exception $e) {
            Log::error('Error al generar comprobante', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Genera un comprobante de venta
     *
     * @param Venta $venta
     * @return Comprobante
     */
    public function generarComprobanteVenta(Venta $venta)
    {
        try {
            Log::info('Iniciando generación de comprobante de venta', [
                'venta_id' => $venta->id,
                'total' => $venta->total
            ]);

            // Obtener empresa para verificar régimen tributario
            $empresa = \App\Models\Empresa::first();
            $esResponsableIva = $empresa ? $empresa->esResponsableIva() : false;
            
            // Calcular subtotal e IVA basado en detalles y régimen tributario
            $subtotalSinIva = 0;
            $totalIva = 0;
            
            // Iterar sobre los detalles para obtener valores precisos
            foreach ($venta->detalles as $detalle) {
                // Si el detalle tiene los nuevos campos de IVA
                if (isset($detalle->tiene_iva) && $detalle->tiene_iva !== null) {
                    $subtotalSinIva += $detalle->subtotal ?? 0;
                    $totalIva += $detalle->valor_iva ?? 0;
                } else {
                    // Compatibilidad con registros antiguos
                    // Para servicios sin IVA, el subtotal es igual al total del detalle
                    $subtotalSinIva += $detalle->subtotal ?? ($detalle->cantidad * $detalle->precio);
                }
            }
            
            // Si no hay detalles o el subtotal es 0, usar los valores de la venta
            if ($subtotalSinIva == 0) {
                if ($esResponsableIva) {
                    // Para responsables de IVA: subtotal + IVA = total
                    $subtotalSinIva = $venta->subtotal ?? ($venta->total - $venta->iva);
                    $totalIva = $venta->iva ?? 0;
                } else {
                    // Para NO responsables de IVA: subtotal = total, IVA = 0
                    $subtotalSinIva = $venta->total ?? $venta->subtotal;
                    $totalIva = 0;
                }
            }
            
            Log::info('Régimen tributario aplicado', [
                'es_responsable_iva' => $esResponsableIva,
                'regimen_tributario' => $empresa->regimen_tributario ?? 'no_definido'
            ]);
            
            Log::info('Valores calculados para comprobante de venta', [
                'subtotal_sin_iva' => $subtotalSinIva,
                'total_iva' => $totalIva,
                'total' => $venta->total
            ]);

            // Preparar datos del comprobante
            $datos = [
                'fecha' => $venta->fecha_venta,
                'descripcion' => "Venta No. {$venta->numero_factura}",
                'prefijo' => 'V',
                'estado' => 'Aprobado'
            ];
            
            // Usar el servicio de plantillas para obtener los movimientos
            $plantillaService = new PlantillaComprobanteService();
            $datosPlantilla = [
                'subtotal' => $subtotalSinIva,
                'iva' => $totalIva,
                'total' => $venta->total,
                'referencia_id' => $venta->id,
                'numero_documento' => $venta->numero_factura,
                'metodo_pago' => $venta->metodo_pago
            ];
            
            try {
                $movimientos = $plantillaService->obtenerPlantillaVenta($datosPlantilla);
                
                Log::info('Plantilla de venta aplicada correctamente', [
                    'venta_id' => $venta->id,
                    'movimientos' => count($movimientos)
                ]);
            } catch (\Exception $e) {
                Log::warning('Error al aplicar plantilla de venta, usando método alternativo', [
                    'error' => $e->getMessage()
                ]);
                
                // Método alternativo si falla la plantilla
                $cuenta_caja = $this->obtenerCuentaPorConcepto('caja');
                $cuenta_ventas = $this->obtenerCuentaPorConcepto('ventas');
                $cuenta_iva_ventas = $this->obtenerCuentaPorConcepto('iva_ventas');

                if (!$cuenta_caja || !$cuenta_ventas) {
                    throw new \Exception('No se encontraron las cuentas contables necesarias para generar el comprobante de venta');
                }

                // Preparar movimientos manualmente
                $movimientos = [
                    [
                        'cuenta_id' => $cuenta_caja->id,
                        'descripcion' => "Venta No. {$venta->numero_factura}",
                        'debito' => $venta->total,
                        'credito' => 0,
                        'referencia' => $venta->id,
                        'referencia_tipo' => 'App\\Models\\Venta'
                    ],
                    [
                        'cuenta_id' => $cuenta_ventas->id,
                        'descripcion' => "Venta No. {$venta->numero_factura}",
                        'debito' => 0,
                        'credito' => $subtotalSinIva,
                        'referencia' => $venta->id,
                        'referencia_tipo' => 'App\\Models\\Venta'
                    ]
                ];

                // Añadir movimiento de IVA si hay productos con IVA
                if ($totalIva > 0 && $cuenta_iva_ventas) {
                    $movimientos[] = [
                        'cuenta_id' => $cuenta_iva_ventas->id,
                        'descripcion' => "IVA Venta No. {$venta->numero_factura}",
                        'debito' => 0,
                        'credito' => $totalIva,
                        'referencia' => $venta->id,
                        'referencia_tipo' => 'App\\Models\\Venta'
                    ];
                }
            }

            // Generar comprobante
            $comprobante = $this->generarComprobante('Ingreso', $datos, $movimientos);
            
            // Actualizar la venta con el ID del comprobante (si la columna existe)
            try {
                // Verificar si la columna existe antes de intentar actualizar
                if (Schema::hasColumn('ventas', 'comprobante_id')) {
                    $venta->update(['comprobante_id' => $comprobante->id]);
                } else {
                    Log::warning('Columna comprobante_id no existe en tabla ventas', [
                        'venta_id' => $venta->id,
                        'comprobante_id' => $comprobante->id
                    ]);
                }
            } catch (\Exception $e) {
                // Si hay cualquier error, solo logueamos pero no fallar
                Log::warning('No se pudo actualizar comprobante_id en venta', [
                    'venta_id' => $venta->id,
                    'comprobante_id' => $comprobante->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            return $comprobante;
        } catch (\Exception $e) {
            Log::error('Error al generar comprobante de venta', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Genera un comprobante de compra
     *
     * @param Compra $compra
     * @return Comprobante
     */
    public function generarComprobanteCompra(Compra $compra)
    {
        try {
            Log::info('Iniciando generación de comprobante de compra', [
                'compra_id' => $compra->id,
                'total' => $compra->total
            ]);

            // Calcular subtotal e IVA basado en detalles
            $subtotalSinIva = 0;
            $totalIva = 0;
            
            // Iterar sobre los detalles para obtener valores precisos
            foreach ($compra->detalles as $detalle) {
                // Si el detalle tiene los nuevos campos de IVA
                if (isset($detalle->tiene_iva)) {
                    $subtotalSinIva += $detalle->subtotal;
                    $totalIva += $detalle->valor_iva;
                } else {
                    // Compatibilidad con registros antiguos
                    $subtotalSinIva += $detalle->subtotal;
                    // Usar el IVA general si no hay detalle
                    $totalIva += $compra->iva;
                }
            }
            
            Log::info('Valores calculados para comprobante de compra', [
                'subtotal_sin_iva' => $subtotalSinIva,
                'total_iva' => $totalIva,
                'total' => $compra->total
            ]);

            // Preparar datos del comprobante
            $datos = [
                'fecha' => $compra->fecha_compra,
                'descripcion' => "Compra No. {$compra->numero_factura}",
                'prefijo' => 'C',
                'estado' => 'Aprobado'
            ];
            
            // Usar el servicio de plantillas para obtener los movimientos
            $plantillaService = new PlantillaComprobanteService();
            $datosPlantilla = [
                'subtotal' => $subtotalSinIva,
                'iva' => $totalIva,
                'total' => $compra->total,
                'referencia_id' => $compra->id,
                'numero_documento' => $compra->numero_factura
            ];
            
            try {
                $movimientos = $plantillaService->obtenerPlantillaCompra($datosPlantilla);
                
                Log::info('Plantilla de compra aplicada correctamente', [
                    'compra_id' => $compra->id,
                    'movimientos' => count($movimientos)
                ]);
            } catch (\Exception $e) {
                Log::warning('Error al aplicar plantilla de compra, usando método alternativo', [
                    'error' => $e->getMessage()
                ]);
                
                // Método alternativo si falla la plantilla
                $cuenta_inventario = $this->obtenerCuentaPorConcepto('inventario');
                $cuenta_iva = $this->obtenerCuentaPorConcepto('iva_compras');
                $cuenta_proveedores = $this->obtenerCuentaPorConcepto('proveedores');

                if (!$cuenta_inventario || !$cuenta_proveedores) {
                    throw new \Exception('No se encontraron las cuentas contables necesarias para generar el comprobante de compra');
                }

                // Preparar movimientos manualmente
                $movimientos = [
                    [
                        'cuenta_id' => $cuenta_inventario->id,
                        'descripcion' => "Compra No. {$compra->numero_factura}",
                        'debito' => $subtotalSinIva,
                        'credito' => 0,
                        'referencia' => $compra->id,
                        'referencia_tipo' => 'App\\Models\\Compra'
                    ],
                    [
                        'cuenta_id' => $cuenta_proveedores->id,
                        'descripcion' => "Compra No. {$compra->numero_factura}",
                        'debito' => 0,
                        'credito' => $compra->total,
                        'referencia' => $compra->id,
                        'referencia_tipo' => 'App\\Models\\Compra'
                    ]
                ];

                // Añadir movimiento de IVA si hay productos con IVA
                if ($totalIva > 0 && $cuenta_iva) {
                    $movimientos[] = [
                        'cuenta_id' => $cuenta_iva->id,
                        'descripcion' => "IVA Compra No. {$compra->numero_factura}",
                        'debito' => $totalIva,
                        'credito' => 0,
                        'referencia' => $compra->id,
                        'referencia_tipo' => 'App\\Models\\Compra'
                    ];
                }
            }

            // Generar comprobante
            $comprobante = $this->generarComprobante('Egreso', $datos, $movimientos);
            
            // Actualizar la compra con el ID del comprobante
            $compra->comprobante_id = $comprobante->id;
            $compra->save();
            
            return $comprobante;
        } catch (\Exception $e) {
            Log::error('Error al generar comprobante de compra', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Genera un número de comprobante único para un prefijo dado
     *
     * @param string $prefijo
     * @return string
     */
    public function generarNumeroComprobante($prefijo)
    {
        $ultimoNumero = Comprobante::where('prefijo', $prefijo)
            ->orderBy('id', 'desc')
            ->first();

        $siguienteNumero = ($ultimoNumero ? intval($ultimoNumero->numero) + 1 : 1);
        
        return str_pad($siguienteNumero, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Obtiene el prefijo por defecto para un tipo de comprobante
     *
     * @param string $tipo
     * @return string
     */
    public function obtenerPrefijoPorTipo($tipo)
    {
        return match($tipo) {
            'Ingreso' => 'I',
            'Egreso' => 'E',
            'Diario' => 'D',
            default => 'X'
        };
    }

    /**
     * Obtiene una cuenta contable por concepto
     *
     * @param string $concepto
     * @return \App\Models\PlanCuenta|null
     */
    public function obtenerCuentaPorConcepto($concepto)
    {
        return ConfiguracionContable::getCuentaPorConcepto($concepto);
    }
}
