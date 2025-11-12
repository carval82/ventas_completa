@extends('layouts.app')

@section('title', 'Comprobantes Contables')

@section('content')
<div class="container-fluid">
    <!-- Filtros -->
    <div class="card mb-3">
        <div class="card-body">
            <form id="filtrosForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Fecha Desde</label>
                    <input type="date" class="form-control" name="fecha_desde" value="{{ request('fecha_desde') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Hasta</label>
                    <input type="date" class="form-control" name="fecha_hasta" value="{{ request('fecha_hasta') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Prefijo</label>
                    <input type="text" class="form-control" name="prefijo" value="{{ request('prefijo') }}" placeholder="Buscar por prefijo">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipo</label>
                    <select class="form-select" name="tipo">
                        <option value="">Todos</option>
                        <option value="Ingreso" {{ request('tipo') == 'Ingreso' ? 'selected' : '' }}>Ingreso</option>
                        <option value="Egreso" {{ request('tipo') == 'Egreso' ? 'selected' : '' }}>Egreso</option>
                        <option value="Diario" {{ request('tipo') == 'Diario' ? 'selected' : '' }}>Diario</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estado</label>
                    <select class="form-select" name="estado">
                        <option value="">Todos</option>
                        <option value="Borrador" {{ request('estado') == 'Borrador' ? 'selected' : '' }}>Borrador</option>
                        <option value="Aprobado" {{ request('estado') == 'Aprobado' ? 'selected' : '' }}>Aprobado</option>
                        <option value="Anulado" {{ request('estado') == 'Anulado' ? 'selected' : '' }}>Anulado</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <a href="{{ route('comprobantes.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Comprobantes -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-file-invoice"></i> Comprobantes Contables
                </h5>
                <a href="{{ route('comprobantes.create') }}" class="btn btn-light">
                    <i class="fas fa-plus"></i> Nuevo Comprobante
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Prefijo</th>
                            <th>Número</th>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th class="text-end">Total</th>
                            <th>Estado</th>
                            <th>Creado por</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($comprobantes as $comprobante)
                            <tr>
                                <td>{{ $comprobante->prefijo }}</td>
                                <td>{{ $comprobante->numero }}</td>
                                <td>{{ $comprobante->fecha->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ 
                                        $comprobante->tipo == 'Ingreso' ? 'success' : 
                                        ($comprobante->tipo == 'Egreso' ? 'danger' : 'info') 
                                    }}">
                                        {{ $comprobante->tipo }}
                                    </span>
                                </td>
                                <td>{{ Str::limit($comprobante->descripcion, 50) }}</td>
                                <td class="text-end">
                                    ${{ number_format($comprobante->total_debito, 2) }}
                                </td>
                                <td>
                                    <span class="badge bg-{{ 
                                        $comprobante->estado == 'Aprobado' ? 'success' : 
                                        ($comprobante->estado == 'Anulado' ? 'danger' : 'warning') 
                                    }}">
                                        {{ $comprobante->estado }}
                                    </span>
                                </td>
                                <td>{{ $comprobante->creadoPor->name }}</td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="{{ route('comprobantes.show', $comprobante) }}" 
                                           class="btn btn-sm btn-info" 
                                           title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        @if($comprobante->estado == 'Borrador')
                                            <form action="{{ route('comprobantes.aprobar', $comprobante) }}" 
                                                  method="POST" 
                                                  class="d-inline">
                                                @csrf
                                                <button type="button" 
                                                        class="btn btn-sm btn-success" 
                                                        onclick="confirmarAprobacion(this)"
                                                        title="Aprobar">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        @endif

                                        @if($comprobante->estado != 'Anulado')
                                            <form action="{{ route('comprobantes.anular', $comprobante) }}" 
                                                  method="POST" 
                                                  class="d-inline">
                                                @csrf
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger" 
                                                        onclick="confirmarAnulacion(this)"
                                                        title="Anular">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            </form>
                                        @endif

                                        <a href="{{ route('comprobantes.imprimir', $comprobante) }}" 
                                           class="btn btn-sm btn-secondary" 
                                           target="_blank"
                                           title="Imprimir">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-3">
                                    No hay comprobantes registrados
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="d-flex justify-content-center mt-3">
                {{ $comprobantes->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmarAprobacion(button) {
    Swal.fire({
        title: '¿Aprobar comprobante?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, aprobar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            button.closest('form').submit();
        }
    });
}

function confirmarAnulacion(button) {
    Swal.fire({
        title: '¿Anular comprobante?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, anular',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            button.closest('form').submit();
        }
    });
}

// Inicializar datepickers y otros componentes
$(document).ready(function() {
    // Enviar formulario al cambiar cualquier filtro
    $('#filtrosForm select').change(function() {
        $('#filtrosForm').submit();
    });
});
</script>
@endpush