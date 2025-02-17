<?php

namespace App\Models\Traits;

use App\Models\Comprobante;
use App\Models\MovimientoContable;
use App\Models\ConfiguracionContable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait GeneraComprobanteVentaCredito
{
    public function generarComprobanteVentaCredito() 
    {
        Log::info('Iniciando generación de comprobante de venta a crédito', [
            'venta_id' => $this->id,
            'total' => $this->total,
            'subtotal' => $this->subtotal,
            'iva' => $this->iva
        ]);

        return DB::transaction(function() {
            try {
                // 1. Verificar que existan todas las configuraciones necesarias
                $cuenta_por_cobrar = ConfiguracionContable::where('concepto', 'cuentas_por_cobrar')->first();
                if (!$cuenta_por_cobrar || !$cuenta_por_cobrar->cuenta_id) {
                    throw new \Exception('No se encontró la configuración de cuenta por cobrar');
                }

                $cuenta_ventas = ConfiguracionContable::where('concepto', 'ventas')->first();
                if (!$cuenta_ventas || !$cuenta_ventas->cuenta_id) {
                    throw new \Exception('No se encontró la configuración de cuenta de ventas');
                }

                $cuenta_iva = ConfiguracionContable::where('concepto', 'iva_ventas')->first();
                if (!$cuenta_iva || !$cuenta_iva->cuenta_id) {
                    throw new \Exception('No se encontró la configuración de cuenta de IVA');
                }

                Log::info('Configuraciones contables encontradas', [
                    'cuenta_por_cobrar_id' => $cuenta_por_cobrar->cuenta_id,
                    'cuenta_ventas_id' => $cuenta_ventas->cuenta_id,
                    'cuenta_iva_id' => $cuenta_iva->cuenta_id
                ]);

                // 2. Crear el comprobante
                $comprobante = Comprobante::create([
                    'fecha' => $this->fecha_venta,
                    'tipo' => 'Diario',
                    'numero' => $this->generarNumeroComprobanteCredito(),
                    'descripcion' => "Venta a crédito No. {$this->numero_factura}",
                    'estado' => 'Aprobado',
                    'created_by' => Auth::id(),
                    'total_debito' => $this->total,
                    'total_credito' => $this->total
                ]);

                // 3. Crear los movimientos contables
                // Débito a Cuentas por Cobrar
                MovimientoContable::create([
                    'comprobante_id' => $comprobante->id,
                    'cuenta_id' => $cuenta_por_cobrar->cuenta_id,
                    'fecha' => $this->fecha_venta,
                    'descripcion' => "Venta a crédito No. {$this->numero_factura}",
                    'debito' => $this->total,
                    'credito' => 0
                ]);

                // Crédito a Ventas
                MovimientoContable::create([
                    'comprobante_id' => $comprobante->id,
                    'cuenta_id' => $cuenta_ventas->cuenta_id,
                    'fecha' => $this->fecha_venta,
                    'descripcion' => "Venta a crédito No. {$this->numero_factura}",
                    'debito' => 0,
                    'credito' => $this->subtotal
                ]);

                // Crédito a IVA por pagar
                if ($this->iva > 0) {
                    MovimientoContable::create([
                        'comprobante_id' => $comprobante->id,
                        'cuenta_id' => $cuenta_iva->cuenta_id,
                        'fecha' => $this->fecha_venta,
                        'descripcion' => "IVA Venta a crédito No. {$this->numero_factura}",
                        'debito' => 0,
                        'credito' => $this->iva
                    ]);
                }

                Log::info('Comprobante de venta a crédito generado exitosamente', [
                    'comprobante_id' => $comprobante->id
                ]);

                return $comprobante;

            } catch(\Exception $e) {
                Log::error('Error al generar comprobante de venta a crédito', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        });
    }

    private function generarNumeroComprobanteCredito()
    {
        $prefijo = 'VC'; // VC para Ventas a Crédito
        
        $ultimoNumero = DB::table('comprobantes')
            ->where('numero', 'LIKE', $prefijo . '%')
            ->orderBy('numero', 'desc')
            ->value('numero');
            
        if (!$ultimoNumero) {
            return $prefijo . '000001';
        }
        
        $numero = intval(substr($ultimoNumero, 2));
        return $prefijo . str_pad($numero + 1, 6, '0', STR_PAD_LEFT);
    }
} 