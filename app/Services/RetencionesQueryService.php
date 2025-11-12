<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Models\Venta;
use App\Models\Compra;
use App\Models\Retencion;
use App\Models\DetalleRetencion;

class RetencionesQueryService
{
    /**
     * Obtiene un resumen de retenciones efectuadas (en ventas)
     * 
     * @param string $fechaInicio Fecha de inicio del período
     * @param string $fechaFin Fecha de fin del período
     * @param string|null $tipoRetencion Tipo de retención (opcional)
     * @return array Resumen de retenciones efectuadas
     */
    public function obtenerResumenRetencionesEfectuadas($fechaInicio, $fechaFin, $tipoRetencion = null)
    {
        // Clave de caché única para esta consulta
        $cacheKey = "retenciones_efectuadas_{$fechaInicio}_{$fechaFin}_" . ($tipoRetencion ?? 'all');
        
        // Intentar obtener del caché primero (30 minutos)
        return Cache::remember($cacheKey, 1800, function () use ($fechaInicio, $fechaFin, $tipoRetencion) {
            // Consulta para obtener el detalle de retenciones
            $detalle = DB::table('retenciones')
                ->join('ventas', 'retenciones.venta_id', '=', 'ventas.id')
                ->join('clientes', 'ventas.cliente_id', '=', 'clientes.id')
                ->select(
                    'retenciones.id',
                    'retenciones.fecha',
                    'retenciones.tipo',
                    'retenciones.porcentaje',
                    'retenciones.base',
                    'retenciones.valor',
                    'retenciones.numero_certificado',
                    'ventas.numero_factura',
                    'clientes.nombre as cliente',
                    'clientes.nit'
                )
                ->whereBetween('retenciones.fecha', [$fechaInicio, $fechaFin])
                ->when($tipoRetencion, function ($query) use ($tipoRetencion) {
                    return $query->where('retenciones.tipo', $tipoRetencion);
                })
                ->orderBy('retenciones.fecha')
                ->get();
            
            // Consulta para obtener totales
            $totales = DB::table('retenciones')
                ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                ->when($tipoRetencion, function ($query) use ($tipoRetencion) {
                    return $query->where('tipo', $tipoRetencion);
                })
                ->select(
                    DB::raw('SUM(base) as total_base'),
                    DB::raw('SUM(valor) as total_retenido'),
                    DB::raw('COUNT(*) as cantidad')
                )
                ->first();
            
            // Agrupar por tipo de retención
            $porTipo = DB::table('retenciones')
                ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                ->groupBy('tipo')
                ->select(
                    'tipo',
                    DB::raw('SUM(base) as total_base'),
                    DB::raw('SUM(valor) as total_retenido'),
                    DB::raw('COUNT(*) as cantidad')
                )
                ->get();
            
            return [
                'detalle' => $detalle,
                'totales' => $totales,
                'por_tipo' => $porTipo
            ];
        });
    }
    
    /**
     * Obtiene un resumen de retenciones practicadas (en compras)
     * 
     * @param string $fechaInicio Fecha de inicio del período
     * @param string $fechaFin Fecha de fin del período
     * @param string|null $tipoRetencion Tipo de retención (opcional)
     * @return array Resumen de retenciones practicadas
     */
    public function obtenerResumenRetencionesPracticadas($fechaInicio, $fechaFin, $tipoRetencion = null)
    {
        // Clave de caché única para esta consulta
        $cacheKey = "retenciones_practicadas_{$fechaInicio}_{$fechaFin}_" . ($tipoRetencion ?? 'all');
        
        // Intentar obtener del caché primero (30 minutos)
        return Cache::remember($cacheKey, 1800, function () use ($fechaInicio, $fechaFin, $tipoRetencion) {
            // Consulta para obtener el detalle de retenciones
            $detalle = DB::table('retenciones')
                ->join('compras', 'retenciones.compra_id', '=', 'compras.id')
                ->join('proveedores', 'compras.proveedor_id', '=', 'proveedores.id')
                ->select(
                    'retenciones.id',
                    'retenciones.fecha',
                    'retenciones.tipo',
                    'retenciones.porcentaje',
                    'retenciones.base',
                    'retenciones.valor',
                    'retenciones.numero_certificado',
                    'compras.numero_factura',
                    'proveedores.nombre as proveedor',
                    'proveedores.nit'
                )
                ->whereBetween('retenciones.fecha', [$fechaInicio, $fechaFin])
                ->when($tipoRetencion, function ($query) use ($tipoRetencion) {
                    return $query->where('retenciones.tipo', $tipoRetencion);
                })
                ->orderBy('retenciones.fecha')
                ->get();
            
            // Consulta para obtener totales
            $totales = DB::table('retenciones')
                ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                ->when($tipoRetencion, function ($query) use ($tipoRetencion) {
                    return $query->where('tipo', $tipoRetencion);
                })
                ->select(
                    DB::raw('SUM(base) as total_base'),
                    DB::raw('SUM(valor) as total_retenido'),
                    DB::raw('COUNT(*) as cantidad')
                )
                ->first();
            
            // Agrupar por tipo de retención
            $porTipo = DB::table('retenciones')
                ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                ->groupBy('tipo')
                ->select(
                    'tipo',
                    DB::raw('SUM(base) as total_base'),
                    DB::raw('SUM(valor) as total_retenido'),
                    DB::raw('COUNT(*) as cantidad')
                )
                ->get();
            
            return [
                'detalle' => $detalle,
                'totales' => $totales,
                'por_tipo' => $porTipo
            ];
        });
    }
    
