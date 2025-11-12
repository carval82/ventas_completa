
@extends('layouts.app')

@section('title', 'Ventas')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Ventas</h1>
        <a href="{{ route('ventas.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nueva Venta
        </a>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('ventas.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Fecha Inicio</label>
                    <input type="date" class="form-control" name="fecha_inicio" value="{{ request('fecha_inicio') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Fin</label>
                    <input type="date" class="form-control" name="fecha_fin" value="{{ request('fecha_fin') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Buscar</label>
                    <input type="text" class="form-control" name="search" placeholder="Número de factura o cliente..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive" style="overflow-x: auto;">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Factura #</th>
                            <th>Tipo</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">IVA</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ventas as $venta)
                            <tr>
                                <td>
                                    <strong>{{ $venta->getNumeroFacturaMostrar() }}</strong>
                                    @if($venta->esFacturaElectronica())
                                        <br><small class="text-muted">ID Local: {{ $venta->numero_factura }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($venta->esFacturaElectronica())
                                        <span class="badge 
                                            @if($venta->estado_dian == 'draft' || !$venta->estado_dian) bg-warning text-dark
                                            @elseif($venta->estado_dian == 'open') bg-info
                                            @elseif($venta->estado_dian == 'sent') bg-primary
                                            @elseif($venta->estado_dian == 'accepted' || $venta->estado_dian == 'issued') bg-success
                                            @elseif($venta->estado_dian == 'rejected') bg-danger
                                            @elseif(str_contains(strtolower($venta->estado_dian), 'aprobado') || str_contains(strtolower($venta->estado_dian), 'aceptado')) bg-success
                                            @elseif(str_contains(strtolower($venta->estado_dian), 'observacion')) bg-warning
                                            @else bg-secondary
                                            @endif">
                                            <i class="fas fa-bolt"></i> 
                                            @if($venta->estado_dian == 'draft' || !$venta->estado_dian)
                                                Borrador
                                            @elseif($venta->estado_dian == 'open')
                                                Abierta
                                            @elseif($venta->estado_dian == 'sent')
                                                Enviada
                                            @elseif($venta->estado_dian == 'accepted' || $venta->estado_dian == 'issued')
                                                Aceptada
                                            @elseif($venta->estado_dian == 'rejected')
                                                Rechazada
                                            @elseif(str_contains(strtolower($venta->estado_dian), 'aprobado'))
                                                Aprobada DIAN
                                            @else
                                                {{ ucfirst($venta->estado_dian) }}
                                            @endif
                                        </span>
                                        <br><small class="text-muted">{{ ucfirst($venta->estado_dian ?? 'Pendiente') }}</small>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-file-invoice"></i> Local
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $venta->cliente->nombres }}</td>
                                <td>{{ $venta->fecha_venta->format('d/m/Y h:i A') }}</td>
                                <td class="text-end">${{ number_format($venta->subtotal, 2) }}</td>
                                <td class="text-end">${{ number_format($venta->iva, 2) }}</td>
                                <td class="text-end">${{ number_format($venta->total, 2) }}</td>
                                <td class="text-center" style="min-width: 200px;">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('ventas.show', $venta) }}" class="btn btn-sm btn-info" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        @if($venta->esFacturaElectronica())
                                            <!-- Botón de Verificar Estado siempre visible para facturas electrónicas -->
                                            <button class="btn btn-sm btn-outline-info" onclick="verificarEstadoFactura({{ $venta->id }})" title="Verificar Estado en Alegra">
                                                <i class="fas fa-sync"></i>
                                            </button>
                                        @else
                                            <!-- Botón para emisión directa de facturas locales -->
                                            <a href="{{ route('facturas.electronicas.emitir-directa', $venta->id) }}" class="btn btn-sm btn-warning" title="Emitir Factura Electrónica">
                                                <i class="fas fa-bolt"></i> Emitir
                                            </a>
                                        @endif
                                        
                                        @if($venta->esFacturaElectronica())
                                            
                                            @if($venta->estado_dian === 'draft' || !$venta->estado_dian)
                                                <!-- Factura en borrador - Necesita ser abierta -->
                                                <a href="{{ route('facturas.electronicas.abrir-y-emitir', $venta->id) }}" class="btn btn-sm btn-warning" title="Abrir Factura en Alegra">
                                                    <i class="fas fa-folder-open"></i>
                                                </a>
                                                
                                                <span class="btn btn-sm btn-secondary disabled" title="Debe abrir la factura primero">
                                                    <i class="fas fa-download"></i>
                                                </span>
                                            @else
                                                <!-- Factura abierta o superior - Todas las opciones disponibles -->
                                                <a href="{{ route('facturas.electronicas.descargar-pdf', $venta->id) }}" class="btn btn-sm btn-primary" target="_blank" title="Descargar PDF Carta">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                
                                                <a href="{{ route('facturas.electronicas.imprimir-tirilla', $venta->id) }}" class="btn btn-sm btn-success" target="_blank" title="Imprimir Tirilla">
                                                    <i class="fas fa-receipt"></i>
                                                </a>
                                                
                                                @if($venta->estado_dian === 'open')
                                                    <a href="{{ route('facturas.electronicas.enviar-dian', $venta->id) }}" class="btn btn-sm btn-outline-primary" title="Enviar a DIAN">
                                                        <i class="fas fa-paper-plane"></i>
                                                    </a>
                                                @endif
                                            @endif
                                            
                                            @if($venta->url_pdf_alegra)
                                                <a href="{{ $venta->url_pdf_alegra }}" class="btn btn-sm btn-secondary" target="_blank" title="Ver PDF de Alegra">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                            @endif
                                        @else
                                            <a href="{{ route('ventas.print', $venta) }}" class="btn btn-sm btn-secondary" target="_blank" title="Imprimir Tirilla">
                                                <i class="fas fa-receipt"></i>
                                            </a>
                                            <a href="{{ route('ventas.print-media-carta', $venta) }}" class="btn btn-sm btn-info" target="_blank" title="Imprimir Media Carta">
                                                <i class="fas fa-file-invoice"></i>
                                            </a>
                                            <button class="btn btn-sm btn-warning" onclick="emitirFacturaElectronica({{ $venta->id }})" title="Emitir factura electrónica">
                                                <i class="fas fa-bolt"></i> FE
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No hay ventas registradas</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $ventas->links() }}
        </div>
    </div>
