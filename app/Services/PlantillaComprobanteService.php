<?php

namespace App\Services;

use App\Models\ConfiguracionContable;
use Illuminate\Support\Facades\Log;

class PlantillaComprobanteService
{
    /**
     * Obtiene la plantilla de movimientos para un comprobante de venta
     *
     * @param array $datos Datos necesarios para la plantilla
     * @return array
     */
    public function obtenerPlantillaVenta(array $datos)
    {
        try {
            // Validar datos requeridos
            $this->validarDatosRequeridos($datos, ['subtotal', 'iva', 'total', 'referencia_id', 'numero_documento']);
            
            // Obtener cuentas contables
            $cuenta_caja = $this->obtenerCuentaObligatoria('caja');
            $cuenta_ventas = $this->obtenerCuentaObligatoria('ventas');
            
            // Preparar movimientos base
            $movimientos = [
                [
                    'cuenta_id' => $cuenta_caja->id,
                    'descripcion' => "Venta No. {$datos['numero_documento']}",
                    'debito' => $datos['total'],
                    'credito' => 0,
                    'referencia' => $datos['referencia_id'],
                    'referencia_tipo' => 'App\\Models\\Venta'
                ],
                [
                    'cuenta_id' => $cuenta_ventas->id,
                    'descripcion' => "Venta No. {$datos['numero_documento']}",
                    'debito' => 0,
                    'credito' => $datos['subtotal'],
                    'referencia' => $datos['referencia_id'],
                    'referencia_tipo' => 'App\\Models\\Venta'
                ]
            ];
            
            // Añadir movimiento de IVA si aplica
            if ($datos['iva'] > 0) {
                $cuenta_iva = $this->obtenerCuentaObligatoria('iva_ventas');
                $movimientos[] = [
                    'cuenta_id' => $cuenta_iva->id,
                    'descripcion' => "IVA Venta No. {$datos['numero_documento']}",
                    'debito' => 0,
                    'credito' => $datos['iva'],
                    'referencia' => $datos['referencia_id'],
                    'referencia_tipo' => 'App\\Models\\Venta'
                ];
            }
            
            // Añadir movimientos adicionales si existen
            if (isset($datos['movimientos_adicionales']) && is_array($datos['movimientos_adicionales'])) {
                $movimientos = array_merge($movimientos, $datos['movimientos_adicionales']);
            }
            
            return $movimientos;
        } catch (\Exception $e) {
            Log::error('Error al obtener plantilla de venta', [
                'error' => $e->getMessage(),
                'datos' => $datos
            ]);
            throw $e;
        }
    }
    
    /**
     * Obtiene la plantilla de movimientos para un comprobante de compra
     *
     * @param array $datos Datos necesarios para la plantilla
     * @return array
     */
    public function obtenerPlantillaCompra(array $datos)
    {
        try {
            // Validar datos requeridos
            $this->validarDatosRequeridos($datos, ['subtotal', 'iva', 'total', 'referencia_id', 'numero_documento']);
            
            // Obtener cuentas contables
            $cuenta_inventario = $this->obtenerCuentaObligatoria('inventario');
            $cuenta_proveedores = $this->obtenerCuentaObligatoria('proveedores');
            
            // Preparar movimientos base
            $movimientos = [
                [
                    'cuenta_id' => $cuenta_inventario->id,
                    'descripcion' => "Compra No. {$datos['numero_documento']}",
                    'debito' => $datos['subtotal'],
                    'credito' => 0,
                    'referencia' => $datos['referencia_id'],
                    'referencia_tipo' => 'App\\Models\\Compra'
                ],
                [
                    'cuenta_id' => $cuenta_proveedores->id,
                    'descripcion' => "Compra No. {$datos['numero_documento']}",
                    'debito' => 0,
                    'credito' => $datos['total'],
                    'referencia' => $datos['referencia_id'],
                    'referencia_tipo' => 'App\\Models\\Compra'
                ]
            ];
            
            // Añadir movimiento de IVA si aplica
            if ($datos['iva'] > 0) {
                $cuenta_iva = $this->obtenerCuentaObligatoria('iva_compras');
                $movimientos[] = [
                    'cuenta_id' => $cuenta_iva->id,
                    'descripcion' => "IVA Compra No. {$datos['numero_documento']}",
                    'debito' => $datos['iva'],
                    'credito' => 0,
                    'referencia' => $datos['referencia_id'],
                    'referencia_tipo' => 'App\\Models\\Compra'
                ];
            }
            
            // Añadir movimientos adicionales si existen
            if (isset($datos['movimientos_adicionales']) && is_array($datos['movimientos_adicionales'])) {
                $movimientos = array_merge($movimientos, $datos['movimientos_adicionales']);
            }
            
            return $movimientos;
        } catch (\Exception $e) {
            Log::error('Error al obtener plantilla de compra', [
                'error' => $e->getMessage(),
                'datos' => $datos
            ]);
            throw $e;
        }
    }
    
