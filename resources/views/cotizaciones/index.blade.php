@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-file-invoice"></i> Cotizaciones
                    </h4>
                    <a href="{{ route('cotizaciones.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Cotización
                    </a>
                </div>

                <div class="card-body">
                    <!-- Filtros -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <form method="GET" action="{{ route('cotizaciones.index') }}" class="row g-3">
                                <div class="col-md-2">
                                    <select name="estado" class="form-select">
                                        <option value="">Todos los estados</option>
                                        <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                        <option value="aprobada" {{ request('estado') == 'aprobada' ? 'selected' : '' }}>Aprobada</option>
                                        <option value="rechazada" {{ request('estado') == 'rechazada' ? 'selected' : '' }}>Rechazada</option>
                                        <option value="vencida" {{ request('estado') == 'vencida' ? 'selected' : '' }}>Vencida</option>
                                        <option value="convertida" {{ request('estado') == 'convertida' ? 'selected' : '' }}>Convertida</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select name="cliente_id" class="form-select">
                                        <option value="">Todos los clientes</option>
                                        @foreach($clientes as $cliente)
                                            <option value="{{ $cliente->id }}" {{ request('cliente_id') == $cliente->id ? 'selected' : '' }}>
                                                {{ $cliente->nombres }} {{ $cliente->apellidos }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="date" name="fecha_desde" class="form-control" value="{{ request('fecha_desde') }}" placeholder="Fecha desde">
                                </div>
                                <div class="col-md-2">
                                    <input type="date" name="fecha_hasta" class="form-control" value="{{ request('fecha_hasta') }}" placeholder="Fecha hasta">
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="fas fa-search"></i> Filtrar
                                    </button>
                                    <a href="{{ route('cotizaciones.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Limpiar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tabla de cotizaciones -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Número</th>
                                    <th>Cliente</th>
                                    <th>Fecha</th>
                                    <th>Vencimiento</th>
                                    <th>Estado</th>
                                    <th>Total</th>
                                    <th>Vendedor</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cotizaciones as $cotizacion)
                                    <tr>
                                        <td>
                                            <strong>{{ $cotizacion->numero_cotizacion }}</strong>
                                        </td>
                                        <td>
                                            {{ $cotizacion->cliente->nombres }} {{ $cotizacion->cliente->apellidos }}
                                        </td>
                                        <td>{{ $cotizacion->fecha_cotizacion->format('d/m/Y') }}</td>
                                        <td>
                                            {{ $cotizacion->fecha_vencimiento->format('d/m/Y') }}
                                            @if($cotizacion->estaVencida())
                                                <span class="badge bg-danger ms-1">Vencida</span>
                                            @endif
                                        </td>
                                        <td>
                                            @switch($cotizacion->estado)
                                                @case('pendiente')
                                                    <span class="badge bg-warning">Pendiente</span>
                                                    @break
                                                @case('aprobada')
                                                    <span class="badge bg-success">Aprobada</span>
                                                    @break
                                                @case('rechazada')
                                                    <span class="badge bg-danger">Rechazada</span>
                                                    @break
                                                @case('vencida')
                                                    <span class="badge bg-secondary">Vencida</span>
                                                    @break
                                                @case('convertida')
                                                    <span class="badge bg-info">Convertida</span>
                                                    @break
                                            @endswitch
                                        </td>
                                        <td>
                                            <strong>${{ number_format($cotizacion->total, 0, ',', '.') }}</strong>
                                        </td>
                                        <td>
                                            {{ $cotizacion->vendedor->name ?? 'N/A' }}
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('cotizaciones.show', $cotizacion) }}" 
                                                   class="btn btn-sm btn-outline-primary" title="Ver">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                @if($cotizacion->estado === 'pendiente')
                                                    <a href="{{ route('cotizaciones.edit', $cotizacion) }}" 
                                                       class="btn btn-sm btn-outline-warning" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endif

                                                @if($cotizacion->estado === 'aprobada')
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-success" 
                                                            onclick="convertirAVenta({{ $cotizacion->id }})"
                                                            title="Convertir a Venta">
                                                        <i class="fas fa-shopping-cart"></i>
                                                    </button>
                                                @endif

                                                <a href="{{ route('cotizaciones.pdf', $cotizacion) }}" 
                                                   class="btn btn-sm btn-outline-info" 
                                                   target="_blank" title="PDF">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>

                                                @if($cotizacion->estado !== 'convertida')
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger" 
                                                            onclick="eliminarCotizacion({{ $cotizacion->id }})"
                                                            title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No hay cotizaciones registradas</p>
                                            <a href="{{ route('cotizaciones.create') }}" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Crear Primera Cotización
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    @if($cotizaciones->hasPages())
                        <div class="d-flex justify-content-center">
                            {{ $cotizaciones->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para cambiar estado -->
<div class="modal fade" id="estadoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cambiar Estado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="estadoForm">
                    <div class="mb-3">
                        <label class="form-label">Nuevo Estado</label>
                        <select name="estado" class="form-select" required>
                            <option value="pendiente">Pendiente</option>
                            <option value="aprobada">Aprobada</option>
                            <option value="rechazada">Rechazada</option>
                            <option value="vencida">Vencida</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarEstado()">Guardar</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let cotizacionIdActual = null;

function cambiarEstado(cotizacionId) {
    cotizacionIdActual = cotizacionId;
    $('#estadoModal').modal('show');
}

function guardarEstado() {
    const estado = $('#estadoForm select[name="estado"]').val();
    
    $.ajax({
        url: `/cotizaciones/${cotizacionIdActual}/cambiar-estado`,
        method: 'POST',
        data: {
            estado: estado,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    title: 'Éxito',
                    text: response.message,
                    icon: 'success'
                }).then(() => {
                    location.reload();
                });
            }
        },
        error: function(xhr) {
            Swal.fire({
                title: 'Error',
                text: 'Error al cambiar el estado',
                icon: 'error'
            });
        }
    });
    
    $('#estadoModal').modal('hide');
}

function convertirAVenta(cotizacionId) {
    Swal.fire({
        title: '¿Convertir a Venta?',
        text: 'Esta acción creará una nueva venta y actualizará el inventario',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, convertir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/cotizaciones/${cotizacionId}/convertir-venta`,
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Éxito',
                            text: `Cotización convertida a venta ${response.numero_factura}`,
                            icon: 'success'
                        }).then(() => {
                            location.reload();
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        title: 'Error',
                        text: xhr.responseJSON?.message || 'Error al convertir la cotización',
                        icon: 'error'
                    });
                }
            });
        }
    });
}

function eliminarCotizacion(cotizacionId) {
    Swal.fire({
        title: '¿Eliminar Cotización?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/cotizaciones/${cotizacionId}`,
                method: 'DELETE',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Eliminado',
                            text: response.message,
                            icon: 'success'
                        }).then(() => {
                            location.reload();
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        title: 'Error',
                        text: xhr.responseJSON?.message || 'Error al eliminar la cotización',
                        icon: 'error'
                    });
                }
            });
        }
    });
}
</script>
@endpush
