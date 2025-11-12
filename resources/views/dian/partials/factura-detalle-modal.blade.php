<div class="row">
    <div class="col-md-6">
        <h6 class="text-primary mb-3">📧 Información del Email</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Remitente:</strong></td>
                <td>{{ $factura->remitente_email }}</td>
            </tr>
            @if($factura->remitente_nombre)
            <tr>
                <td><strong>Nombre:</strong></td>
                <td>{{ $factura->remitente_nombre }}</td>
            </tr>
            @endif
            <tr>
                <td><strong>Asunto:</strong></td>
                <td>{{ $factura->asunto_email }}</td>
            </tr>
            <tr>
                <td><strong>Fecha Email:</strong></td>
                <td>{{ $factura->fecha_email ? $factura->fecha_email->format('d/m/Y H:i:s') : 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Procesado:</strong></td>
                <td>{{ $factura->created_at->format('d/m/Y H:i:s') }}</td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6 class="text-success mb-3">📄 Información de la Factura</h6>
        <table class="table table-sm">
            @if($factura->numero_factura)
            <tr>
                <td><strong>Número:</strong></td>
                <td><span class="badge bg-primary">{{ $factura->numero_factura }}</span></td>
            </tr>
            @endif
            @if($factura->cufe)
            <tr>
                <td><strong>CUFE:</strong></td>
                <td><code class="small">{{ $factura->cufe }}</code></td>
            </tr>
            @endif
            @if($factura->nit_emisor)
            <tr>
                <td><strong>NIT Emisor:</strong></td>
                <td>{{ $factura->nit_emisor }}</td>
            </tr>
            @endif
            @if($factura->nombre_emisor)
            <tr>
                <td><strong>Emisor:</strong></td>
                <td>{{ $factura->nombre_emisor }}</td>
            </tr>
            @endif
            @if($factura->valor_total)
            <tr>
                <td><strong>Valor Total:</strong></td>
                <td><strong class="text-success">${{ number_format($factura->valor_total, 2) }}</strong></td>
            </tr>
            @endif
        </table>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <h6 class="text-info mb-3">⚙️ Estado del Procesamiento</h6>
        <div class="row">
            <div class="col-md-4">
                <div class="card border-0 bg-light">
                    <div class="card-body text-center py-2">
                        <div class="d-flex align-items-center justify-content-center">
                            @switch($factura->estado)
                                @case('procesada')
                                    <i class="fas fa-check-circle text-success fa-2x me-2"></i>
                                    <div>
                                        <div class="fw-bold text-success">Procesada</div>
                                        <small class="text-muted">Completamente procesada</small>
                                    </div>
                                    @break
                                @case('pendiente')
                                    <i class="fas fa-clock text-warning fa-2x me-2"></i>
                                    <div>
                                        <div class="fw-bold text-warning">Pendiente</div>
                                        <small class="text-muted">En proceso</small>
                                    </div>
                                    @break
                                @case('error')
                                    <i class="fas fa-exclamation-triangle text-danger fa-2x me-2"></i>
                                    <div>
                                        <div class="fw-bold text-danger">Error</div>
                                        <small class="text-muted">Error en procesamiento</small>
                                    </div>
                                    @break
                                @default
                                    <i class="fas fa-question-circle text-secondary fa-2x me-2"></i>
                                    <div>
                                        <div class="fw-bold text-secondary">{{ $factura->estado }}</div>
                                        <small class="text-muted">Estado desconocido</small>
                                    </div>
                            @endswitch
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card border-0 bg-light">
                    <div class="card-body text-center py-2">
                        <div class="d-flex align-items-center justify-content-center">
                            @if($factura->acuse_enviado)
                                <i class="fas fa-paper-plane text-success fa-2x me-2"></i>
                                <div>
                                    <div class="fw-bold text-success">Acuse Enviado</div>
                                    <small class="text-muted">
                                        {{ $factura->fecha_acuse ? $factura->fecha_acuse->format('d/m/Y H:i') : 'Enviado' }}
                                    </small>
                                </div>
                            @else
                                <i class="fas fa-clock text-warning fa-2x me-2"></i>
                                <div>
                                    <div class="fw-bold text-warning">Acuse Pendiente</div>
                                    <small class="text-muted">No enviado</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card border-0 bg-light">
                    <div class="card-body text-center py-2">
                        <div class="d-flex align-items-center justify-content-center">
                            @if($factura->archivo_xml)
                                <i class="fas fa-file-code text-info fa-2x me-2"></i>
                                <div>
                                    <div class="fw-bold text-info">XML Disponible</div>
                                    <small class="text-muted">Archivo procesado</small>
                                </div>
                            @else
                                <i class="fas fa-file-times text-muted fa-2x me-2"></i>
                                <div>
                                    <div class="fw-bold text-muted">Sin XML</div>
                                    <small class="text-muted">No disponible</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($factura->observaciones)
<div class="row mt-3">
    <div class="col-12">
        <h6 class="text-warning mb-2">📝 Observaciones</h6>
        <div class="alert alert-warning">
            {{ $factura->observaciones }}
        </div>
    </div>
</div>
@endif

@if($factura->error_mensaje)
<div class="row mt-3">
    <div class="col-12">
        <h6 class="text-danger mb-2">❌ Error</h6>
        <div class="alert alert-danger">
            <strong>Error:</strong> {{ $factura->error_mensaje }}
        </div>
    </div>
</div>
@endif

<div class="row mt-4">
    <div class="col-12">
        <h6 class="text-secondary mb-3">🔧 Acciones Disponibles</h6>
        <div class="d-flex gap-2 flex-wrap">
            @if($factura->archivo_xml)
                <a href="{{ route('dian.factura.xml', $factura) }}" 
                   class="btn btn-sm btn-outline-success" 
                   target="_blank">
                    <i class="fas fa-download"></i> Descargar XML
                </a>
            @endif
            
            @if(!$factura->acuse_enviado)
                <button type="button" 
                        class="btn btn-sm btn-outline-info" 
                        onclick="enviarAcuseDesdeModal({{ $factura->id }})">
                    <i class="fas fa-paper-plane"></i> Enviar Acuse
                </button>
            @endif
            
            <button type="button" 
                    class="btn btn-sm btn-outline-primary" 
                    onclick="reprocesarFactura({{ $factura->id }})">
                <i class="fas fa-sync-alt"></i> Reprocesar
            </button>
            
            <a href="{{ route('dian.factura.detalle', $factura) }}" 
               class="btn btn-sm btn-outline-secondary" 
               target="_blank">
                <i class="fas fa-external-link-alt"></i> Ver Completo
            </a>
        </div>
    </div>
</div>

<script>
function enviarAcuseDesdeModal(facturaId) {
    if (confirm('¿Deseas enviar el acuse de recibido para esta factura?')) {
        fetch(`/dian/facturas/${facturaId}/acuse`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Acuse enviado correctamente');
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Error enviando acuse');
        });
    }
}

function reprocesarFactura(facturaId) {
    alert('🔄 Función de reprocesamiento en desarrollo');
}
</script>
