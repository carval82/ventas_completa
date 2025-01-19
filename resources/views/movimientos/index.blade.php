@extends('layouts.app')

@section('title', 'Movimientos Internos')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" rel="stylesheet">
<style>
    .badge-entrada { background-color: #28a745; color: white; }
    .badge-salida { background-color: #dc3545; color: white; }
    .badge-traslado { background-color: #17a2b8; color: white; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Movimientos Internos</h5>
                <div>
                    <a href="{{ route('movimientos.create') }}" class="btn btn-light">
                        <i class="fas fa-plus"></i> Nuevo Movimiento
                    </a>
                    <div class="btn-group">
                        <button type="button" class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-file-alt"></i> Reportes
                        </button>
                        <div class="dropdown-menu">
                            <a href="{{ route('movimientos.reporte-stock') }}" class="dropdown-item">
                                <i class="fas fa-boxes"></i> Stock por Ubicación
                            </a>
                            <a href="{{ route('movimientos.stock-bajo') }}" class="dropdown-item">
                                <i class="fas fa-exclamation-triangle"></i> Alerta Stock Bajo
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Filtros -->
            <form action="{{ route('movimientos.index') }}" method="GET" class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Rango de Fechas</label>
                        <input type="text" class="form-control" id="fecha_rango" name="fecha_rango">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tipo de Movimiento</label>
                        <select class="form-control" name="tipo">
                            <option value="">Todos</option>
                            <option value="entrada" {{ request('tipo') == 'entrada' ? 'selected' : '' }}>Entrada</option>
                            <option value="salida" {{ request('tipo') == 'salida' ? 'selected' : '' }}>Salida</option>
                            <option value="traslado" {{ request('tipo') == 'traslado' ? 'selected' : '' }}>Traslado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <a href="{{ route('movimientos.index') }}" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Limpiar
                        </a>
                    </div>
                </div>
            </form>

            <!-- Tabla de Movimientos -->
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Origen</th>
                            <th>Destino</th>
                            <th>Motivo</th>
                            <th>Usuario</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movimientos as $movimiento)
                            <tr>
                                <td>{{ $movimiento->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <span class="badge badge-{{ $movimiento->tipo_movimiento }}">
                                        {{ ucfirst($movimiento->tipo_movimiento) }}
                                    </span>
                                </td>
                                <td>{{ $movimiento->producto->nombre }}</td>
                                <td>{{ $movimiento->cantidad }}</td>
                                <td>{{ $movimiento->ubicacionOrigen->nombre ?? 'N/A' }}</td>
                                <td>{{ $movimiento->ubicacionDestino->nombre ?? 'N/A' }}</td>
                                <td>{{ ucfirst($movimiento->motivo) }}</td>
                                <td>{{ $movimiento->usuario->name }}</td>
                                <td>
                                    <a href="{{ route('movimientos.show', $movimiento) }}" 
                                       class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">No hay movimientos registrados</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="mt-4">
                {{ $movimientos->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
$(document).ready(function() {
    $('#fecha_rango').daterangepicker({
        locale: {
            format: 'DD/MM/YYYY',
            applyLabel: 'Aplicar',
            cancelLabel: 'Cancelar',
            fromLabel: 'Desde',
            toLabel: 'Hasta'
        },
        autoUpdateInput: false
    });

    $('#fecha_rango').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
    });

    $('#fecha_rango').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
    });
});
</script>
@endpush