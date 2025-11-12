@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-truck"></i> Remisión {{ $remision->numero_remision }}
                        <span class="badge ms-2 
                            @switch($remision->estado)
                                @case('pendiente') bg-warning @break
                                @case('en_transito') bg-info @break
                                @case('entregada') bg-success @break
                                @case('devuelta') bg-danger @break
                                @case('cancelada') bg-secondary @break
                            @endswitch">
                            {{ str_replace('_', ' ', ucfirst($remision->estado)) }}
                        </span>
                    </h4>
                    <div>
                        @if($remision->estado === 'pendiente')
                            <button type="button" class="btn btn-info me-2" onclick="cambiarEstado('en_transito')">
                                <i class="fas fa-shipping-fast"></i> Marcar En Tránsito
                            </button>
                            <a href="{{ route('remisiones.edit', $remision->id) }}" class="btn btn-primary me-2">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        @endif

                        @if($remision->estado === 'en_transito')
                            <button type="button" class="btn btn-success me-2" onclick="registrarEntrega()">
                                <i class="fas fa-check"></i> Registrar Entrega
                            </button>
                        @endif

                        @if(in_array($remision->estado, ['pendiente', 'en_transito']))
                            <button type="button" class="btn btn-warning me-2" onclick="cambiarEstado('devuelta')">
                                <i class="fas fa-undo"></i> Marcar Devuelta
                            </button>
                        @endif

                        <a href="{{ route('remisiones.pdf', $remision->id) }}" class="btn btn-info me-2" target="_blank">
                            <i class="fas fa-file-pdf"></i> PDF
                        </a>
                        <a href="{{ route('remisiones.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Información General -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Información General</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <p><strong>Número:</strong> {{ $remision->numero_remision }}</p>
                                            <p><strong>Fecha:</strong> {{ $remision->fecha_remision->format('d/m/Y') }}</p>
                                            <p><strong>Tipo:</strong> 
                                                <span class="badge 
                                                    @switch($remision->tipo)
                                                        @case('venta') bg-primary @break
                                                        @case('traslado') bg-info @break
                                                        @case('devolucion') bg-warning @break
                                                        @case('muestra') bg-secondary @break
                                                    @endswitch">
                                                    {{ ucfirst($remision->tipo) }}
                                                </span>
                                            </p>
                                        </div>
                                        <div class="col-sm-6">
                                            @if($remision->fecha_entrega)
                                                <p><strong>Fecha Entrega:</strong> {{ $remision->fecha_entrega->format('d/m/Y') }}</p>
                                            @endif
                                            <p><strong>Vendedor:</strong> {{ $remision->vendedor->name ?? 'N/A' }}</p>
                                            @if($remision->venta_id)
                                                <p><strong>Venta ID:</strong> {{ $remision->venta_id }}</p>
                                            @endif
                                            @if($remision->cotizacion_id)
                                                <p><strong>Cotización:</strong> {{ $remision->cotizacion->numero_cotizacion ?? 'N/A' }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-user"></i> Cliente</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Nombre:</strong> {{ $remision->cliente->nombres }} {{ $remision->cliente->apellidos }}</p>
                                    <p><strong>Documento:</strong> {{ $remision->cliente->cedula }}</p>
                                    <p><strong>Email:</strong> {{ $remision->cliente->email ?? 'N/A' }}</p>
                                    <p><strong>Teléfono:</strong> {{ $remision->cliente->telefono ?? 'N/A' }}</p>
                                    <p><strong>Dirección:</strong> {{ $remision->cliente->direccion ?? 'N/A' }}</p>
                                    @if($remision->direccion_entrega)
                                        <p><strong>Dir. Entrega:</strong> {{ $remision->direccion_entrega }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información de Transporte -->
                    @if($remision->transportador || $remision->vehiculo || $remision->conductor)
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="card bg-info bg-opacity-10">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="fas fa-truck"></i> Información de Transporte</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                @if($remision->transportador)
                                                    <p><strong>Transportador:</strong> {{ $remision->transportador }}</p>
                                                @endif
                                            </div>
                                            <div class="col-md-3">
                                                @if($remision->vehiculo)
                                                    <p><strong>Vehículo:</strong> {{ $remision->vehiculo }}</p>
                                                @endif
                                            </div>
                                            <div class="col-md-3">
                                                @if($remision->conductor)
                                                    <p><strong>Conductor:</strong> {{ $remision->conductor }}</p>
                                                @endif
                                            </div>
                                            <div class="col-md-3">
                                                @if($remision->cedula_conductor)
                                                    <p><strong>Cédula:</strong> {{ $remision->cedula_conductor }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Productos -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-boxes"></i> Productos</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Código</th>
                                            <th>Producto</th>
                                            <th class="text-center">Cantidad</th>
                                            <th class="text-center">Unidad</th>
                                            <th class="text-center">Entregada</th>
                                            <th class="text-center">Pendiente</th>
                                            @if($remision->total > 0)
                                                <th class="text-end">Precio Unit.</th>
                                                <th class="text-end">Subtotal</th>
                                                <th class="text-end">Total</th>
                                            @endif
                                            <th class="text-center">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($remision->detalles as $detalle)
                                            <tr>
                                                <td>{{ $detalle->producto->codigo ?? 'N/A' }}</td>
                                                <td>
                                                    <strong>{{ $detalle->producto->nombre }}</strong>
                                                    @if($detalle->observaciones)
                                                        <br><small class="text-muted">{{ $detalle->observaciones }}</small>
                                                    @endif
                                                </td>
                                                <td class="text-center">{{ number_format($detalle->cantidad, 3) }}</td>
                                                <td class="text-center">{{ $detalle->unidad_medida }}</td>
                                                <td class="text-center">
                                                    <span class="badge bg-success">{{ number_format($detalle->cantidad_entregada, 3) }}</span>
                                                </td>
                                                <td class="text-center">
                                                    @php $pendiente = $detalle->cantidadPendiente() @endphp
                                                    <span class="badge {{ $pendiente > 0 ? 'bg-warning' : 'bg-success' }}">
                                                        {{ number_format($pendiente, 3) }}
                                                    </span>
                                                </td>
                                                @if($remision->total > 0)
                                                    <td class="text-end">${{ number_format($detalle->precio_unitario, 0, ',', '.') }}</td>
                                                    <td class="text-end">${{ number_format($detalle->subtotal, 0, ',', '.') }}</td>
                                                    <td class="text-end"><strong>${{ number_format($detalle->total, 0, ',', '.') }}</strong></td>
                                                @endif
                                                <td class="text-center">
                                                    @if($detalle->cantidadPendiente() == 0)
                                                        <span class="badge bg-success">Completo</span>
                                                    @else
                                                        <span class="badge bg-warning">Pendiente</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Totales -->
                    @if($remision->total > 0)
                        <div class="row mt-4">
                            <div class="col-md-8"></div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="fas fa-calculator"></i> Totales</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <span>Subtotal:</span>
                                            <span>${{ number_format($remision->subtotal, 0, ',', '.') }}</span>
                                        </div>
                                        @if($remision->descuento > 0)
                                            <div class="d-flex justify-content-between">
                                                <span>Descuento:</span>
                                                <span class="text-danger">-${{ number_format($remision->descuento, 0, ',', '.') }}</span>
                                            </div>
                                        @endif
                                        @if($remision->impuestos > 0)
                                            <div class="d-flex justify-content-between">
                                                <span>IVA:</span>
                                                <span>${{ number_format($remision->impuestos, 0, ',', '.') }}</span>
                                            </div>
                                        @endif
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <strong>Total:</strong>
                                            <strong class="text-primary">${{ number_format($remision->total, 0, ',', '.') }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Observaciones -->
                    @if($remision->observaciones)
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="fas fa-sticky-note"></i> Observaciones</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-0">{{ $remision->observaciones }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Resumen de Entrega -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-chart-pie"></i> Resumen de Entrega</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-3">
                                            <div class="card bg-primary text-white">
                                                <div class="card-body">
                                                    <h5>{{ $remision->detalles->count() }}</h5>
                                                    <small>Total Productos</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-success text-white">
                                                <div class="card-body">
                                                    <h5>{{ $remision->detalles->where('cantidad_entregada', '>', 0)->count() }}</h5>
                                                    <small>Con Entregas</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-warning text-white">
                                                <div class="card-body">
                                                    <h5>{{ $remision->detalles->filter(function($d) { return $d->cantidadPendiente() > 0; })->count() }}</h5>
                                                    <small>Pendientes</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card {{ $remision->estaCompletamenteEntregada() ? 'bg-success' : 'bg-secondary' }} text-white">
                                                <div class="card-body">
                                                    <h5>{{ $remision->estaCompletamenteEntregada() ? '100%' : '0%' }}</h5>
                                                    <small>Completado</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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
                    <div class="mb-3">
                        <label class="form-label">Observaciones (opcional)</label>
                        <textarea name="observaciones" class="form-control" rows="3"></textarea>
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
function cambiarEstado(nuevoEstado = null) {
    if (nuevoEstado) {
        // Cambio directo de estado
        Swal.fire({
            title: '¿Cambiar Estado?',
            text: `¿Está seguro de cambiar el estado a "${nuevoEstado.replace('_', ' ')}"?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/remisiones/{{ $remision->id }}/cambiar-estado`,
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
            }
        });
    } else {
        // Mostrar modal para seleccionar estado
        $('#estadoModal').modal('show');
    }
}

function guardarEstado() {
    const estado = $('#estadoForm select[name="estado"]').val();
    const observaciones = $('#estadoForm textarea[name="observaciones"]').val();
    
    $.ajax({
        url: `/remisiones/{{ $remision->id }}/cambiar-estado`,
        method: 'POST',
        data: {
            estado: estado,
            observaciones: observaciones,
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

function registrarEntrega() {
    Swal.fire({
        title: 'Registrar Entrega',
        text: '¿Confirmar que todos los productos han sido entregados?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, entregar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/remisiones/{{ $remision->id }}/registrar-entrega`,
                method: 'POST',
                data: {
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
                        text: xhr.responseJSON?.message || 'Error al registrar la entrega',
                        icon: 'error'
                    });
                }
            });
        }
    });
}
</script>
@endpush
