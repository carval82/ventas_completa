<?php

namespace App\Services;

use App\Models\Comprobante;
use App\Models\MovimientoContable;
use App\Models\Venta;
use App\Models\Compra;
use App\Models\PlanCuenta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ContabilidadQueryService
{
    /**
     * Genera un reporte fiscal de IVA para un período específico
     * Utiliza caché para optimizar consultas frecuentes
     *
     * @param string $fechaInicio Fecha de inicio en formato Y-m-d
     * @param string $fechaFin Fecha de fin en formato Y-m-d
     * @return array Datos del reporte fiscal de IVA
     */
    public function generarReporteFiscalIva($fechaInicio, $fechaFin)
    {
        // Convertir fechas a objetos Carbon para manipulación
        $inicio = Carbon::parse($fechaInicio)->startOfDay();
        $fin = Carbon::parse($fechaFin)->endOfDay();
        
        // Clave de caché única para este reporte y período
        $cacheKey = 'reporte_fiscal_iva_' . $fechaInicio . '_' . $fechaFin;
        
        // Intentar obtener de caché primero (30 minutos)
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 1800, function () use ($inicio, $fin) {
            // Inicializar estructura del reporte
            $reporte = [
                'resumen' => [
                    'iva_generado' => 0,
                    'iva_descontable' => 0,
                    'saldo_a_pagar' => 0,
                    'saldo_a_favor' => 0,
                ],
                'ventas' => [
                    'gravadas' => [
                        'total' => 0,
                        'iva' => 0,
                        'detalle' => []
                    ],
                    'excluidas' => [
                        'total' => 0,
                        'detalle' => []
                    ],
                    'exentas' => [
                        'total' => 0,
                        'detalle' => []
                    ],
                ],
                'compras' => [
                    'gravadas' => [
                        'total' => 0,
                        'iva' => 0,
                        'detalle' => []
                    ],
                    'excluidas' => [
                        'total' => 0,
                        'detalle' => []
                    ],
                    'exentas' => [
                        'total' => 0,
                        'detalle' => []
                    ],
                ]
            ];
            
            // Obtener ventas del período
            $ventas = DB::table('ventas')
                ->join('clientes', 'ventas.cliente_id', '=', 'clientes.id')
                ->select(
                    'ventas.id',
                    'ventas.numero_factura',
                    'ventas.fecha_venta as fecha',
                    'ventas.total',
                    'ventas.iva',
                    'ventas.subtotal',
                    DB::raw("CONCAT(clientes.nombres, ' ', clientes.apellidos) as cliente_nombre"),
                    'clientes.cedula as cliente_documento',
                    'clientes.tipo_documento as cliente_tipo_documento'
                )
                ->whereBetween('ventas.fecha_venta', [$inicio, $fin])
                ->orderBy('ventas.fecha_venta', 'asc')
                ->get();
            
            // Procesar ventas
            foreach ($ventas as $venta) {
                // Determinar tipo de venta (gravada, excluida, exenta)
                if ($venta->iva > 0) {
                    // Venta gravada
                    $reporte['ventas']['gravadas']['total'] += $venta->subtotal;
                    $reporte['ventas']['gravadas']['iva'] += $venta->iva;
                    $reporte['ventas']['gravadas']['detalle'][] = [
                        'id' => $venta->id,
                        'numero' => $venta->numero_factura,
                        'fecha' => $venta->fecha,
                        'cliente' => $venta->cliente_nombre,
                        'documento' => $venta->cliente_documento,
                        'subtotal' => $venta->subtotal,
                        'iva' => $venta->iva,
                        'total' => $venta->total
                    ];
                } else {
                    // Determinar si es excluida o exenta
                    // Por simplicidad, asumimos que si no tiene IVA es excluida
                    // En un sistema real, esto debería determinarse por la configuración de los productos
                    $reporte['ventas']['excluidas']['total'] += $venta->total;
                    $reporte['ventas']['excluidas']['detalle'][] = [
                        'id' => $venta->id,
                        'numero' => $venta->numero_factura,
                        'fecha' => $venta->fecha,
                        'cliente' => $venta->cliente_nombre,
                        'documento' => $venta->cliente_documento,
                        'total' => $venta->total
                    ];
                }
            }
            
            // Obtener compras del período
            $compras = DB::table('compras')
                ->join('proveedores', 'compras.proveedor_id', '=', 'proveedores.id')
                ->select(
                    'compras.id',
                    'compras.numero_factura',
                    'compras.fecha_compra as fecha',
                    'compras.total',
                    'compras.iva',
                    'compras.subtotal',
                    'proveedores.razon_social as proveedor_nombre',
                    'proveedores.nit as proveedor_nit'
                )
                ->whereBetween('compras.fecha_compra', [$inicio, $fin])
                ->orderBy('compras.fecha_compra', 'asc')
                ->get();
            
            // Procesar compras
            foreach ($compras as $compra) {
                // Determinar tipo de compra (gravada, excluida, exenta)
                if ($compra->iva > 0) {
                    // Compra gravada
                    $reporte['compras']['gravadas']['total'] += $compra->subtotal;
                    $reporte['compras']['gravadas']['iva'] += $compra->iva;
                    $reporte['compras']['gravadas']['detalle'][] = [
                        'id' => $compra->id,
                        'numero' => $compra->numero_factura,
                        'fecha' => $compra->fecha,
                        'proveedor' => $compra->proveedor_nombre,
                        'nit' => $compra->proveedor_nit,
                        'subtotal' => $compra->subtotal,
                        'iva' => $compra->iva,
                        'total' => $compra->total
                    ];
                } else {
                    // Determinar si es excluida o exenta
                    // Por simplicidad, asumimos que si no tiene IVA es excluida
                    $reporte['compras']['excluidas']['total'] += $compra->total;
                    $reporte['compras']['excluidas']['detalle'][] = [
                        'id' => $compra->id,
                        'numero' => $compra->numero_factura,
                        'fecha' => $compra->fecha,
                        'proveedor' => $compra->proveedor_nombre,
                        'nit' => $compra->proveedor_nit,
                        'total' => $compra->total
                    ];
                }
            }
            
            // Calcular resumen
            $reporte['resumen']['iva_generado'] = $reporte['ventas']['gravadas']['iva'];
            $reporte['resumen']['iva_descontable'] = $reporte['compras']['gravadas']['iva'];
            
            // Determinar saldo a pagar o a favor
            $saldo = $reporte['resumen']['iva_generado'] - $reporte['resumen']['iva_descontable'];
            if ($saldo > 0) {
                $reporte['resumen']['saldo_a_pagar'] = $saldo;
                $reporte['resumen']['saldo_a_favor'] = 0;
            } else {
                $reporte['resumen']['saldo_a_pagar'] = 0;
                $reporte['resumen']['saldo_a_favor'] = abs($saldo);
            }
            
            return $reporte;
        });
    }
    /**
     * Obtiene los movimientos contables de una cuenta en un período específico
     * Optimizado para reducir la carga de la base de datos
     *
     * @param int $cuentaId ID de la cuenta contable
     * @param string $fechaInicio Fecha de inicio en formato Y-m-d
     * @param string $fechaFin Fecha de fin en formato Y-m-d
     * @return array
     */
    public function obtenerMovimientosCuenta($cuentaId, $fechaInicio, $fechaFin)
    {
        try {
            // Convertir fechas a objetos Carbon para manipulación
            $inicio = Carbon::parse($fechaInicio)->startOfDay();
            $fin = Carbon::parse($fechaFin)->endOfDay();
            
            // Usar query builder para optimizar la consulta
            $movimientos = DB::table('movimientos_contables')
                ->join('comprobantes', 'movimientos_contables.comprobante_id', '=', 'comprobantes.id')
                ->join('plan_cuentas', 'movimientos_contables.cuenta_id', '=', 'plan_cuentas.id')
                ->select(
                    'movimientos_contables.id',
                    'comprobantes.fecha',
                    'comprobantes.prefijo',
                    'comprobantes.numero',
                    'movimientos_contables.descripcion',
                    'movimientos_contables.debito',
                    'movimientos_contables.credito',
                    'plan_cuentas.codigo as cuenta_codigo',
                    'plan_cuentas.nombre as cuenta_nombre'
                )
                ->where('movimientos_contables.cuenta_id', $cuentaId)
                ->whereBetween('comprobantes.fecha', [$inicio, $fin])
                ->where('comprobantes.estado', 'Aprobado')
                ->orderBy('comprobantes.fecha', 'asc')
                ->orderBy('comprobantes.id', 'asc')
                ->get();
                
            // Calcular saldos acumulados
            $saldoAcumulado = 0;
            $resultado = [];
            
            foreach ($movimientos as $movimiento) {
                $saldoAcumulado += $movimiento->debito - $movimiento->credito;
                
                $resultado[] = [
                    'id' => $movimiento->id,
                    'fecha' => $movimiento->fecha,
                    'comprobante' => $movimiento->prefijo . '-' . $movimiento->numero,
                    'descripcion' => $movimiento->descripcion,
                    'debito' => $movimiento->debito,
                    'credito' => $movimiento->credito,
                    'saldo' => $saldoAcumulado
                ];
            }
            
            return $resultado;
        } catch (\Exception $e) {
            Log::error('Error al obtener movimientos de cuenta', [
                'cuenta_id' => $cuentaId,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Obtiene el saldo de una cuenta a una fecha específica
     * Optimizado con índices y caché
     *
     * @param int $cuentaId ID de la cuenta contable
     * @param string $fecha Fecha en formato Y-m-d
     * @return float
     */
    public function obtenerSaldoCuenta($cuentaId, $fecha = null)
    {
        try {
            // Si no se especifica fecha, usar la fecha actual
            $fechaLimite = $fecha ? Carbon::parse($fecha)->endOfDay() : Carbon::now()->endOfDay();
            
            // Usar una clave de caché única para esta consulta
            $cacheKey = "saldo_cuenta_{$cuentaId}_{$fechaLimite->format('Y-m-d')}";
            
            // Intentar obtener el resultado desde la caché
            if (cache()->has($cacheKey)) {
                return cache()->get($cacheKey);
            }
            
            // Si no está en caché, calcular el saldo
            $saldo = DB::table('movimientos_contables')
                ->join('comprobantes', 'movimientos_contables.comprobante_id', '=', 'comprobantes.id')
                ->where('movimientos_contables.cuenta_id', $cuentaId)
                ->where('comprobantes.fecha', '<=', $fechaLimite)
                ->where('comprobantes.estado', 'Aprobado')
                ->select(
                    DB::raw('SUM(movimientos_contables.debito) as total_debito'),
                    DB::raw('SUM(movimientos_contables.credito) as total_credito')
                )
                ->first();
                
            $resultado = ($saldo->total_debito ?? 0) - ($saldo->total_credito ?? 0);
            
            // Guardar en caché por 1 hora
            cache()->put($cacheKey, $resultado, 60 * 60);
            
            return $resultado;
        } catch (\Exception $e) {
            Log::error('Error al obtener saldo de cuenta', [
                'cuenta_id' => $cuentaId,
                'fecha' => $fecha,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Obtiene un balance de comprobación para un período específico
     * Optimizado para reducir la carga de la base de datos
     *
     * @param string $fechaInicio Fecha de inicio en formato Y-m-d
     * @param string $fechaFin Fecha de fin en formato Y-m-d
     * @return array
     */
    public function obtenerBalanceComprobacion($fechaInicio, $fechaFin)
    {
        try {
            // Convertir fechas a objetos Carbon para manipulación
            $inicio = Carbon::parse($fechaInicio)->startOfDay();
            $fin = Carbon::parse($fechaFin)->endOfDay();
            
            // Obtener todas las cuentas activas
            $cuentas = PlanCuenta::where('estado', 1)->orderBy('codigo')->get();
            
            // Obtener los saldos iniciales (antes del período)
            $saldosIniciales = [];
            $fechaAnterior = $inicio->copy()->subDay();
            
            foreach ($cuentas as $cuenta) {
                $saldosIniciales[$cuenta->id] = $this->obtenerSaldoCuenta($cuenta->id, $fechaAnterior->format('Y-m-d'));
            }
            
            // Obtener los movimientos del período
            $movimientosPeriodo = DB::table('movimientos_contables')
                ->join('comprobantes', 'movimientos_contables.comprobante_id', '=', 'comprobantes.id')
                ->select(
                    'movimientos_contables.cuenta_id',
                    DB::raw('SUM(movimientos_contables.debito) as total_debito'),
                    DB::raw('SUM(movimientos_contables.credito) as total_credito')
                )
                ->whereBetween('comprobantes.fecha', [$inicio, $fin])
                ->where('comprobantes.estado', 'Aprobado')
                ->groupBy('movimientos_contables.cuenta_id')
                ->get()
                ->keyBy('cuenta_id');
                
            // Preparar el resultado
            $resultado = [];
            $totalDebito = 0;
            $totalCredito = 0;
            $totalSaldoDeudor = 0;
            $totalSaldoAcreedor = 0;
            
            foreach ($cuentas as $cuenta) {
                $saldoInicial = $saldosIniciales[$cuenta->id] ?? 0;
                $movimiento = $movimientosPeriodo[$cuenta->id] ?? null;
                
                $debito = $movimiento ? $movimiento->total_debito : 0;
                $credito = $movimiento ? $movimiento->total_credito : 0;
                
                $saldoFinal = $saldoInicial + $debito - $credito;
                $saldoDeudor = $saldoFinal > 0 ? $saldoFinal : 0;
                $saldoAcreedor = $saldoFinal < 0 ? abs($saldoFinal) : 0;
                
                // Solo incluir cuentas con movimientos o saldo
                if ($saldoInicial != 0 || $debito != 0 || $credito != 0) {
                    $resultado[] = [
                        'cuenta_id' => $cuenta->id,
                        'codigo' => $cuenta->codigo,
                        'nombre' => $cuenta->nombre,
                        'saldo_inicial' => $saldoInicial,
                        'debito' => $debito,
                        'credito' => $credito,
                        'saldo_deudor' => $saldoDeudor,
                        'saldo_acreedor' => $saldoAcreedor
                    ];
                    
                    $totalDebito += $debito;
                    $totalCredito += $credito;
                    $totalSaldoDeudor += $saldoDeudor;
                    $totalSaldoAcreedor += $saldoAcreedor;
                }
            }
            
            return [
                'detalle' => $resultado,
                'totales' => [
                    'debito' => $totalDebito,
                    'credito' => $totalCredito,
                    'saldo_deudor' => $totalSaldoDeudor,
                    'saldo_acreedor' => $totalSaldoAcreedor
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error al obtener balance de comprobación', [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Obtiene un resumen de ventas por período con IVA detallado
     * Optimizado para reportes fiscales
     *
     * @param string $fechaInicio Fecha de inicio en formato Y-m-d
     * @param string $fechaFin Fecha de fin en formato Y-m-d
     * @return array
     */
    public function obtenerResumenVentasConIva($fechaInicio, $fechaFin)
    {
        try {
            // Convertir fechas a objetos Carbon para manipulación
            $inicio = Carbon::parse($fechaInicio)->startOfDay();
            $fin = Carbon::parse($fechaFin)->endOfDay();
            
            // Usar query builder para optimizar la consulta
            $ventas = DB::table('ventas')
                ->leftJoin('detalle_ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
                ->leftJoin('clientes', 'ventas.cliente_id', '=', 'clientes.id')
                ->select(
                    'ventas.id',
                    'ventas.numero_factura',
                    'ventas.fecha_venta',
                    'clientes.nombre as cliente_nombre',
                    'clientes.documento as cliente_documento',
                    DB::raw('SUM(detalle_ventas.subtotal) as subtotal'),
                    DB::raw('SUM(detalle_ventas.valor_iva) as iva'),
                    DB::raw('SUM(detalle_ventas.total_con_iva) as total')
                )
                ->whereBetween('ventas.fecha_venta', [$inicio, $fin])
                ->where('ventas.estado', 'completada')
                ->groupBy('ventas.id', 'ventas.numero_factura', 'ventas.fecha_venta', 'clientes.nombre', 'clientes.documento')
                ->orderBy('ventas.fecha_venta', 'asc')
                ->get();
                
            // Calcular totales
            $totalSubtotal = 0;
            $totalIva = 0;
            $totalVentas = 0;
            
            foreach ($ventas as $venta) {
                $totalSubtotal += $venta->subtotal;
                $totalIva += $venta->iva;
                $totalVentas += $venta->total;
            }
            
            return [
                'ventas' => $ventas,
                'totales' => [
                    'subtotal' => $totalSubtotal,
                    'iva' => $totalIva,
                    'total' => $totalVentas
                ],
                'periodo' => [
                    'inicio' => $fechaInicio,
                    'fin' => $fechaFin
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error al obtener resumen de ventas con IVA', [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Obtiene un resumen de compras por período con IVA detallado
     * Optimizado para reportes fiscales
     *
     * @param string $fechaInicio Fecha de inicio en formato Y-m-d
     * @param string $fechaFin Fecha de fin en formato Y-m-d
     * @return array
     */
    public function obtenerResumenComprasConIva($fechaInicio, $fechaFin)
    {
        try {
            // Convertir fechas a objetos Carbon para manipulación
            $inicio = Carbon::parse($fechaInicio)->startOfDay();
            $fin = Carbon::parse($fechaFin)->endOfDay();
            
            // Usar query builder para optimizar la consulta
            $compras = DB::table('compras')
                ->leftJoin('detalle_compras', 'compras.id', '=', 'detalle_compras.compra_id')
                ->leftJoin('proveedores', 'compras.proveedor_id', '=', 'proveedores.id')
                ->select(
                    'compras.id',
                    'compras.numero_factura',
                    'compras.fecha_compra',
                    'proveedores.nombre as proveedor_nombre',
                    'proveedores.nit as proveedor_nit',
                    DB::raw('SUM(detalle_compras.subtotal) as subtotal'),
                    DB::raw('SUM(detalle_compras.valor_iva) as iva'),
                    DB::raw('SUM(detalle_compras.total_con_iva) as total')
                )
                ->whereBetween('compras.fecha_compra', [$inicio, $fin])
                ->groupBy('compras.id', 'compras.numero_factura', 'compras.fecha_compra', 'proveedores.nombre', 'proveedores.nit')
                ->orderBy('compras.fecha_compra', 'asc')
                ->get();
                
            // Calcular totales
            $totalSubtotal = 0;
            $totalIva = 0;
            $totalCompras = 0;
            
            foreach ($compras as $compra) {
                $totalSubtotal += $compra->subtotal;
                $totalIva += $compra->iva;
                $totalCompras += $compra->total;
            }
            
            return [
                'compras' => $compras,
                'totales' => [
                    'subtotal' => $totalSubtotal,
                    'iva' => $totalIva,
                    'total' => $totalCompras
                ],
                'periodo' => [
                    'inicio' => $fechaInicio,
                    'fin' => $fechaFin
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error al obtener resumen de compras con IVA', [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}
