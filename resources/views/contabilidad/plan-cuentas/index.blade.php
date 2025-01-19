@extends('layouts.app')

@section('title', 'Plan de Cuentas')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list"></i> Plan de Cuentas
                        </h5>
                        <a href="{{ route('plan-cuentas.create') }}" class="btn btn-light">
                            <i class="fas fa-plus"></i> Nueva Cuenta
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Filtros -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <input type="text" 
                                   class="form-control" 
                                   id="searchInput" 
                                   placeholder="Buscar por código o nombre...">
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" id="tipoFilter">
                                <option value="">Todos los tipos</option>
                                <option value="Activo">Activo</option>
                                <option value="Pasivo">Pasivo</option>
                                <option value="Patrimonio">Patrimonio</option>
                                <option value="Ingreso">Ingreso</option>
                                <option value="Gasto">Gasto</option>
                            </select>
                        </div>
                    </div>

                    <!-- Tabla -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Nivel</th>
                                    <th>Cuenta Padre</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cuentas as $cuenta)
                                    <tr>
                                        <td>{{ $cuenta->codigo }}</td>
                                        <td>{{ $cuenta->nombre }}</td>
                                        <td>
                                            <span class="badge bg-{{ 
                                                $cuenta->tipo == 'Activo' ? 'primary' :
                                                ($cuenta->tipo == 'Pasivo' ? 'danger' :
                                                ($cuenta->tipo == 'Patrimonio' ? 'success' :
                                                ($cuenta->tipo == 'Ingreso' ? 'info' : 'warning')))
                                            }}">
                                                {{ $cuenta->tipo }}
                                            </span>
                                        </td>
                                        <td>{{ $cuenta->nivel }}</td>
                                        <td>{{ optional($cuenta->cuentaPadre)->nombre ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $cuenta->estado ? 'success' : 'danger' }}">
                                                {{ $cuenta->estado ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('plan-cuentas.edit', $cuenta) }}" 
                                               class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if(!$cuenta->movimientos()->exists() && !$cuenta->subcuentas()->exists())
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger"
                                                        onclick="confirmarEliminacion('{{ $cuenta->id }}')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-3">
                                            No hay cuentas registradas
                                        </td>
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

<!-- Formulario para eliminar -->
<form id="deleteForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Búsqueda en tiempo real
    $('#searchInput').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $("table tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

    // Filtro por tipo
    $('#tipoFilter').change(function() {
        var value = $(this).val().toLowerCase();
        if (value) {
            $("table tbody tr").filter(function() {
                $(this).toggle($(this).find("td:eq(2)").text().toLowerCase().indexOf(value) > -1)
            });
        } else {
            $("table tbody tr").show();
        }
    });
});

function confirmarEliminacion(id) {
    Swal.fire({
        title: '¿Está seguro?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            var form = $('#deleteForm');
            form.attr('action', '/plan-cuentas/' + id);
            form.submit();
        }
    });
}
</script>
@endpush