    /**
     * Genera un reporte fiscal de retenciones
     * 
     * @param string $fechaInicio Fecha de inicio del período
     * @param string $fechaFin Fecha de fin del período
     * @return array Datos del reporte fiscal de retenciones
     */
    public function generarReporteFiscalRetenciones($fechaInicio, $fechaFin)
    {
        // Clave de caché única para esta consulta
        $cacheKey = "reporte_fiscal_retenciones_{$fechaInicio}_{$fechaFin}";
        
        // Intentar obtener del caché primero (30 minutos)
        return Cache::remember($cacheKey, 1800, function () use ($fechaInicio, $fechaFin) {
            // Obtener resúmenes de retenciones
            $retencionesEfectuadas = $this->obtenerResumenRetencionesEfectuadas($fechaInicio, $fechaFin);
            $retencionesPracticadas = $this->obtenerResumenRetencionesPracticadas($fechaInicio, $fechaFin);
            
            // Calcular saldos
            $totalEfectuadas = $retencionesEfectuadas['totales']->total_retenido ?? 0;
            $totalPracticadas = $retencionesPracticadas['totales']->total_retenido ?? 0;
            
            return [
                'retenciones_efectuadas' => $retencionesEfectuadas,
                'retenciones_practicadas' => $retencionesPracticadas,
                'total_efectuadas' => $totalEfectuadas,
                'total_practicadas' => $totalPracticadas,
                'saldo_a_pagar' => $totalEfectuadas,
                'saldo_a_favor' => 0
            ];
        });
    }
    
    /**
     * Obtiene los certificados de retención emitidos
     * 
     * @param string $fechaInicio Fecha de inicio del período
     * @param string $fechaFin Fecha de fin del período
     * @return \Illuminate\Support\Collection Certificados de retención
     */
    public function obtenerCertificadosRetencion($fechaInicio, $fechaFin)
    {
        return DB::table('retenciones')
            ->join('ventas', 'retenciones.venta_id', '=', 'ventas.id')
            ->join('clientes', 'ventas.cliente_id', '=', 'clientes.id')
            ->select(
                'retenciones.id',
                'retenciones.fecha',
                'retenciones.numero_certificado',
                'clientes.nombre as cliente',
                'clientes.nit',
                DB::raw('SUM(retenciones.valor) as total_retenido'),
                DB::raw('COUNT(*) as cantidad_conceptos')
            )
            ->whereBetween('retenciones.fecha', [$fechaInicio, $fechaFin])
            ->whereNotNull('retenciones.numero_certificado')
            ->groupBy('retenciones.numero_certificado', 'retenciones.id', 'retenciones.fecha', 'clientes.nombre', 'clientes.nit')
            ->orderBy('retenciones.fecha', 'desc')
            ->get();
    }
    
    /**
     * Obtiene el detalle de un certificado de retención
     * 
     * @param int $certificadoId ID del certificado de retención
     * @return array Detalle del certificado de retención
     */
    public function obtenerDetalleCertificado($certificadoId)
    {
        // Obtener datos del certificado
        $certificado = DB::table('retenciones')
            ->join('ventas', 'retenciones.venta_id', '=', 'ventas.id')
            ->join('clientes', 'ventas.cliente_id', '=', 'clientes.id')
            ->select(
                'retenciones.*',
                'ventas.numero_factura',
                'ventas.fecha as fecha_venta',
                'clientes.nombre as cliente',
                'clientes.nit',
                'clientes.direccion',
                'clientes.telefono'
            )
            ->where('retenciones.id', $certificadoId)
            ->first();
        
        // Obtener conceptos del certificado
        $conceptos = DB::table('detalle_retenciones')
            ->where('retencion_id', $certificadoId)
            ->get();
        
        return [
            'certificado' => $certificado,
            'conceptos' => $conceptos
        ];
    }
}
