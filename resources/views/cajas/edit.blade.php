@extends('layouts.app')

@section('title', 'Cerrar Caja #' . $caja->id)

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3 mb-0 text-gray-800">Cerrar Caja #{{ $caja->id }}</h1>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="{{ route('cajas.show', $caja) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Detalles
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Resumen de la Caja -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Resumen de Caja</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th>Fecha Apertura:</th>
                            <td>{{ $caja->fecha_apertura->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Monto Apertura (Base):</th>
                            <td>$ {{ number_format($caja->monto_apertura, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Total Ventas:</th>
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
                        <tr class="table-primary">
                            <th>Saldo Teórico:</th>
                            <td>$ {{ number_format($saldoTeorico, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="alert alert-info">
                                    <strong>Cálculo del saldo:</strong><br>
                                    Base inicial: $ {{ number_format($caja->monto_apertura, 2) }}<br>
                                    (+) Ventas: $ {{ number_format($totalVentas, 2) }}<br>
                                    (-) Gastos: $ {{ number_format($totalGastos, 2) }}<br>
                                    (-) Pagos: $ {{ number_format($totalPagos, 2) }}<br>
                                    <hr>
                                    <strong>= Saldo teórico: $ {{ number_format($saldoTeorico, 2) }}</strong>
                                    <hr>
                                    <strong class="text-success">Saldo sin la base (ganancias netas): $ {{ number_format($totalVentas - $totalGastos - $totalPagos, 2) }}</strong>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Formulario de Cierre -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Datos de Cierre</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('cajas.update', $caja) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Fecha y Hora de Cierre</label>
                                <input type="text" class="form-control" value="{{ now()->format('d/m/Y H:i:s') }}" readonly>
                                <small class="text-muted">La fecha y hora se registrarán automáticamente</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Monto de Cierre <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0" class="form-control @error('monto_cierre') is-invalid @enderror" 
                                           name="monto_cierre" value="{{ old('monto_cierre', $saldoTeorico) }}" required>
                                </div>
                                <small class="text-muted">Ingrese el monto real con el que cierra la caja</small>
                                @error('monto_cierre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Observaciones</label>
                            <textarea class="form-control @error('observaciones') is-invalid @enderror" 
                                      name="observaciones" rows="3">{{ old('observaciones', $caja->observaciones) }}</textarea>
                            <small class="text-muted">Opcional. Agregue cualquier nota relevante sobre el cierre de caja</small>
                            @error('observaciones')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <strong>¡Atención!</strong> Una vez cerrada la caja, no podrá registrar más movimientos en ella. 
                            Asegúrese de que todos los movimientos del día estén registrados.
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-lock"></i> Cerrar Caja
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
