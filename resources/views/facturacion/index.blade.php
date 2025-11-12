@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-invoice-dollar"></i>
                        Configuración de Facturación Electrónica
                    </h3>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle"></i> Proveedor Activo</h5>
                                <p class="mb-0">
                                    Actualmente usando: <strong>{{ $proveedorActivo ? $proveedores[$proveedorActivo->proveedor]['nombre'] ?? 'No configurado' : 'No configurado' }}</strong>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        @foreach($proveedores as $key => $proveedor)
                        <div class="col-md-6 col-lg-3 mb-4">
                            <div class="card h-100 {{ $proveedorActivo && $key === $proveedorActivo->proveedor ? 'border-primary' : '' }}">
                                <div class="card-header {{ $proveedorActivo && $key === $proveedorActivo->proveedor ? 'bg-primary text-white' : '' }}">
                                    <h5 class="card-title mb-0">
                                        {{ $proveedor['nombre'] }}
                                        @if($proveedorActivo && $key === $proveedorActivo->proveedor)
                                            <span class="badge badge-light ml-2">ACTIVO</span>
                                        @endif
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="card-text text-muted small">
                                        {{ $proveedor['descripcion'] }}
                                    </p>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center">
                                            <span class="mr-2">Estado:</span>
                                            @if($proveedor['activo'])
                                                @if($proveedor['configurado'] ?? false)
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check"></i> Configurado
                                                    </span>
                                                @else
                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-exclamation-triangle"></i> Sin configurar
                                                    </span>
                                                @endif
                                            @else
                                                <span class="badge badge-secondary">
                                                    <i class="fas fa-pause"></i> Deshabilitado
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    @if(isset($proveedor['config_actual']))
                                        <div class="mb-3">
                                            <small class="text-muted">Configuración:</small>
                                            <ul class="list-unstyled small">
                                                @foreach($proveedor['config_actual'] as $configKey => $configValue)
                                                    @if($configKey !== 'activo')
                                                        <li>
                                                            <strong>{{ ucfirst(str_replace('_', ' ', $configKey)) }}:</strong>
                                                            {{ $configValue ? (str_contains($configKey, 'password') || str_contains($configKey, 'token') || str_contains($configKey, 'key') ? '***' : $configValue) : 'No configurado' }}
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                                <div class="card-footer">
                                    <a href="{{ route('facturacion.configurar', $key) }}" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-cog"></i> Configurar
                                    </a>
                                    
                                    <div class="text-center">
                                        <a href="{{ route('facturacion.probar-conexion-directo', $key) }}" 
                                           class="btn btn-info btn-sm mb-1">
                                            <i class="fas fa-plug"></i> Probar
                                        </a>
                                        <br>
                                        <a href="{{ route('facturacion.sincronizar-productos-vista', $key) }}" 
                                           class="btn btn-success btn-sm mb-1">
                                            <i class="fas fa-box"></i> Sync Productos
                                        </a>
                                        <br>
                                        <a href="{{ route('facturacion.sincronizar-clientes-vista', $key) }}" 
                                           class="btn btn-warning btn-sm">
                                            <i class="fas fa-users"></i> Sync Clientes
                                        </a>
                                    </div>            
                                    @if($proveedor['configurado'] ?? false)
                                        @if(!$proveedorActivo || $key !== $proveedorActivo->proveedor)
                                            <form method="POST" action="{{ route('facturacion.cambiar-proveedor') }}" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="proveedor" value="{{ $key }}">
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-check"></i> Activar
                                                </button>
                                            </form>
                                        @endif
                                        
                                        @if(in_array($key, ['alegra', 'siigo', 'worldoffice']))
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="sincronizarProductos('{{ $key }}')">
                                                <i class="fas fa-sync"></i> Sync
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Configuración de Variables de Entorno</h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">Para configurar los proveedores, agrega las siguientes variables a tu archivo <code>.env</code>:</p>
                                    
                                    <div class="accordion" id="configAccordion">
                                        @foreach($proveedores as $key => $proveedor)
                                        <div class="card">
                                            <div class="card-header" id="heading{{ ucfirst($key) }}">
                                                <h6 class="mb-0">
                                                    <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse{{ ucfirst($key) }}">
                                                        Configuración {{ $proveedor['nombre'] }}
                                                    </button>
                                                </h6>
                                            </div>
                                            <div id="collapse{{ ucfirst($key) }}" class="collapse" data-parent="#configAccordion">
                                                <div class="card-body">
                                                    @if($key === 'alegra')
                                                        <pre><code>ALEGRA_USUARIO=tu_usuario
ALEGRA_TOKEN=tu_token
ALEGRA_URL_BASE=https://api.alegra.com/api/v1</code></pre>
                                                    @elseif($key === 'dian')
                                                        <pre><code>DIAN_NIT_EMPRESA=123456789
DIAN_USERNAME=tu_usuario
DIAN_PASSWORD=tu_password
DIAN_TEST_MODE=true</code></pre>
                                                    @elseif($key === 'siigo')
                                                        <pre><code>SIIGO_USERNAME=tu_usuario
SIIGO_ACCESS_KEY=tu_access_key</code></pre>
                                                    @elseif($key === 'worldoffice')
                                                        <pre><code>WORLDOFFICE_API_KEY=tu_api_key
WORLDOFFICE_COMPANY_ID=tu_company_id</code></pre>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
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

