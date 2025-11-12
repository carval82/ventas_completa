@extends('layouts.app')

@section('title', 'Reporte de Caja #' . $caja->id)

@section('styles')
<style>
    @media print {
        .no-print {
            display: none !important;
        }
        body {
            font-size: 12pt;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        .card-header {
            background-color: #f8f9fc !important;
            color: #000 !important;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            padding: 5px;
            border: 1px solid #ddd;
        }
        .container-fluid {
            width: 100%;
            padding: 0;
        }
        .page-break {
            page-break-after: always;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-4 no-print">
        <div class="col-md-6">
            <h1 class="h3 mb-0 text-gray-800">Reporte de Caja #{{ $caja->id }}</h1>
        </div>
        <div class="col-md-6 text-md-end">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Imprimir Reporte
            </button>
            <a href="{{ route('cajas.show', $caja) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Detalles
            </a>
        </div>
    </div>

    <!-- Encabezado del Reporte -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Reporte de Caja Diaria</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h4>{{ config('app.name') }}</h4>
                    <p>
                        <strong>Fecha del Reporte:</strong> {{ now()->format('d/m/Y H:i:s') }}<br>
                        <strong>Usuario:</strong> {{ auth()->user()->name }}
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h5>Caja #{{ $caja->id }}</h5>
                    <p>
                        <strong>Estado:</strong> 
                        <span class="badge {{ $caja->estado === 'abierta' ? 'bg-success' : 'bg-secondary' }}">
                            {{ $caja->estado === 'abierta' ? 'Abierta' : 'Cerrada' }}
                        </span><br>
                        <strong>Fecha Apertura:</strong> {{ $caja->fecha_apertura->format('d/m/Y H:i:s') }}<br>
                        @if($caja->fecha_cierre)
                            <strong>Fecha Cierre:</strong> {{ $caja->fecha_cierre->format('d/m/Y H:i:s') }}
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen de la Caja -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Resumen de Operaciones</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th class="table-light" width="50%">Monto de Apertura (Base)</th>
                            <td class="text-end">$ {{ number_format($caja->monto_apertura, 2) }}</td>
                        </tr>
                        <tr>
                            <th class="table-light">Total Ventas (+)</th>
                            <td class="text-end">$ {{ number_format($totalVentas, 2) }}</td>
                        </tr>
                        <tr>
                            <th class="table-light">Total Gastos (-)</th>
                            <td class="text-end">$ {{ number_format($totalGastos, 2) }}</td>
                        </tr>
                        <tr>
                            <th class="table-light">Total Pagos (-)</th>
                            <td class="text-end">$ {{ number_format($totalPagos, 2) }}</td>
                        </tr>
                        <tr class="table-success">
                            <th>Ganancias netas (sin base)</th>
                            <td class="text-end">$ {{ number_format($totalVentas - $totalGastos - $totalPagos, 2) }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th class="table-primary" width="50%">Saldo Teórico</th>
                            <td class="text-end">$ {{ number_format($saldoActual, 2) }}</td>
                        </tr>
                        @if($caja->estado === 'cerrada')
                            <tr>
                                <th class="table-primary">Monto de Cierre</th>
                                <td class="text-end">$ {{ number_format($caja->monto_cierre, 2) }}</td>
                            </tr>
                            <tr>
                                <th class="table-{{ $caja->diferencia < 0 ? 'danger' : ($caja->diferencia > 0 ? 'success' : 'light') }}">
                                    Diferencia
                                </th>
                                <td class="text-end {{ $caja->diferencia < 0 ? 'text-danger' : ($caja->diferencia > 0 ? 'text-success' : '') }}">
                                    $ {{ number_format($caja->diferencia, 2) }}
                                </td>
                            </tr>
                        @endif
                    </table>
                    
                    <div class="alert alert-info mt-3">
                        <strong>Cálculo del saldo:</strong><br>
                        Base inicial: $ {{ number_format($caja->monto_apertura, 2) }}<br>
                        (+) Ventas: $ {{ number_format($totalVentas, 2) }}<br>
                        (-) Gastos: $ {{ number_format($totalGastos, 2) }}<br>
                        (-) Pagos: $ {{ number_format($totalPagos, 2) }}<br>
                        <hr>
                        <strong>= Saldo teórico: $ {{ number_format($saldoActual, 2) }}</strong>
                        @if($caja->estado === 'cerrada')
                        <br>
                        <hr>
                        <strong>Monto real al cierre: $ {{ number_format($caja->monto_cierre, 2) }}</strong><br>
                        <strong class="{{ $caja->diferencia < 0 ? 'text-danger' : ($caja->diferencia > 0 ? 'text-success' : '') }}">
                            Diferencia: $ {{ number_format($caja->diferencia, 2) }}
                            ({{ $caja->diferencia < 0 ? 'Faltante' : ($caja->diferencia > 0 ? 'Sobrante' : 'Sin diferencia') }})
                        </strong>
                        @endif
                        <hr>
                        <strong class="text-success">Saldo sin la base (ganancias netas): $ {{ number_format($totalVentas - $totalGastos - $totalPagos, 2) }}</strong>
                    </div>
                    
                    @if($caja->observaciones)
                        <div class="alert alert-info mt-3">
                            <strong>Observaciones:</strong> {{ $caja->observaciones }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Detalle de Ventas -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Detalle de Ventas</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Factura</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th>Método Pago</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($caja->ventas as $venta)
                            <tr>
                                <td>{{ $venta->numero_factura }}</td>
                                <td>{{ $venta->cliente->nombres }} {{ $venta->cliente->apellidos }}</td>
                                <td>{{ $venta->fecha_venta->format('d/m/Y H:i') }}</td>
                                <td>{{ ucfirst($venta->metodo_pago) }}</td>
                                <td class="text-end">$ {{ number_format($venta->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No hay ventas registradas</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th colspan="4" class="text-end">Total Ventas:</th>
                            <th class="text-end">$ {{ number_format($totalVentas, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="page-break"></div>

    <!-- Detalle de Movimientos -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Detalle de Movimientos</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Concepto</th>
                            <th>Método Pago</th>
                            <th>Monto</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($caja->movimientos as $movimiento)
                            <tr>
                                <td>{{ $movimiento->fecha->format('d/m/Y H:i') }}</td>
                                <td>
                                    <span class="badge {{ $movimiento->tipo === 'ingreso' ? 'bg-success' : ($movimiento->tipo === 'gasto' ? 'bg-danger' : 'bg-warning') }}">
                                        {{ ucfirst($movimiento->tipo) }}
                                    </span>
                                </td>
                                <td>{{ $movimiento->concepto }}</td>
                                <td>{{ ucfirst($movimiento->metodo_pago) }}</td>
                                <td class="text-end">$ {{ number_format($movimiento->monto, 2) }}</td>
                                <td>{{ $movimiento->observaciones ?: 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No hay movimientos registrados</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-success">
                            <th colspan="4" class="text-end">Total Ingresos:</th>
                            <th class="text-end">$ {{ number_format($ingresos->sum('monto'), 2) }}</th>
                            <th></th>
                        </tr>
                        <tr class="table-danger">
                            <th colspan="4" class="text-end">Total Gastos:</th>
                            <th class="text-end">$ {{ number_format($totalGastos, 2) }}</th>
                            <th></th>
                        </tr>
                        <tr class="table-warning">
                            <th colspan="4" class="text-end">Total Pagos:</th>
                            <th class="text-end">$ {{ number_format($totalPagos, 2) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Pie del Reporte -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <p>Este reporte fue generado automáticamente por el sistema.</p>
                </div>
                <div class="col-md-4">
                    <div class="mt-4 text-center">
                        <hr>
                        <p>Firma Responsable</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