</div>

<!-- Modal para progreso de facturación electrónica -->
<div class="modal fade" id="modalFacturacionElectronica" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Emitiendo Factura Electrónica</h5>
            </div>
            <div class="modal-body text-center">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p id="estadoFacturacion">Preparando factura...</p>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function emitirFacturaElectronica(ventaId) {
    // Mostrar modal de progreso
    const modal = new bootstrap.Modal(document.getElementById('modalFacturacionElectronica'));
    modal.show();
    
    // Actualizar estado
    document.getElementById('estadoFacturacion').textContent = 'Enviando a Alegra...';
    
    // Realizar petición AJAX
    fetch(`/ventas/${ventaId}/emitir-factura-electronica`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        modal.hide();
        
        if (data.success) {
            // Mostrar mensaje de éxito
            Swal.fire({
                icon: 'success',
                title: '¡Factura Electrónica Emitida!',
                html: `
                    <p><strong>Número FE:</strong> ${data.numero_factura_electronica || 'N/A'}</p>
                    <p><strong>CUFE:</strong> ${data.cufe || 'N/A'}</p>
                    <p><strong>Estado DIAN:</strong> ${data.estado_dian || 'Procesando'}</p>
                `,
                confirmButtonText: 'Aceptar'
            }).then(() => {
                // Recargar la página para actualizar el estado
                location.reload();
            });
        } else {
            // Mostrar error
            Swal.fire({
                icon: 'error',
                title: 'Error al Emitir Factura',
                text: data.message || 'Ocurrió un error inesperado',
                confirmButtonText: 'Aceptar'
            });
        }
    })
    .catch(error => {
        modal.hide();
        console.error('Error:', error);
        
        Swal.fire({
            icon: 'error',
            title: 'Error de Conexión',
            text: 'No se pudo conectar con el servidor',
            confirmButtonText: 'Aceptar'
        });
    });
}

function verificarEstadoFactura(ventaId) {
    // Mostrar indicador de carga
    const boton = event.target.closest('button');
    const iconoOriginal = boton.innerHTML;
    boton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    boton.disabled = true;
    
    // Realizar petición AJAX
    fetch(`/facturas-electronicas/${ventaId}/verificar-estado`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        // Restaurar botón
        boton.innerHTML = iconoOriginal;
        boton.disabled = false;
        
        if (data.success) {
            // Mostrar mensaje de éxito
            Swal.fire({
                icon: 'success',
                title: '¡Estado Actualizado!',
                html: `
                    <p><strong>Estado DIAN:</strong> ${data.data.status || 'No disponible'}</p>
                    ${data.data.cufe ? `<p><strong>CUFE:</strong> ${data.data.cufe.substring(0, 20)}...</p>` : ''}
                    ${data.data.stamp && data.data.stamp.legalStatus ? `<p><strong>Estado Legal:</strong> ${data.data.stamp.legalStatus}</p>` : ''}
                `,
                confirmButtonText: 'Aceptar'
            }).then(() => {
                // Recargar la página para mostrar el estado actualizado
                location.reload();
            });
        } else {
            // Mostrar error
            Swal.fire({
                icon: 'error',
                title: 'Error al Verificar Estado',
                text: data.message || 'Ocurrió un error inesperado',
                confirmButtonText: 'Aceptar'
            });
        }
    })
    .catch(error => {
        // Restaurar botón
        boton.innerHTML = iconoOriginal;
        boton.disabled = false;
        
        console.error('Error:', error);
        
        Swal.fire({
            icon: 'error',
            title: 'Error de Conexión',
            text: 'No se pudo conectar con el servidor',
            confirmButtonText: 'Aceptar'
        });
    });
}
</script>
@endpush