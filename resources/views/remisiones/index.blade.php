@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-truck"></i> Remisiones
                    </h4>
                    <a href="{{ route('remisiones.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Remisión
                    </a>
                </div>

                <div class="card-body">
                    <!-- Filtros -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <form method="GET" action="{{ route('remisiones.index') }}" class="row g-3">
                                <div class="col-md-2">
                                    <select name="estado" class="form-select">
                                        <option value="">Todos los estados</option>
                                        <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                        <option value="en_transito" {{ request('estado') == 'en_transito' ? 'selected' : '' }}>En Tránsito</option>
                                        <option value="entregada" {{ request('estado') == 'entregada' ? 'selected' : '' }}>Entregada</option>
                                        <option value="devuelta" {{ request('estado') == 'devuelta' ? 'selected' : '' }}>Devuelta</option>
                                        <option value="cancelada" {{ request('estado') == 'cancelada' ? 'selected' : '' }}>Cancelada</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select name="tipo" class="form-select">
                                        <option value="">Todos los tipos</option>
                                        <option value="venta" {{ request('tipo') == 'venta' ? 'selected' : '' }}>Venta</option>
                                        <option value="traslado" {{ request('tipo') == 'traslado' ? 'selected' : '' }}>Traslado</option>
                                        <option value="devolucion" {{ request('tipo') == 'devolucion' ? 'selected' : '' }}>Devolución</option>
                                        <option value="muestra" {{ request('tipo') == 'muestra' ? 'selected' : '' }}>Muestra</option>
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
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="fas fa-search"></i> Filtrar
                                    </button>
                                    <a href="{{ route('remisiones.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Limpiar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Estadísticas rápidas -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h5>{{ $remisiones->where('estado', 'pendiente')->count() }}</h5>
                                    <small>Pendientes</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h5>{{ $remisiones->where('estado', 'en_transito')->count() }}</h5>
                                    <small>En Tránsito</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h5>{{ $remisiones->where('estado', 'entregada')->count() }}</h5>
                                    <small>Entregadas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h5>{{ $remisiones->where('estado', 'cancelada')->count() }}</h5>
                                    <small>Canceladas</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de remisiones -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Número</th>
                                    <th>Cliente</th>
                                    <th>Tipo</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Total</th>
                                    <th>Transportador</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($remisiones as $remision)
                                    <tr>
                                        <td>
                                            <strong>{{ $remision->numero_remision }}</strong>
                                            @if($remision->venta_id)
                                                <br><small class="text-muted">Venta: {{ $remision->venta->numero_factura ?? 'N/A' }}</small>
                                            @endif
                                            @if($remision->cotizacion_id)
                                                <br><small class="text-muted">Cotización: {{ $remision->cotizacion->numero_cotizacion ?? 'N/A' }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $remision->cliente->nombres }} {{ $remision->cliente->apellidos }}
                                        </td>
                                        <td>
                                            @switch($remision->tipo)
                                                @case('venta')
                                                    <span class="badge bg-primary">Venta</span>
                                                    @break
                                                @case('traslado')
                                                    <span class="badge bg-info">Traslado</span>
                                                    @break
                                                @case('devolucion')
                                                    <span class="badge bg-warning">Devolución</span>
                                                    @break
                                                @case('muestra')
                                                    <span class="badge bg-secondary">Muestra</span>
                                                    @break
                                            @endswitch
                                        </td>
                                        <td>
                                            {{ $remision->fecha_remision->format('d/m/Y') }}
                                            @if($remision->fecha_entrega)
                                                <br><small class="text-muted">Entrega: {{ $remision->fecha_entrega->format('d/m/Y') }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @switch($remision->estado)
                                                @case('pendiente')
                                                    <span class="badge bg-warning">Pendiente</span>
                                                    @break
                                                @case('en_transito')
                                                    <span class="badge bg-info">En Tránsito</span>
                                                    @break
                                                @case('entregada')
                                                    <span class="badge bg-success">Entregada</span>
                                                    @break
                                                @case('devuelta')
                                                    <span class="badge bg-danger">Devuelta</span>
                                                    @break
                                                @case('cancelada')
                                                    <span class="badge bg-secondary">Cancelada</span>
                                                    @break
                                            @endswitch
                                        </td>
                                        <td>
                                            <strong>${{ number_format($remision->total, 0, ',', '.') }}</strong>
                                        </td>
                                        <td>
                                            {{ $remision->transportador ?? 'N/A' }}
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('remisiones.show', $remision) }}" 
                                                   class="btn btn-sm btn-outline-primary" title="Ver">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                @if(in_array($remision->estado, ['pendiente', 'en_transito']))
                                                    <a href="{{ route('remisiones.edit', $remision) }}" 
                                                       class="btn btn-sm btn-outline-warning" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endif

                                                @if($remision->estado === 'pendiente')
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-info" 
                                                            onclick="cambiarEstado({{ $remision->id }}, 'en_transito')"
                                                            title="Marcar En Tránsito">
                                                        <i class="fas fa-shipping-fast"></i>
                                                    </button>
                                                @endif

                                                @if($remision->estado === 'en_transito')
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-success" 
                                                            onclick="registrarEntrega({{ $remision->id }})"
                                                            title="Registrar Entrega">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                @endif

                                                <a href="{{ route('remisiones.pdf', $remision) }}" 
                                                   class="btn btn-sm btn-outline-info" 
                                                   target="_blank" title="PDF">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>

                                                @if($remision->estado !== 'entregada')
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger" 
                                                            onclick="eliminarRemision({{ $remision->id }})"
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
                                            <i class="fas fa-truck fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No hay remisiones registradas</p>
                                            <a href="{{ route('remisiones.create') }}" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Crear Primera Remisión
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    @if($remisiones->hasPages())
                        <div class="d-flex justify-content-center">
                            {{ $remisiones->appends(request()->query())->links() }}
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
                            <option value="en_transito">En Tránsito</option>
                            <option value="entregada">Entregada</option>
                            <option value="devuelta">Devuelta</option>
                            <option value="cancelada">Cancelada</option>
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