    /**
     * Obtiene la plantilla de movimientos para un comprobante de pago a proveedor
     *
     * @param array $datos Datos necesarios para la plantilla
     * @return array
     */
    public function obtenerPlantillaPagoProveedor(array $datos)
    {
        try {
            // Validar datos requeridos
            $this->validarDatosRequeridos($datos, ['total', 'referencia_id', 'numero_documento', 'metodo_pago']);
            
            // Obtener cuentas contables
            $cuenta_proveedores = $this->obtenerCuentaObligatoria('proveedores');
            
            // Determinar la cuenta de origen según el método de pago
            $cuenta_origen = $this->obtenerCuentaSegunMetodoPago($datos['metodo_pago']);
            
            // Preparar movimientos
            $movimientos = [
                [
                    'cuenta_id' => $cuenta_proveedores->id,
                    'descripcion' => "Pago a proveedor No. {$datos['numero_documento']}",
                    'debito' => $datos['total'],
                    'credito' => 0,
                    'referencia' => $datos['referencia_id'],
                    'referencia_tipo' => 'App\\Models\\PagoProveedor'
                ],
                [
                    'cuenta_id' => $cuenta_origen->id,
                    'descripcion' => "Pago a proveedor No. {$datos['numero_documento']}",
                    'debito' => 0,
                    'credito' => $datos['total'],
                    'referencia' => $datos['referencia_id'],
                    'referencia_tipo' => 'App\\Models\\PagoProveedor'
                ]
            ];
            
            // Añadir movimientos adicionales si existen
            if (isset($datos['movimientos_adicionales']) && is_array($datos['movimientos_adicionales'])) {
                $movimientos = array_merge($movimientos, $datos['movimientos_adicionales']);
            }
            
            return $movimientos;
        } catch (\Exception $e) {
            Log::error('Error al obtener plantilla de pago a proveedor', [
                'error' => $e->getMessage(),
                'datos' => $datos
            ]);
            throw $e;
        }
    }
    
    /**
     * Valida que los datos requeridos estén presentes
     *
     * @param array $datos
     * @param array $requeridos
     * @return void
     * @throws \Exception
     */
    protected function validarDatosRequeridos(array $datos, array $requeridos)
    {
        $faltantes = [];
        
        foreach ($requeridos as $campo) {
            if (!isset($datos[$campo])) {
                $faltantes[] = $campo;
            }
        }
        
        if (!empty($faltantes)) {
            throw new \Exception('Faltan datos requeridos para la plantilla: ' . implode(', ', $faltantes));
        }
    }
    
    /**
     * Obtiene una cuenta contable por concepto, lanzando excepción si no existe
     *
     * @param string $concepto
     * @return \App\Models\PlanCuenta
     * @throws \Exception
     */
    protected function obtenerCuentaObligatoria($concepto)
    {
        $cuenta = ConfiguracionContable::getCuentaPorConcepto($concepto);
        
        if (!$cuenta) {
            throw new \Exception("No se encontró la cuenta contable para el concepto: {$concepto}");
        }
        
        return $cuenta;
    }
    
    /**
     * Obtiene la cuenta contable según el método de pago
     *
     * @param string $metodoPago
     * @return \App\Models\PlanCuenta
     * @throws \Exception
     */
    protected function obtenerCuentaSegunMetodoPago($metodoPago)
    {
        switch (strtolower($metodoPago)) {
            case 'efectivo':
                return $this->obtenerCuentaObligatoria('caja');
            case 'transferencia':
            case 'consignacion':
                return $this->obtenerCuentaObligatoria('banco');
            case 'tarjeta_credito':
                return $this->obtenerCuentaObligatoria('tarjeta_credito');
            case 'tarjeta_debito':
                return $this->obtenerCuentaObligatoria('tarjeta_debito');
            default:
                return $this->obtenerCuentaObligatoria('caja');
        }
    }
}
