@extends('layouts.app')

@section('title', 'Detalles de Caja #' . $caja->id)

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3 mb-0 text-gray-800">
                Caja #{{ $caja->id }} 
                <span class="badge {{ $caja->estado === 'abierta' ? 'bg-success' : 'bg-secondary' }}">
                    {{ $caja->estado === 'abierta' ? 'Abierta' : 'Cerrada' }}
                </span>
            </h1>
        </div>
        <div class="col-md-6 text-md-end">
            <div class="btn-group" role="group">
                <a href="{{ route('cajas.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver al Listado
                </a>
                @if($caja->estado === 'abierta')
                    <a href="{{ route('cajas.edit', $caja) }}" class="btn btn-warning">
                        <i class="fas fa-lock"></i> Cerrar Caja
                    </a>
                    <a href="{{ route('cajas.movimientos.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Nuevo Movimiento
                    </a>
                @endif
                <a href="{{ route('cajas.reporte', $caja) }}" class="btn btn-info">
                    <i class="fas fa-print"></i> Imprimir Reporte
                </a>
            </div>
        </div>
    </div>

    <!-- Resumen de la Caja -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Monto Apertura</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">$ {{ number_format($caja->monto_apertura, 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Ventas</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">$ {{ number_format($totalVentas, 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Total Gastos y Pagos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">$ {{ number_format($totalGastos + $totalPagos, 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hand-holding-usd fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Saldo Actual</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">$ {{ number_format($saldoActual, 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-coins fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Información de la Caja -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Información General</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">ID:</th>
                            <td>{{ $caja->id }}</td>
                        </tr>
                        <tr>
                            <th>Fecha Apertura:</th>
                            <td>{{ $caja->fecha_apertura->format('d/m/Y H:i:s') }}</td>
                        </tr>
                        <tr>
                            <th>Fecha Cierre:</th>
                            <td>{{ $caja->fecha_cierre ? $caja->fecha_cierre->format('d/m/Y H:i:s') : 'No cerrada' }}</td>
                        </tr>
                        <tr>
                            <th>Monto Apertura:</th>
                            <td>$ {{ number_format($caja->monto_apertura, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Monto Cierre:</th>
                            <td>{{ $caja->monto_cierre ? '$ ' . number_format($caja->monto_cierre, 2) : 'No cerrada' }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">Total Ventas:</th>
                            <td>$ {{ number_format($totalVentas, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Total Gastos:</th>
                            <td>$ {{ number_format($totalGastos, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Total Pagos:</th>
                            <td>$ {{ number_format($totalPagos, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Diferencia:</th>
                            <td class="{{ $caja->diferencia < 0 ? 'text-danger' : ($caja->diferencia > 0 ? 'text-success' : '') }}">
                                {{ $caja->diferencia !== null ? '$ ' . number_format($caja->diferencia, 2) : 'No cerrada' }}
                            </td>
                        </tr>
                        <tr>
                            <th>Creado por:</th>
                            <td>{{ $caja->creadoPor->name }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            @if($caja->observaciones)
                <div class="alert alert-info mt-3">
                    <strong>Observaciones:</strong> {{ $caja->observaciones }}
                </div>
            @endif

            <div class="alert alert-primary mt-3">
                <h5 class="alert-heading">Cálculo del saldo de caja:</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Base inicial:</strong> $ {{ number_format($caja->monto_apertura, 2) }}</p>
                        <p><strong>(+) Ventas:</strong> $ {{ number_format($totalVentas, 2) }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>(-) Gastos:</strong> $ {{ number_format($totalGastos, 2) }}</p>
                        <p><strong>(-) Pagos:</strong> $ {{ number_format($totalPagos, 2) }}</p>
                        <p><strong>= Saldo teórico:</strong> $ {{ number_format($saldoActual, 2) }}</p>
                    </div>
                </div>
                
                <hr>
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="text-success">Saldo sin la base (ganancias netas): $ {{ number_format($totalVentas - $totalGastos - $totalPagos, 2) }}</h5>
                    </div>
                </div>
                
                @if($caja->estado === 'cerrada')
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Monto real al cierre:</strong> $ {{ number_format($caja->monto_cierre, 2) }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="{{ $caja->diferencia < 0 ? 'text-danger' : ($caja->diferencia > 0 ? 'text-success' : '') }}">
                            <strong>Diferencia:</strong> $ {{ number_format($caja->diferencia, 2) }}
                            ({{ $caja->diferencia < 0 ? 'Faltante' : ($caja->diferencia > 0 ? 'Sobrante' : 'Sin diferencia') }})
                        </p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Tabs de Movimientos -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <ul class="nav nav-tabs card-header-tabs" id="cajaTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="ventas-tab" data-bs-toggle="tab" data-bs-target="#ventas" type="button" role="tab" aria-controls="ventas" aria-selected="true">
                        Ventas ({{ $caja->ventas->count() }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="ingresos-tab" data-bs-toggle="tab" data-bs-target="#ingresos" type="button" role="tab" aria-controls="ingresos" aria-selected="false">
                        Ingresos ({{ $ingresos->count() }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="gastos-tab" data-bs-toggle="tab" data-bs-target="#gastos" type="button" role="tab" aria-controls="gastos" aria-selected="false">
                        Gastos ({{ $gastos->count() }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="pagos-tab" data-bs-toggle="tab" data-bs-target="#pagos" type="button" role="tab" aria-controls="pagos" aria-selected="false">
                        Pagos ({{ $pagos->count() }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="todos-tab" data-bs-toggle="tab" data-bs-target="#todos" type="button" role="tab" aria-controls="todos" aria-selected="false">
                        Todos los Movimientos ({{ $caja->movimientos->count() }})
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="cajaTabsContent">
                <!-- Tab Ventas -->
                <div class="tab-pane fade show active" id="ventas" role="tabpanel" aria-labelledby="ventas-tab">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Factura</th>
                                    <th>Cliente</th>
                                    <th>Fecha</th>
                                    <th>Método Pago</th>
                                    <th>Total</th>
                                    <th>Acciones</th>
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
                                        <td>
                                            <a href="{{ route('ventas.show', $venta) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No hay ventas registradas</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Tab Ingresos -->
                <div class="tab-pane fade" id="ingresos" role="tabpanel" aria-labelledby="ingresos-tab">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Concepto</th>
                                    <th>Método Pago</th>
                                    <th>Monto</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ingresos as $ingreso)
                                    <tr>
                                        <td>{{ $ingreso->fecha->format('d/m/Y H:i') }}</td>
                                        <td>{{ $ingreso->concepto }}</td>
                                        <td>{{ ucfirst($ingreso->metodo_pago) }}</td>
                                        <td class="text-end">$ {{ number_format($ingreso->monto, 2) }}</td>
                                        <td>{{ $ingreso->observaciones ?: 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No hay ingresos registrados</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Tab Gastos -->
                <div class="tab-pane fade" id="gastos" role="tabpanel" aria-labelledby="gastos-tab">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Concepto</th>
                                    <th>Método Pago</th>
                                    <th>Monto</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($gastos as $gasto)
                                    <tr>
                                        <td>{{ $gasto->fecha->format('d/m/Y H:i') }}</td>
                                        <td>{{ $gasto->concepto }}</td>
                                        <td>{{ ucfirst($gasto->metodo_pago) }}</td>
                                        <td class="text-end">$ {{ number_format($gasto->monto, 2) }}</td>
                                        <td>{{ $gasto->observaciones ?: 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No hay gastos registrados</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Tab Pagos -->
                <div class="tab-pane fade" id="pagos" role="tabpanel" aria-labelledby="pagos-tab">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Concepto</th>
                                    <th>Método Pago</th>
                                    <th>Monto</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pagos as $pago)
                                    <tr>
                                        <td>{{ $pago->fecha->format('d/m/Y H:i') }}</td>
                                        <td>{{ $pago->concepto }}</td>
                                        <td>{{ ucfirst($pago->metodo_pago) }}</td>
                                        <td class="text-end">$ {{ number_format($pago->monto, 2) }}</td>
                                        <td>{{ $pago->observaciones ?: 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No hay pagos registrados</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Tab Todos los Movimientos -->
                <div class="tab-pane fade" id="todos" role="tabpanel" aria-labelledby="todos-tab">
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
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
