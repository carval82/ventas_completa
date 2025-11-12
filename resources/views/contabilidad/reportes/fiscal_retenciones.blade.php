@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Reporte Fiscal de Retenciones</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h4 class="text-center">Reporte Fiscal de Retenciones</h4>
                            <h5 class="text-center">Período: {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}</h5>
                        </div>
                    </div>

                    <div class="text-end mb-3">
                        <a href="{{ route('reportes.fiscal-retenciones', ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'export' => 'excel']) }}" class="btn btn-success me-2">
                            <i class="fas fa-file-excel"></i> Exportar a Excel
                        </a>
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                    </div>

                    <!-- Resumen General -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Resumen General</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Retenciones Efectuadas (Por Tipo)</h6>
                                        </div>
                                        <div class="card-body p-0">
                                            <table class="table table-striped table-bordered mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Tipo</th>
                                                        <th class="text-right">Base</th>
                                                        <th class="text-right">Valor Retenido</th>
                                                        <th class="text-center">Cantidad</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @if(!empty($reporte['retenciones_efectuadas']['por_tipo']))
                                                        @foreach($reporte['retenciones_efectuadas']['por_tipo'] as $tipo)
                                                            <tr>
                                                                <td>{{ $tipo->tipo }}</td>
                                                                <td class="text-right">{{ number_format($tipo->total_base, 2) }}</td>
                                                                <td class="text-right">{{ number_format($tipo->total_retenido, 2) }}</td>
                                                                <td class="text-center">{{ $tipo->cantidad }}</td>
                                                            </tr>
                                                        @endforeach
                                                    @else
                                                        <tr>
                                                            <td colspan="4" class="text-center">No hay datos disponibles</td>
                                                        </tr>
                                                    @endif
                                                </tbody>
                                                <tfoot>
                                                    <tr class="font-weight-bold">
                                                        <td>TOTAL</td>
                                                        <td class="text-right">{{ number_format($reporte['retenciones_efectuadas']['totales']->total_base ?? 0, 2) }}</td>
                                                        <td class="text-right">{{ number_format($reporte['retenciones_efectuadas']['totales']->total_retenido ?? 0, 2) }}</td>
                                                        <td class="text-center">{{ $reporte['retenciones_efectuadas']['totales']->cantidad ?? 0 }}</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Retenciones Practicadas (Por Tipo)</h6>
                                        </div>
                                        <div class="card-body p-0">
                                            <table class="table table-striped table-bordered mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Tipo</th>
                                                        <th class="text-right">Base</th>
                                                        <th class="text-right">Valor Retenido</th>
                                                        <th class="text-center">Cantidad</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @if(!empty($reporte['retenciones_practicadas']['por_tipo']))
                                                        @foreach($reporte['retenciones_practicadas']['por_tipo'] as $tipo)
                                                            <tr>
                                                                <td>{{ $tipo->tipo }}</td>
                                                                <td class="text-right">{{ number_format($tipo->total_base, 2) }}</td>
                                                                <td class="text-right">{{ number_format($tipo->total_retenido, 2) }}</td>
                                                                <td class="text-center">{{ $tipo->cantidad }}</td>
                                                            </tr>
                                                        @endforeach
                                                    @else
                                                        <tr>
                                                            <td colspan="4" class="text-center">No hay datos disponibles</td>
                                                        </tr>
                                                    @endif
                                                </tbody>
                                                <tfoot>
                                                    <tr class="font-weight-bold">
                                                        <td>TOTAL</td>
                                                        <td class="text-right">{{ number_format($reporte['retenciones_practicadas']['totales']->total_base ?? 0, 2) }}</td>
                                                        <td class="text-right">{{ number_format($reporte['retenciones_practicadas']['totales']->total_retenido ?? 0, 2) }}</td>
                                                        <td class="text-center">{{ $reporte['retenciones_practicadas']['totales']->cantidad ?? 0 }}</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-md-6 offset-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <table class="table table-bordered mb-0">
                                                <tr class="font-weight-bold">
                                                    <td>TOTAL RETENCIONES EFECTUADAS</td>
                                                    <td class="text-right">{{ number_format($reporte['total_efectuadas'], 2) }}</td>
                                                </tr>
                                                <tr class="font-weight-bold">
                                                    <td>SALDO A PAGAR</td>
                                                    <td class="text-right">{{ number_format($reporte['saldo_a_pagar'], 2) }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detalle de Retenciones Efectuadas -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Detalle de Retenciones Efectuadas</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Factura</th>
                                            <th>Cliente</th>
                                            <th>NIT</th>
                                            <th>Tipo</th>
                                            <th>Porcentaje</th>
                                            <th class="text-right">Base</th>
                                            <th class="text-right">Valor Retenido</th>
                                            <th>Certificado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(!empty($reporte['retenciones_efectuadas']['detalle']))
                                            @foreach($reporte['retenciones_efectuadas']['detalle'] as $retencion)
                                                <tr>
                                                    <td>{{ \Carbon\Carbon::parse($retencion->fecha)->format('d/m/Y') }}</td>
                                                    <td>{{ $retencion->numero_factura }}</td>
                                                    <td>{{ $retencion->cliente }}</td>
                                                    <td>{{ $retencion->nit }}</td>
                                                    <td>{{ $retencion->tipo }}</td>
                                                    <td>{{ number_format($retencion->porcentaje, 2) }}%</td>
                                                    <td class="text-right">{{ number_format($retencion->base, 2) }}</td>
                                                    <td class="text-right">{{ number_format($retencion->valor, 2) }}</td>
                                                    <td>{{ $retencion->numero_certificado ?? 'N/A' }}</td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="9" class="text-center">No hay datos disponibles</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                    <tfoot>
                                        <tr class="font-weight-bold">
                                            <td colspan="6">TOTAL</td>
                                            <td class="text-right">{{ number_format($reporte['retenciones_efectuadas']['totales']->total_base ?? 0, 2) }}</td>
                                            <td class="text-right">{{ number_format($reporte['retenciones_efectuadas']['totales']->total_retenido ?? 0, 2) }}</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Detalle de Retenciones Practicadas -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Detalle de Retenciones Practicadas</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Factura</th>
                                            <th>Proveedor</th>
                                            <th>NIT</th>
                                            <th>Tipo</th>
                                            <th>Porcentaje</th>
                                            <th class="text-right">Base</th>
                                            <th class="text-right">Valor Retenido</th>
                                            <th>Certificado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(!empty($reporte['retenciones_practicadas']['detalle']))
                                            @foreach($reporte['retenciones_practicadas']['detalle'] as $retencion)
                                                <tr>
                                                    <td>{{ \Carbon\Carbon::parse($retencion->fecha)->format('d/m/Y') }}</td>
                                                    <td>{{ $retencion->numero_factura }}</td>
                                                    <td>{{ $retencion->proveedor }}</td>
                                                    <td>{{ $retencion->nit }}</td>
                                                    <td>{{ $retencion->tipo }}</td>
                                                    <td>{{ number_format($retencion->porcentaje, 2) }}%</td>
                                                    <td class="text-right">{{ number_format($retencion->base, 2) }}</td>
                                                    <td class="text-right">{{ number_format($retencion->valor, 2) }}</td>
                                                    <td>{{ $retencion->numero_certificado ?? 'N/A' }}</td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="9" class="text-center">No hay datos disponibles</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                    <tfoot>
                                        <tr class="font-weight-bold">
                                            <td colspan="6">TOTAL</td>
                                            <td class="text-right">{{ number_format($reporte['retenciones_practicadas']['totales']->total_base ?? 0, 2) }}</td>
                                            <td class="text-right">{{ number_format($reporte['retenciones_practicadas']['totales']->total_retenido ?? 0, 2) }}</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .btn, .card-tools, .main-header, .main-sidebar, .main-footer {
            display: none !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        .card-header {
            background-color: #f8f9fa !important;
            color: #000 !important;
            border-bottom: 1px solid #dee2e6 !important;
        }
        body {
            margin: 0;
            padding: 0;
        }
        .container-fluid {
            width: 100%;
            padding: 0;
        }
    }
</style>
@endsection
