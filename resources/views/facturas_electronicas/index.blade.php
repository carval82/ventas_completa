@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3>Facturas Electrónicas</h3>
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

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Factura</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Total</th>
                                    <th>ID Alegra</th>
                                    <th>Estado DIAN</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($ventas as $venta)
                                    <tr>
                                        <td>{{ $venta->numero_factura }}</td>
                                        <td>{{ date('d/m/Y', strtotime($venta->fecha_venta)) }}</td>
                                        <td>{{ $venta->cliente->nombres }} {{ $venta->cliente->apellidos }}</td>
                                        <td>${{ number_format($venta->total, 2) }}</td>
                                        <td>{{ $venta->alegra_id }}</td>
                                        <td>
                                            <span class="badge 
                                                @if($venta->estado_dian == 'draft') bg-warning
                                                @elseif($venta->estado_dian == 'open') bg-info
                                                @elseif($venta->estado_dian == 'accepted' || $venta->estado_dian == 'issued') bg-success
                                                @elseif($venta->estado_dian == 'rejected') bg-danger
                                                @elseif(!$venta->estado_dian) bg-warning
                                                @else bg-secondary
                                                @endif">
                                                {{ $venta->estado_dian ?? 'Pendiente' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('facturas.electronicas.show', $venta->id) }}" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> Ver
                                                </a>
                                                <a href="{{ route('facturas.electronicas.descargar-pdf', $venta->id) }}" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-file-pdf"></i> PDF
                                                </a>
                                                
                                                @if($venta->estado_dian && $venta->estado_dian !== 'draft')
                                                <a href="{{ route('facturas.electronicas.imprimir-tirilla', $venta->id) }}" 
                                                   class="btn btn-sm btn-dark"
                                                   target="_blank">
                                                    <i class="fas fa-receipt"></i> Tirilla
                                                </a>
                                                @endif
                                                
                                                @if (!$venta->estado_dian || $venta->estado_dian == 'Pendiente' || $venta->estado_dian == 'draft')
                                                <a href="{{ route('facturas.electronicas.abrir-factura', $venta->id) }}" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="fas fa-folder-open"></i> Abrir
                                                </a>
                                                @endif
                                                
                                                @if (!$venta->estado_dian || $venta->estado_dian == 'Pendiente' || $venta->estado_dian == 'draft' || $venta->estado_dian == 'open')
                                                <a href="{{ route('facturas.electronicas.enviar-dian', $venta->id) }}" 
                                                   class="btn btn-sm btn-success">
                                                    <i class="fas fa-paper-plane"></i> DIAN
                                                </a>
                                                @endif
                                                
                                                <button class="btn btn-sm btn-secondary verificar-estado" 
                                                        data-id="{{ $venta->id }}"
                                                        data-url="{{ route('facturas.electronicas.verificar-estado', $venta->id) }}">
                                                    <i class="fas fa-sync"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">No hay facturas electrónicas registradas</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $ventas->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Obtener todos los botones de verificar estado
        const botonesVerificar = document.querySelectorAll('.verificar-estado');
        
        console.log('Inicializando script de facturas electrónicas - Botones encontrados:', botonesVerificar.length);
        
        // Añadir evento de clic a cada botón
        botonesVerificar.forEach(boton => {
            boton.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const url = this.getAttribute('data-url');
                const botonOriginal = this;
                
                console.log('Verificando estado de factura:', id, 'URL:', url);
                
                // Cambiar el ícono a un spinner
                botonOriginal.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                botonOriginal.disabled = true;
                
                // Realizar la petición para verificar el estado
                fetch(url)
                    .then(response => {
                        console.log('Respuesta recibida:', response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log('Datos recibidos:', data);
                        
                        if (data.success) {
                            // Actualizar el estado en la interfaz
                            const fila = botonOriginal.closest('tr');
                            const celdaEstado = fila.querySelector('td:nth-child(6) .badge');
                            
                            if (data.data.status) {
                                // Actualizar el texto del estado
                                celdaEstado.textContent = data.data.status;
                                
                                // Actualizar la clase del badge según el estado
                                celdaEstado.classList.remove('bg-warning', 'bg-success', 'bg-danger', 'bg-info', 'bg-secondary');
                                
                                if (data.data.status === 'draft') {
                                    celdaEstado.classList.add('bg-warning');
                                } else if (data.data.status === 'open') {
                                    celdaEstado.classList.add('bg-info');
                                } else if (data.data.status === 'accepted' || data.data.status === 'issued') {
                                    celdaEstado.classList.add('bg-success');
                                } else if (data.data.status === 'rejected') {
                                    celdaEstado.classList.add('bg-danger');
                                } else {
                                    celdaEstado.classList.add('bg-secondary');
                                }
                            }
                            
                            // Mostrar notificación de éxito
                            const alertDiv = document.createElement('div');
                            alertDiv.className = 'alert alert-success alert-dismissible fade show mt-3';
                            alertDiv.innerHTML = `
                                <strong>Éxito!</strong> Estado actualizado correctamente para la factura #${id}.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            `;
                            
                            const cardBody = document.querySelector('.card-body');
                            cardBody.insertBefore(alertDiv, cardBody.firstChild);
                            
                            // Eliminar la alerta después de 3 segundos
                            setTimeout(() => {
                                alertDiv.remove();
                            }, 3000);
                        } else {
                            // Mostrar notificación de error
                            const alertDiv = document.createElement('div');
                            alertDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
                            alertDiv.innerHTML = `
                                <strong>Error!</strong> ${data.message || 'Error al verificar estado'}.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            `;
                            
                            const cardBody = document.querySelector('.card-body');
                            cardBody.insertBefore(alertDiv, cardBody.firstChild);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        
                        // Mostrar notificación de error
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
                        alertDiv.innerHTML = `
                            <strong>Error!</strong> Error de conexión al verificar estado.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                        
                        const cardBody = document.querySelector('.card-body');
                        cardBody.insertBefore(alertDiv, cardBody.firstChild);
                    })
                    .finally(() => {
                        // Restaurar el botón
                        botonOriginal.innerHTML = '<i class="fas fa-sync"></i>';
                        botonOriginal.disabled = false;
                    });
            });
        });
    });
</script>
@endsection