<!-- Modal para resultados -->
<div class="modal fade" id="resultModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Resultado</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="resultContent">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Función simple para probar que el JavaScript funciona
function probarConexionSimple(proveedor) {
    alert('Probando conexión con: ' + proveedor);
    console.log('Función JavaScript ejecutada correctamente para:', proveedor);
    
    // Test básico de fetch
    fetch('{{ route("facturacion.probar-conexion") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            proveedor: proveedor
        })
    })
    .then(response => {
        console.log('Respuesta recibida:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Datos recibidos:', data);
        alert('Respuesta: ' + JSON.stringify(data));
    })
    .catch(error => {
        console.error('Error en fetch:', error);
        alert('Error: ' + error.message);
    });
}

function probarConexion(proveedor) {
    console.log('Probando conexión con:', proveedor);
    
    // Mostrar loading en el botón
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Probando...';
    btn.disabled = true;
    // Verificar si jQuery está disponible
    if (typeof $ === 'undefined') {
        console.error('jQuery no está disponible');
        alert('Error: jQuery no está cargado');
    } else {
        // Mostrar loading en el botón
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Probando...';
        btn.disabled = true;

        $.ajax({
            url: '{{ route("facturacion.probar-conexion") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                proveedor: proveedor
            },
            success: function(response) {
                // Restaurar botón
                btn.innerHTML = originalText;
                btn.disabled = false;

                let content = `
                    <div class="alert alert-success">
                        <h6><i class="fas fa-check-circle"></i> Conexión Exitosa con ${proveedor.toUpperCase()}</h6>
                        <p>${response.message}</p>
                    </div>
                `;
                
                if (response.data) {
                    content += '<h6>Información obtenida:</h6><ul>';
                    for (let key in response.data) {
                        if (key !== 'status') {
                            content += `<li><strong>${key}:</strong> ${response.data[key]}</li>`;
                        }
            if (data.data) {
                content += '<h6>Información obtenida:</h6><ul>';
                for (let key in data.data) {
                    if (key !== 'status') {
                        content += `<li><strong>${key}:</strong> ${data.data[key]}</li>`;
                    }
                }
                content += '</ul>';
            }
            
            mostrarModal('Prueba de Conexión', content);
        } else {
            mostrarModal('Error de Conexión', `
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-circle"></i> Error de Conexión</h6>
                    <p>${data.message}</p>
                </div>
            `);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Restaurar botón
        btn.innerHTML = originalText;
        btn.disabled = false;
        
        mostrarModal('Error', `
            <div class="alert alert-danger">
                <h6><i class="fas fa-exclamation-circle"></i> Error</h6>
                <p>Error de conexión: ${error.message}</p>
            </div>
        `);
    });
}

function mostrarModal(titulo, contenido) {
    document.getElementById('resultContent').innerHTML = contenido;
    
    // Si jQuery está disponible, usar Bootstrap modal
    if (typeof $ !== 'undefined' && $.fn.modal) {
        $('#resultModal').modal('show');
    } else {
        // Fallback: mostrar alert
        alert(titulo + '\n\n' + contenido.replace(/<[^>]*>/g, ''));
    }
}

function sincronizarProductos(proveedor) {
    // Mostrar loading en el botón
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sincronizando...';
    btn.disabled = true;

    $.ajax({
        url: '{{ route("facturacion.sincronizar-productos") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            proveedor: proveedor
        },
        success: function(response) {
            // Restaurar botón
            btn.innerHTML = originalText;
            btn.disabled = false;

            let content = `
                <div class="alert alert-${response.success ? 'success' : 'warning'}">
                    <h6><i class="fas fa-sync"></i> Sincronización de Productos</h6>
                    <p>${response.message}</p>
                </div>
            `;
            
            $('#resultContent').html(content);
            $('#resultModal').modal('show');
        },
        error: function(xhr) {
            // Restaurar botón
            btn.innerHTML = originalText;
            btn.disabled = false;

            let response = xhr.responseJSON;
            $('#resultContent').html(`
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-circle"></i> Error de Sincronización</h6>
                    <p>${response ? response.message : 'Error desconocido al sincronizar productos'}</p>
        }
    })
    .catch(error => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        console.error('Error:', error);
        alert('Error de conexión al sincronizar productos');
    });
}

function sincronizarClientes(proveedor) {
    if (!confirm('¿Estás seguro de que deseas sincronizar los clientes desde ' + proveedor + '? Esto puede tomar varios minutos.')) {
        return;
    }

    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sincronizando...';
    btn.disabled = true;

    fetch('{{ route("facturacion.sincronizar-clientes") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            proveedor: proveedor
        })
    })
    .then(response => response.json())
    .then(data => {
        btn.innerHTML = originalText;
        btn.disabled = false;

        if (data.success) {
            alert('Clientes sincronizados exitosamente!\n\n' +
                  'Clientes creados: ' + (data.data.clientes_creados || 0) + '\n' +
                  'Clientes actualizados: ' + (data.data.clientes_actualizados || 0) + '\n' +
                  'Total procesados: ' + (data.data.total_procesados || 0));
            
            // Recargar la página para mostrar cambios
            location.reload();
        } else {
            alert('Error al sincronizar clientes: ' + data.message);
        }
    })
    .catch(error => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        console.error('Error:', error);
        alert('Error de conexión al sincronizar clientes');
    });
}
</script>
@endsection