<!-- Modal para registrar entrega -->
<div class="modal fade" id="entregaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Entrega</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="entregaContent">
                    <!-- Se carga dinámicamente -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarEntrega()">Registrar Entrega</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let remisionIdActual = null;

function cambiarEstado(remisionId, nuevoEstado = null) {
    remisionIdActual = remisionId;
    
    if (nuevoEstado) {
        // Cambio directo de estado
        $.ajax({
            url: `/remisiones/${remisionId}/cambiar-estado`,
            method: 'POST',
            data: {
                estado: nuevoEstado,
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
                    text: xhr.responseJSON?.message || 'Error al cambiar el estado',
                    icon: 'error'
                });
            }
        });
    } else {
        // Mostrar modal para seleccionar estado
        $('#estadoModal').modal('show');
    }
}

function guardarEstado() {
    const estado = $('#estadoForm select[name="estado"]').val();
    
    $.ajax({
        url: `/remisiones/${remisionIdActual}/cambiar-estado`,
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

function registrarEntrega(remisionId) {
    remisionIdActual = remisionId;
    
    // Cargar detalles de la remisión para registrar entrega
    $.get(`/remisiones/${remisionId}`)
        .done(function(data) {
            // Aquí cargarías el contenido del modal con los productos
            // Por simplicidad, mostraremos un modal básico
            $('#entregaContent').html(`
                <p>Registrar entrega para remisión ${remisionId}</p>
                <div class="alert alert-info">
                    Esta funcionalidad se completará con los detalles específicos de cada producto.
                </div>
            `);
            $('#entregaModal').modal('show');
        })
        .fail(function() {
            Swal.fire({
                title: 'Error',
                text: 'Error al cargar los detalles de la remisión',
                icon: 'error'
            });
        });
}

function guardarEntrega() {
    // Implementar lógica de guardado de entrega
    cambiarEstado(remisionIdActual, 'entregada');
    $('#entregaModal').modal('hide');
}

function eliminarRemision(remisionId) {
    Swal.fire({
        title: '¿Eliminar Remisión?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/remisiones/${remisionId}`,
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
                        text: xhr.responseJSON?.message || 'Error al eliminar la remisión',
                        icon: 'error'
                    });
                }
            });
        }
    });
}
</script>
@endpush
