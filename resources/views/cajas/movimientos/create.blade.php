@extends('layouts.app')

@section('title', 'Registrar Movimiento de Caja')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3 mb-0 text-gray-800">Registrar Movimiento de Caja</h1>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="{{ route('cajas.show', $caja) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Caja #{{ $caja->id }}
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Resumen de la Caja -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Información de Caja #{{ $caja->id }}</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <span class="badge bg-success">Abierta</span>
                        <p class="mt-2 mb-0"><strong>Fecha Apertura:</strong> {{ $caja->fecha_apertura->format('d/m/Y H:i') }}</p>
                    </div>
                    
                    <div class="alert alert-info">
                        <p class="mb-0"><strong>Saldo Actual:</strong> ${{ number_format($caja->monto_apertura + $caja->calcularTotalVentas() - $caja->calcularTotalGastos() - $caja->calcularTotalPagos(), 2) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de Movimiento -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Datos del Movimiento</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('cajas.movimientos.store') }}" method="POST">
                        @csrf
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Tipo de Movimiento <span class="text-danger">*</span></label>
                                <select class="form-select @error('tipo') is-invalid @enderror" name="tipo" required>
                                    <option value="">Seleccione un tipo</option>
                                    <option value="ingreso" {{ old('tipo') == 'ingreso' ? 'selected' : '' }}>Ingreso</option>
                                    <option value="gasto" {{ old('tipo') == 'gasto' ? 'selected' : '' }}>Gasto</option>
                                    <option value="pago" {{ old('tipo') == 'pago' ? 'selected' : '' }}>Pago a Terceros</option>
                                </select>
                                @error('tipo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Método de Pago <span class="text-danger">*</span></label>
                                <select class="form-select @error('metodo_pago') is-invalid @enderror" name="metodo_pago" required>
                                    <option value="">Seleccione un método</option>
                                    <option value="efectivo" {{ old('metodo_pago') == 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                                    <option value="transferencia" {{ old('metodo_pago') == 'transferencia' ? 'selected' : '' }}>Transferencia</option>
                                    <option value="tarjeta" {{ old('metodo_pago') == 'tarjeta' ? 'selected' : '' }}>Tarjeta</option>
                                    <option value="cheque" {{ old('metodo_pago') == 'cheque' ? 'selected' : '' }}>Cheque</option>
                                    <option value="otro" {{ old('metodo_pago') == 'otro' ? 'selected' : '' }}>Otro</option>
                                </select>
                                @error('metodo_pago')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Concepto <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('concepto') is-invalid @enderror" 
                                       name="concepto" value="{{ old('concepto') }}" required>
                                <small class="text-muted">Ej: Pago de servicios, Compra de insumos, etc.</small>
                                @error('concepto')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Monto <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0.01" class="form-control @error('monto') is-invalid @enderror" 
                                           name="monto" value="{{ old('monto') }}" required>
                                </div>
                                @error('monto')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Observaciones</label>
                            <textarea class="form-control @error('observaciones') is-invalid @enderror" 
                                      name="observaciones" rows="3">{{ old('observaciones') }}</textarea>
                            <small class="text-muted">Opcional. Agregue detalles adicionales sobre este movimiento</small>
                            @error('observaciones')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            Los movimientos quedarán registrados en la caja actual y afectarán el saldo disponible.
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Registrar Movimiento
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
