@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-file-invoice"></i> Cotización {{ $cotizacion->numero_cotizacion }}
                    </h4>
                    <div>
                        @if($cotizacion->estado === 'pendiente')
                            <button type="button" class="btn btn-warning me-2" onclick="cambiarEstado('aprobada')">
                                <i class="fas fa-check"></i> Aprobar
                            </button>
                            <button type="button" class="btn btn-danger me-2" onclick="cambiarEstado('rechazada')">
                                <i class="fas fa-times"></i> Rechazar
                            </button>
                            <a href="{{ route('cotizaciones.edit', $cotizacion) }}" class="btn btn-primary me-2">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        @endif

                        @if($cotizacion->estado === 'aprobada')
                            <button type="button" class="btn btn-success me-2" onclick="convertirAVenta()">
                                <i class="fas fa-shopping-cart"></i> Convertir a Venta
                            </button>
                        @endif

                        <a href="{{ route('cotizaciones.pdf', $cotizacion) }}" class="btn btn-info me-2" target="_blank">
                            <i class="fas fa-file-pdf"></i> PDF
                        </a>
                        <a href="{{ route('cotizaciones.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Información General -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Información del Cliente</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td><strong>Nombre:</strong></td>
                                            <td>{{ $cotizacion->cliente->nombres }} {{ $cotizacion->cliente->apellidos }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Documento:</strong></td>
                                            <td>{{ $cotizacion->cliente->cedula }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Email:</strong></td>
                                            <td>{{ $cotizacion->cliente->email ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Teléfono:</strong></td>
                                            <td>{{ $cotizacion->cliente->telefono ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Dirección:</strong></td>
                                            <td>{{ $cotizacion->cliente->direccion ?? 'N/A' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Información de la Cotización</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td><strong>Número:</strong></td>
                                            <td>{{ $cotizacion->numero_cotizacion }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Fecha:</strong></td>
                                            <td>{{ $cotizacion->fecha_cotizacion->format('d/m/Y') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Vencimiento:</strong></td>
                                            <td>
                                                {{ $cotizacion->fecha_vencimiento->format('d/m/Y') }}
                                                @if($cotizacion->estaVencida())
                                                    <span class="badge bg-danger ms-1">Vencida</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Estado:</strong></td>
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
                                        </tr>
                                        <tr>
                                            <td><strong>Vendedor:</strong></td>
                                            <td>{{ $cotizacion->vendedor->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Forma de Pago:</strong></td>
                                            <td>{{ ucfirst($cotizacion->forma_pago ?? 'N/A') }}</td>
                                        </tr>
                                        @if($cotizacion->venta_id)
                                            <tr>
                                                <td><strong>Venta Generada:</strong></td>
                                                <td>
                                                    <a href="{{ route('ventas.show', $cotizacion->venta_id) }}" class="btn btn-sm btn-outline-primary">
                                                        Ver Venta
                                                    </a>
                                                </td>
                                            </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Productos -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Productos Cotizados</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Producto</th>
                                            <th class="text-center">Cantidad</th>
                                            <th class="text-center">Unidad</th>
                                            <th class="text-end">Precio Unit.</th>
                                            <th class="text-center">Desc. %</th>
                                            <th class="text-end">Subtotal</th>
                                            @if($cotizacion->impuestos > 0)
                                                <th class="text-center">IVA %</th>
                                                <th class="text-end">IVA</th>
                                            @endif
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($cotizacion->detalles as $detalle)
                                            <tr>
                                                <td>
                                                    <strong>{{ $detalle->producto->nombre }}</strong>
                                                    @if($detalle->observaciones)
                                                        <br><small class="text-muted">{{ $detalle->observaciones }}</small>
                                                    @endif
                                                </td>
                                                <td class="text-center">{{ number_format($detalle->cantidad, 3) }}</td>
                                                <td class="text-center">{{ $detalle->unidad_medida }}</td>
                                                <td class="text-end">${{ number_format($detalle->precio_unitario, 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format($detalle->descuento_porcentaje, 2) }}%</td>
                                                <td class="text-end">${{ number_format($detalle->subtotal, 0, ',', '.') }}</td>
                                                @if($cotizacion->impuestos > 0)
                                                    <td class="text-center">{{ number_format($detalle->impuesto_porcentaje, 2) }}%</td>
                                                    <td class="text-end">${{ number_format($detalle->impuesto_valor, 0, ',', '.') }}</td>
                                                @endif
                                                <td class="text-end"><strong>${{ number_format($detalle->total, 0, ',', '.') }}</strong></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Totales -->
                            <div class="row">
                                <div class="col-md-8"></div>
                                <div class="col-md-4">
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Subtotal:</strong></td>
                                            <td class="text-end">${{ number_format($cotizacion->subtotal, 0, ',', '.') }}</td>
                                        </tr>
                                        @if($cotizacion->descuento > 0)
                                            <tr>
                                                <td><strong>Descuento:</strong></td>
                                                <td class="text-end">-${{ number_format($cotizacion->descuento, 0, ',', '.') }}</td>
                                            </tr>
                                        @endif
                                        @if($cotizacion->impuestos > 0)
                                            <tr>
                                                <td><strong>IVA:</strong></td>
                                                <td class="text-end">${{ number_format($cotizacion->impuestos, 0, ',', '.') }}</td>
                                            </tr>
                                        @endif
                                        <tr class="table-dark">
                                            <td><strong>TOTAL:</strong></td>
                                            <td class="text-end"><strong>${{ number_format($cotizacion->total, 0, ',', '.') }}</strong></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Observaciones y Condiciones -->
                    @if($cotizacion->observaciones || $cotizacion->condiciones_comerciales)
                        <div class="row">
                            @if($cotizacion->observaciones)
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Observaciones</h6>
                                        </div>
                                        <div class="card-body">
                                            <p class="mb-0">{{ $cotizacion->observaciones }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($cotizacion->condiciones_comerciales)
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Condiciones Comerciales</h6>
                                        </div>
                                        <div class="card-body">
                                            <p class="mb-0">{{ $cotizacion->condiciones_comerciales }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function cambiarEstado(nuevoEstado) {
    const titulo = nuevoEstado === 'aprobada' ? '¿Aprobar Cotización?' : '¿Rechazar Cotización?';
    const texto = nuevoEstado === 'aprobada' ? 
        'La cotización será marcada como aprobada y podrá convertirse en venta' : 
        'La cotización será marcada como rechazada';

    Swal.fire({
        title: titulo,
        text: texto,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/cotizaciones/{{ $cotizacion->id }}/cambiar-estado`,
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
                        text: 'Error al cambiar el estado',
                        icon: 'error'
                    });
                }
            });
        }
    });
}

function convertirAVenta() {
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
                url: `/cotizaciones/{{ $cotizacion->id }}/convertir-venta`,
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Éxito',
                            text: `Cotización convertida a venta ${response.numero_factura}`,
                            icon: 'success',
                            showCancelButton: true,
                            confirmButtonText: 'Ver Venta',
                            cancelButtonText: 'Continuar aquí'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = `/ventas/${response.venta_id}`;
                            } else {
                                location.reload();
                            }
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
</script>
@endpush
