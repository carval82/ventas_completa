@extends('layouts.app')

@section('title', 'Abrir Caja')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3 mb-0 text-gray-800">Abrir Nueva Caja</h1>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="{{ route('cajas.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver al Listado
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Datos de Apertura</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('cajas.store') }}" method="POST">
                @csrf
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Fecha y Hora de Apertura</label>
                        <input type="text" class="form-control" value="{{ now()->format('d/m/Y H:i:s') }}" readonly>
                        <small class="text-muted">La fecha y hora se registrarán automáticamente</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Monto de Apertura <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" min="0" class="form-control @error('monto_apertura') is-invalid @enderror" 
                                   name="monto_apertura" value="{{ old('monto_apertura', 0) }}" required>
                        </div>
                        <small class="text-muted">Ingrese el monto inicial con el que abre la caja</small>
                        @error('monto_apertura')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Observaciones</label>
                    <textarea class="form-control @error('observaciones') is-invalid @enderror" 
                              name="observaciones" rows="3">{{ old('observaciones') }}</textarea>
                    <small class="text-muted">Opcional. Agregue cualquier nota relevante sobre la apertura de caja</small>
                    @error('observaciones')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Al abrir una caja, podrá registrar ventas, gastos y pagos asociados a esta caja. 
                    La caja debe cerrarse al final del día o turno.
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-cash-register"></i> Abrir Caja
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
