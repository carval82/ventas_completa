@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3>Factura Electrónica #{{ $venta->numero_factura }}</h3>
                        <div>
                            <a href="{{ route('facturas.electronicas.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                            <a href="{{ route('facturas.electronicas.descargar-pdf', $venta->id) }}" class="btn btn-primary">
                                <i class="fas fa-file-pdf"></i> Descargar PDF
                            </a>
                            @if (!$venta->estado_dian || $venta->estado_dian == 'Pendiente' || $venta->estado_dian == 'draft')
                            <a href="{{ route('facturas.electronicas.abrir-factura', $venta->id) }}" class="btn btn-warning">
                                <i class="fas fa-folder-open"></i> Abrir Factura
                            </a>
                            <a href="{{ route('facturas.electronicas.abrir-y-emitir', $venta->id) }}" class="btn btn-warning">
                                <i class="fas fa-bolt"></i> Abrir y Emitir
                            </a>
                            <a href="{{ route('facturas.electronicas.enviar-dian', $venta->id) }}" class="btn btn-success">
                                <i class="fas fa-paper-plane"></i> Enviar a DIAN
                            </a>
                            @endif
                            <button id="btn-verificar-estado" class="btn btn-info">
                                <i class="fas fa-sync"></i> Verificar Estado
                            </button>
                            @if ($venta->estado_dian && ($venta->estado_dian == 'accepted' || $venta->estado_dian == 'Aceptado'))
                            <a href="{{ route('facturas.electronicas.imprimir', $venta->id) }}" class="btn btn-success">
                                <i class="fas fa-print"></i> Imprimir
                            </a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div id="estado-alert" class="alert alert-info d-none">
                        Verificando estado...
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5>Información de la Factura</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="40%">Número de Factura:</th>
                                            <td>{{ $venta->numero_factura }}</td>
                                        </tr>
                                        <tr>
                                            <th>Fecha:</th>
                                            <td>{{ date('d/m/Y', strtotime($venta->fecha_venta)) }}</td>
                                        </tr>
                                        <tr>
                                            <th>ID Alegra:</th>
                                            <td>{{ $venta->alegra_id }}</td>
                                        </tr>
                                        <tr>
                                            <th>Estado DIAN:</th>
                                            <td>
                                                <span id="estado-dian" class="badge {{ $venta->estado_dian ? 'bg-success' : 'bg-warning' }}">
                                                    {{ $venta->estado_dian ?? 'Pendiente' }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>CUFE:</th>
                                            <td>
                                                <small id="cufe">{{ $venta->cufe ?? 'No disponible' }}</small>
                                            </td>
                                        </tr>
                                        @if($venta->qr_code)
                                        <tr>
                                            <th>Código QR:</th>
                                            <td>
                                                <div class="text-center">
                                                    <img src="data:image/png;base64,{{ $venta->qr_code }}" alt="QR Code" style="max-width: 150px;">
                                                </div>
                                            </td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5>Información del Cliente</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="40%">Nombre:</th>
                                            <td>{{ $venta->cliente->nombres }} {{ $venta->cliente->apellidos }}</td>
                                        </tr>
                                        <tr>
                                            <th>Cédula:</th>
                                            <td>{{ $venta->cliente->cedula }}</td>
                                        </tr>
                                        <tr>
                                            <th>Teléfono:</th>
                                            <td>{{ $venta->cliente->telefono }}</td>
                                        </tr>
                                        <tr>
                                            <th>Email:</th>
                                            <td>{{ $venta->cliente->email }}</td>
                                        </tr>
                                        <tr>
                                            <th>Dirección:</th>
                                            <td>{{ $venta->cliente->direccion }}</td>
                                        </tr>
                                        <tr>
                                            <th>ID Alegra:</th>
                                            <td>{{ $venta->cliente->id_alegra }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-light">
                            <h5>Detalles de la Factura</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th>Unidad</th>
                                            <th>Cantidad</th>
                                            <th>Precio Unitario</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($venta->detalles as $detalle)
                                            <tr>
                                                <td>{{ $detalle->producto->nombre }}</td>
                                                <td>{{ $detalle->producto->unidad_medida ?? 'unit' }}</td>
                                                <td>{{ $detalle->cantidad }}</td>
                                                <td>${{ number_format($detalle->precio_unitario, 2) }}</td>
                                                <td>${{ number_format($detalle->subtotal, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="4" class="text-right">Subtotal:</th>
                                            <td>${{ number_format($venta->subtotal, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <th colspan="4" class="text-right">IVA:</th>
                                            <td>${{ number_format($venta->iva, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <th colspan="4" class="text-right">Total:</th>
                                            <td>${{ number_format($venta->total, 2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    @if(isset($estadoAlegra) && $estadoAlegra['success'])
                    <div class="card mt-4">
                        <div class="card-header bg-light">
                            <h5>Estado en Alegra</h5>
                        </div>
                        <div class="card-body">
                            <pre class="bg-light p-3">{{ json_encode($estadoAlegra['data'], JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnVerificarEstado = document.getElementById('btn-verificar-estado');
        const estadoAlert = document.getElementById('estado-alert');
        const estadoDian = document.getElementById('estado-dian');
        const cufeElement = document.getElementById('cufe');
        
        btnVerificarEstado.addEventListener('click', function() {
            estadoAlert.classList.remove('d-none');
            estadoAlert.classList.remove('alert-success', 'alert-danger');
            estadoAlert.classList.add('alert-info');
            estadoAlert.textContent = 'Verificando estado...';
            
            fetch('{{ route("facturas.electronicas.verificar-estado", $venta->id) }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        estadoAlert.classList.remove('alert-info', 'alert-danger');
                        estadoAlert.classList.add('alert-success');
                        estadoAlert.textContent = 'Estado actualizado correctamente: ' + (data.data.status || 'Desconocido');
                        
                        // Actualizar estado
                        if (data.data.status) {
                            estadoDian.textContent = data.data.status;
                            
                            // Cambiar el color según el estado
                            estadoDian.classList.remove('bg-warning', 'bg-success', 'bg-danger', 'bg-info');
                            
                            if (data.data.status === 'draft') {
                                estadoDian.classList.add('bg-warning');
                            } else if (data.data.status === 'open') {
                                estadoDian.classList.add('bg-info');
                            } else if (data.data.status === 'accepted' || data.data.status === 'issued') {
                                estadoDian.classList.add('bg-success');
                            } else if (data.data.status === 'rejected') {
                                estadoDian.classList.add('bg-danger');
                            } else {
                                estadoDian.classList.add('bg-secondary');
                            }
                        }
                        
                        // Actualizar CUFE si existe
                        if (data.data.cufe) {
                            cufeElement.textContent = data.data.cufe;
                        }
                        
                        // Mostrar mensaje detallado
                        let detallesEstado = '';
                        if (data.data.dianStatus) {
                            detallesEstado += '<br><strong>Estado DIAN:</strong> ' + data.data.dianStatus;
                        }
                        if (data.data.message) {
                            detallesEstado += '<br><strong>Mensaje:</strong> ' + data.data.message;
                        }
                        
                        if (detallesEstado) {
                            estadoAlert.innerHTML = estadoAlert.textContent + detallesEstado;
                        }
                        
                        // Recargar la página después de 3 segundos
                        setTimeout(() => {
                            window.location.reload();
                        }, 3000);
                    } else {
                        estadoAlert.classList.remove('alert-info', 'alert-success');
                        estadoAlert.classList.add('alert-danger');
                        estadoAlert.textContent = data.message || 'Error al verificar estado';
                    }
                })
                .catch(error => {
                    estadoAlert.classList.remove('alert-info', 'alert-success');
                    estadoAlert.classList.add('alert-danger');
                    estadoAlert.textContent = 'Error de conexión al verificar estado';
                    console.error('Error:', error);
                });
        });
    });
</script>
@endsection
