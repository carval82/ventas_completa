@extends('layouts.app')

@section('title', 'Reportes Contables')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Balance General -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-balance-scale"></i> Balance General
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('reportes.balance-general') }}" method="GET" target="_blank">
                        <div class="mb-3">
                            <label class="form-label required">Fecha de Corte</label>
                            <input type="date" 
                                   name="fecha_corte" 
                                   class="form-control" 
                                   value="{{ date('Y-m-d') }}" 
                                   required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nivel de Detalle</label>
                            <select name="nivel" class="form-select">
                                <option value="1">Nivel 1 - Mayor</option>
                                <option value="2">Nivel 2 - Subcuentas</option>
                                <option value="3">Nivel 3 - Auxiliares</option>
                            </select>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-file-pdf"></i> Generar Reporte
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Estado de Resultados -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line"></i> Estado de Resultados
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('reportes.estado-resultados') }}" method="GET" target="_blank">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">Fecha Desde</label>
                                    <input type="date" 
                                           name="fecha_desde" 
                                           class="form-control" 
                                           value="{{ date('Y-m-01') }}" 
                                           required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">Fecha Hasta</label>
                                    <input type="date" 
                                           name="fecha_hasta" 
                                           class="form-control" 
                                           value="{{ date('Y-m-d') }}" 
                                           required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nivel de Detalle</label>
                            <select name="nivel" class="form-select">
                                <option value="1">Nivel 1 - Mayor</option>
                                <option value="2">Nivel 2 - Subcuentas</option>
                                <option value="3">Nivel 3 - Auxiliares</option>
                            </select>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-file-pdf"></i> Generar Reporte
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Libro Diario -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-book"></i> Libro Diario
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('reportes.libro-diario') }}" method="GET" target="_blank">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">Fecha Desde</label>
                                    <input type="date" 
                                           name="fecha_desde" 
                                           class="form-control" 
                                           value="{{ date('Y-m-01') }}" 
                                           required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">Fecha Hasta</label>
                                    <input type="date" 
                                           name="fecha_hasta" 
                                           class="form-control" 
                                           value="{{ date('Y-m-d') }}" 
                                           required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       name="incluir_anulados" 
                                       id="incluirAnulados">
                                <label class="form-check-label" for="incluirAnulados">
                                    Incluir Comprobantes Anulados
                                </label>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-info">
                                <i class="fas fa-file-pdf"></i> Generar Reporte
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Libro Mayor -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">
                        <i class="fas fa-book-open"></i> Libro Mayor
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('reportes.libro-mayor') }}" method="GET" target="_blank">
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
                                    <input type="date" 
                                           name="fecha_desde" 
                                           class="form-control" 
                                           value="{{ date('Y-m-01') }}" 
                                           required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">Fecha Hasta</label>
                                    <input type="date" 
                                           name="fecha_hasta" 
                                           class="form-control" 
                                           value="{{ date('Y-m-d') }}" 
                                           required>
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-file-pdf"></i> Generar Reporte
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
    // Inicializar Select2
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Validar fechas
    $('form').submit(function(e) {
        const fechaDesde = $(this).find('input[name="fecha_desde"]').val();
        const fechaHasta = $(this).find('input[name="fecha_hasta"]').val();

        if (fechaDesde && fechaHasta && fechaDesde > fechaHasta) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'La fecha desde no puede ser mayor a la fecha hasta'
            });
        }
    });
});
</script>
@endpush