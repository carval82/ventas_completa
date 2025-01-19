@extends('layouts.app')

@section('title', 'Libro Mayor')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-book-open"></i> Libro Mayor
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('reportes.libro-mayor') }}">
                        <div class="mb-3">
                            <label class="form-label required">Cuenta</label>
                            <select name="cuenta_id" class="form-select select2" required>
                                <option value="">Seleccione una cuenta...</option>
                                @foreach($cuentas as $cuenta)
                                    <option value="{{ $cuenta->id }}">
                                        {{ $cuenta->codigo }} - {{ $cuenta->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">Fecha Desde</label>
                                    <input type="date" name="fecha_desde" 
                                           class="form-control" 
                                           value="{{ now()->startOfMonth()->format('Y-m-d') }}" 
                                           required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">Fecha Hasta</label>
                                    <input type="date" name="fecha_hasta" 
                                           class="form-control" 
                                           value="{{ now()->format('Y-m-d') }}" 
                                           required>
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <a href="{{ route('reportes.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Consultar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });
});
</script>
@endpush