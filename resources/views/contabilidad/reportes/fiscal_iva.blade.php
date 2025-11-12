@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Reporte Fiscal de IVA</h4>
                    <h6>Período: {{ date('d/m/Y', strtotime($fechaInicio)) }} - {{ date('d/m/Y', strtotime($fechaFin)) }}</h6>
                </div>

                <div class="card-body">
                    <!-- Resumen General -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h5>Resumen General</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>IVA Generado (Ventas):</strong></td>
                                            <td class="text-right">$ {{ number_format($resumenVentas['totales']['iva'], 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>IVA Descontable (Compras):</strong></td>
                                            <td class="text-right">$ {{ number_format($resumenCompras['totales']['iva'], 2) }}</td>
                                        </tr>
                                        <tr class="table-secondary">
                                            <td><strong>Saldo IVA:</strong></td>
                                            <td class="text-right">$ {{ number_format($resumenVentas['totales']['iva'] - $resumenCompras['totales']['iva'], 2) }}</td>
                                        </tr>
                                        @if($saldoPagar > 0)
                                        <tr class="table-danger">
                                            <td><strong>Saldo a Pagar:</strong></td>
                                            <td class="text-right">$ {{ number_format($saldoPagar, 2) }}</td>
                                        </tr>
                                        @endif
                                        @if($saldoFavor > 0)
                                        <tr class="table-success">
                                            <td><strong>Saldo a Favor:</strong></td>
                                            <td class="text-right">$ {{ number_format($saldoFavor, 2) }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h5>Totales por Operación</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr class="table-primary">
                                            <th>Concepto</th>
                                            <th class="text-right">Base</th>
                                            <th class="text-right">IVA</th>
                                            <th class="text-right">Total</th>
                                        </tr>
                                        <tr>
                                            <td><strong>Ventas:</strong></td>
                                            <td class="text-right">$ {{ number_format($resumenVentas['totales']['subtotal'], 2) }}</td>
                                            <td class="text-right">$ {{ number_format($resumenVentas['totales']['iva'], 2) }}</td>
                                            <td class="text-right">$ {{ number_format($resumenVentas['totales']['total'], 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Compras:</strong></td>
                                            <td class="text-right">$ {{ number_format($resumenCompras['totales']['subtotal'], 2) }}</td>
                                            <td class="text-right">$ {{ number_format($resumenCompras['totales']['iva'], 2) }}</td>
                                            <td class="text-right">$ {{ number_format($resumenCompras['totales']['total'], 2) }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detalle de Ventas -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5>Detalle de Ventas con IVA</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Factura</th>
                                            <th>Cliente</th>
                                            <th>Documento</th>
                                            <th class="text-right">Base</th>
                                            <th class="text-right">IVA</th>
                                            <th class="text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($resumenVentas['gravadas'] as $venta)
                                        <tr>
                                            <td>{{ date('d/m/Y', strtotime($venta['fecha'])) }}</td>
                                            <td>{{ $venta['numero'] }}</td>
                                            <td>{{ $venta['cliente'] }}</td>
                                            <td>{{ $venta['documento'] }}</td>
                                            <td class="text-right">$ {{ number_format($venta['subtotal'], 2) }}</td>
                                            <td class="text-right">$ {{ number_format($venta['iva'], 2) }}</td>
                                            <td class="text-right">$ {{ number_format($venta['total'], 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-secondary">
                                        <tr>
                                            <th colspan="4">TOTALES</th>
                                            <th class="text-right">$ {{ number_format($resumenVentas['totales']['subtotal'], 2) }}</th>
                                            <th class="text-right">$ {{ number_format($resumenVentas['totales']['iva'], 2) }}</th>
                                            <th class="text-right">$ {{ number_format($resumenVentas['totales']['total'], 2) }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Detalle de Compras -->
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5>Detalle de Compras con IVA</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Factura</th>
                                            <th>Proveedor</th>
                                            <th>NIT</th>
                                            <th class="text-right">Base</th>
                                            <th class="text-right">IVA</th>
                                            <th class="text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($resumenCompras['gravadas'] as $compra)
                                        <tr>
                                            <td>{{ date('d/m/Y', strtotime($compra['fecha'])) }}</td>
                                            <td>{{ $compra['numero'] }}</td>
                                            <td>{{ $compra['proveedor'] }}</td>
                                            <td>{{ $compra['nit'] }}</td>
                                            <td class="text-right">$ {{ number_format($compra['subtotal'], 2) }}</td>
                                            <td class="text-right">$ {{ number_format($compra['iva'], 2) }}</td>
                                            <td class="text-right">$ {{ number_format($compra['total'], 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-secondary">
                                        <tr>
                                            <th colspan="4">TOTALES</th>
                                            <th class="text-right">$ {{ number_format($resumenCompras['totales']['subtotal'], 2) }}</th>
                                            <th class="text-right">$ {{ number_format($resumenCompras['totales']['iva'], 2) }}</th>
                                            <th class="text-right">$ {{ number_format($resumenCompras['totales']['total'], 2) }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <a href="{{ route('contabilidad.reportes.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                        <div class="col-md-6 text-right">
                            <div class="text-end mb-3">
                                <a href="{{ route('reportes.fiscal-iva', ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'export' => 'excel']) }}" class="btn btn-success me-2">
                                    <i class="fas fa-file-excel"></i> Exportar a Excel
                                </a>
                                <a href="{{ route('reportes.fiscal-iva', ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'export' => 'pdf']) }}" class="btn btn-danger me-2">
                                    <i class="fas fa-file-pdf"></i> Exportar a PDF
                                </a>
                                <button class="btn btn-primary" onclick="window.print()">
                                    <i class="fas fa-print"></i> Imprimir
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Configuración para impresión
        @if(request()->has('print'))
        setTimeout(function() {
            window.print();
        }, 500);
        @endif
    });
</script>
@endsection
