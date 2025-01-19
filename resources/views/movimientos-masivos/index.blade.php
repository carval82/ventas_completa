@extends('layouts.app')

@section('title', 'Movimientos Masivos')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Movimientos Masivos</h5>
                <a href="{{ route('movimientos-masivos.create') }}" class="btn btn-light">
                    <i class="fas fa-plus"></i> Nuevo Movimiento
                </a>
            </div>
        </div>

        <div class="card-body">
            <!-- Filtros -->
            <form action="{{ route('movimientos-masivos.index') }}" method="GET" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <input type="text" name="documento" class="form-control" 
                               placeholder="Buscar por N° documento"
                               value="{{ request('documento') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="estado" class="form-control">
                            <option value="">Todos los estados</option>
                            <option value="borrador" {{ request('estado') == 'borrador' ? 'selected' : '' }}>Borrador</option>
                            <option value="procesado" {{ request('estado') == 'procesado' ? 'selected' : '' }}>Procesado</option>
                            <option value="anulado" {{ request('estado') == 'anulado' ? 'selected' : '' }}>Anulado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <a href="{{ route('movimientos-masivos.index') }}" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Limpiar
                        </a>
                    </div>
                </div>
            </form>

            <!-- Tabla -->
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>N° Documento</th>
                            <th>Fecha</th>
                            <th>Ubicación Destino</th>
                            <th>Motivo</th>
                            <th>Estado</th>
                            <th>Usuario</th>
                            <th>Productos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movimientos as $movimiento)
                            <tr>
                                <td>{{ $movimiento->numero_documento }}</td>
                                <td>{{ $movimiento->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $movimiento->ubicacionDestino->nombre }}</td>
                                <td>{{ $movimiento->motivo }}</td>
                                <td>
                                    @switch($movimiento->estado)
                                        @case('borrador')
                                            <span class="badge bg-warning">Borrador</span>
                                            @break
                                        @case('procesado')
                                            <span class="badge bg-success">Procesado</span>
                                            @break
                                        @case('anulado')
                                            <span class="badge bg-danger">Anulado</span>
                                            @break
                                    @endswitch
                                </td>
                                <td>{{ $movimiento->usuario->name }}</td>
                                <td>
                                    @if($movimiento->productos)
                                        {{ $movimiento->productos }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('movimientos-masivos.show', $movimiento) }}" 
                                           class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($movimiento->estado === 'borrador')
                                            <button type="button" 
                                                    onclick="confirmarProcesar({{ $movimiento->id }})"
                                                    class="btn btn-sm btn-success">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button"
                                                    onclick="confirmarAnular({{ $movimiento->id }})"
                                                    class="btn btn-sm btn-danger">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No hay movimientos registrados</td>
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

<!-- Formularios para procesar/anular -->
<form id="procesar-form" method="POST" style="display: none;">
    @csrf
    @method('PUT')
</form>
<form id="anular-form" method="POST" style="display: none;">
    @csrf
    @method('PUT')
</form>
@endsection

@push('scripts')
<script>
function confirmarProcesar(id) {
    Swal.fire({
        title: '¿Está seguro?',
        text: "¿Desea procesar este movimiento?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, procesar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('procesar-form');
            form.action = `/movimientos-masivos/${id}/procesar`;
            form.submit();
        }
    });
}

function confirmarAnular(id) {
    Swal.fire({
        title: '¿Está seguro?',
        text: "¿Desea anular este movimiento?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, anular',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('anular-form');
            form.action = `/movimientos-masivos/${id}/anular`;
            form.submit();
        }
    });
}
</script>
@endpush