@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4>Detalle de Factura Electrónica</h4>
                        <a href="{{ route('alegra.facturas.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Mensajes de alerta -->
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Información general de la factura -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5>Información de Factura</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <th>ID Alegra:</th>
                                            <td>{{ $factura['id'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Número:</th>
                                            <td>{{ $factura['numberTemplate']['fullNumber'] ?? ($factura['numberTemplate']['prefix'] ?? '') . ($factura['numberTemplate']['formattedNumber'] ?? '') }}</td>
                                        </tr>
                                        <tr>
                                            <th>Fecha:</th>
                                            <td>{{ \Carbon\Carbon::parse($factura['date'])->format('d/m/Y') }}</td>
                                        </tr>
                                        <tr>
                                            <th>Estado:</th>
                                            <td>
                                                @if($factura['status'] == 'open')
                                                    <span class="badge bg-success">Abierta</span>
                                                @elseif($factura['status'] == 'closed')
                                                    <span class="badge bg-primary">Cerrada</span>
                                                @elseif($factura['status'] == 'voided')
                                                    <span class="badge bg-danger">Anulada</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $factura['status'] }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Vencimiento:</th>
                                            <td>{{ \Carbon\Carbon::parse($factura['dueDate'])->format('d/m/Y') }}</td>
                                        </tr>
                                        <tr>
                                            <th>Forma de Pago:</th>
                                            <td>{{ $factura['paymentForm']['name'] ?? 'No especificada' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Método de Pago:</th>
                                            <td>{{ $factura['paymentMethod']['name'] ?? 'No especificado' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5>Información del Cliente</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <th>Nombre:</th>
                                            <td>{{ $factura['client']['name'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Identificación:</th>
                                            <td>{{ $factura['client']['identification'] ?? 'No especificada' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Email:</th>
                                            <td>{{ $factura['client']['email'] ?? 'No especificado' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Teléfono:</th>
                                            <td>{{ $factura['client']['phone'] ?? 'No especificado' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Dirección:</th>
                                            <td>
                                                {{ $factura['client']['address']['address'] ?? 'No especificada' }}
                                                {{ $factura['client']['address']['city']['name'] ?? '' }}
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estado de factura electrónica -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-warning">
                                    <h5>Estado de Factura Electrónica</h5>
                                </div>
                                <div class="card-body" id="estado-electronico">
                                    <div class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Cargando...</span>
                                        </div>
                                        <p>Consultando estado...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detalle de ítems -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5>Detalle de Productos</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Descripción</th>
                                            <th class="text-end">Cantidad</th>
                                            <th class="text-end">Precio</th>
                                            <th class="text-end">Descuento</th>
                                            <th class="text-end">IVA</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($factura['items'] as $item)
                                        <tr>
                                            <td>{{ $item['item']['code'] ?? 'N/A' }}</td>
                                            <td>{{ $item['name'] }}</td>
                                            <td class="text-end">{{ number_format($item['quantity'], 2) }}</td>
                                            <td class="text-end">{{ number_format($item['price'], 2) }}</td>
                                            <td class="text-end">{{ number_format($item['discount'] ?? 0, 2) }}%</td>
                                            <td class="text-end">{{ number_format($item['tax'][0]['amount'] ?? 0, 2) }}</td>
                                            <td class="text-end">{{ number_format($item['total'], 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="5"></th>
                                            <th class="text-end">Subtotal:</th>
                                            <td class="text-end">{{ number_format($factura['subtotal'], 2) }}</td>
                                        </tr>
                                        <tr>
                                            <th colspan="5"></th>
                                            <th class="text-end">IVA:</th>
                                            <td class="text-end">{{ number_format($factura['total'] - $factura['subtotal'], 2) }}</td>
                                        </tr>
                                        <tr>
                                            <th colspan="5"></th>
                                            <th class="text-end">Total:</th>
                                            <td class="text-end">{{ number_format($factura['total'], 2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Información de venta local vinculada -->
                    @if($venta)
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5>Venta Local Vinculada</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <th>ID Venta:</th>
                                            <td>{{ $venta->id }}</td>
                                        </tr>
                                        <tr>
                                            <th>Número Factura:</th>
                                            <td>{{ $venta->numero_factura }}</td>
                                        </tr>
                                        <tr>
                                            <th>Fecha:</th>
                                            <td>{{ \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y') }}</td>
                                        </tr>
                                        <tr>
                                            <th>Cliente:</th>
                                            <td>{{ $venta->cliente->nombres ?? '' }} {{ $venta->cliente->apellidos ?? '' }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <th>Subtotal:</th>
                                            <td>{{ number_format($venta->subtotal, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <th>IVA:</th>
                                            <td>{{ number_format($venta->iva, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <th>Total:</th>
                                            <td>{{ number_format($venta->total, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <th>Acciones:</th>
                                            <td>
                                                <a href="{{ route('ventas.show', $venta->id) }}" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> Ver Venta
                                                </a>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> Esta factura no está vinculada a ninguna venta local.
                        <button type="button" class="btn btn-sm btn-warning ms-3" data-bs-toggle="modal" data-bs-target="#vincularModal">
                            Vincular a Venta
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para vincular factura con venta -->
@if(!$venta)
<div class="modal fade" id="vincularModal" tabindex="-1" aria-labelledby="vincularModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="vincularModalLabel">Vincular Factura con Venta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('alegra.facturas.vincular') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="alegra_id" value="{{ $factura['id'] }}">
                    <input type="hidden" name="numero_factura" value="{{ $factura['numberTemplate']['prefix'] ?? '' }}{{ $factura['number'] }}">
                    
                    <div class="mb-3">
                        <label for="venta_id" class="form-label">Seleccione la Venta</label>
                        <select class="form-control" id="venta_id" name="venta_id" required>
                            <option value="">-- Seleccione una venta --</option>
                            @foreach($ventas ?? [] as $v)
                                <option value="{{ $v->id }}">
                                    Venta #{{ $v->id }} - {{ $v->numero_factura }} - 
                                    {{ \Carbon\Carbon::parse($v->fecha_venta)->format('d/m/Y') }} - 
                                    ${{ number_format($v->total, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Vincular</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Consultar estado electrónico de la factura
        const estadoElectronicoDiv = document.getElementById('estado-electronico');
        
        fetch(`/alegra/facturas/{{ $factura['id'] }}/estado`)
            .then(response => response.json())
            .then(data => {
                let html = '';
                
                if (data.error) {
                    html = `<div class="alert alert-danger">Error al consultar estado: ${data.error}</div>`;
                } else if (!data || !data.status) {
                    html = `<div class="alert alert-warning">Esta factura no ha sido emitida electrónicamente.</div>`;
                } else {
                    // Construir tabla de estado
                    html = `<table class="table">
                        <tr>
                            <th>Estado:</th>
                            <td>
                                ${getStatusBadge(data.status)}
                            </td>
                        </tr>`;
                    
                    if (data.dianStatus) {
                        html += `<tr>
                            <th>Estado DIAN:</th>
                            <td>${data.dianStatus}</td>
                        </tr>`;
                    }
                    
                    if (data.statusCode) {
                        html += `<tr>
                            <th>Código de Estado:</th>
                            <td>${data.statusCode}</td>
                        </tr>`;
                    }
                    
                    if (data.statusMessage) {
                        html += `<tr>
                            <th>Mensaje:</th>
                            <td>${data.statusMessage}</td>
                        </tr>`;
                    }
                    
                    if (data.cufe) {
                        html += `<tr>
                            <th>CUFE:</th>
                            <td>${data.cufe}</td>
                        </tr>`;
                    }
                    
                    if (data.pdfUrl) {
                        html += `<tr>
                            <th>PDF:</th>
                            <td><a href="${data.pdfUrl}" target="_blank" class="btn btn-sm btn-primary">
                                <i class="fas fa-file-pdf"></i> Ver PDF
                            </a></td>
                        </tr>`;
                    }
                    
                    if (data.xmlUrl) {
                        html += `<tr>
                            <th>XML:</th>
                            <td><a href="${data.xmlUrl}" target="_blank" class="btn btn-sm btn-secondary">
                                <i class="fas fa-file-code"></i> Ver XML
                            </a></td>
                        </tr>`;
                    }
                    
                    html += `</table>`;
                }
                
                estadoElectronicoDiv.innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                estadoElectronicoDiv.innerHTML = `<div class="alert alert-danger">Error al consultar estado: ${error.message}</div>`;
            });
    });
    
    function getStatusBadge(status) {
        switch(status) {
            case 'successful':
                return '<span class="badge bg-success">Validada</span>';
            case 'processing':
                return '<span class="badge bg-warning">En proceso</span>';
            case 'failed':
                return '<span class="badge bg-danger">Fallida</span>';
            default:
                return `<span class="badge bg-secondary">${status}</span>`;
        }
    }
</script>
@endsection
