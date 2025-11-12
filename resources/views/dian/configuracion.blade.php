@extends('layouts.app')

@section('title', 'Configuración Módulo DIAN')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-primary text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-0">
                                <i class="fas fa-cog"></i> Configuración Módulo DIAN
                            </h2>
                            <p class="mb-0 opacity-75">Configure la conexión automática al email DIAN y parámetros de procesamiento</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="{{ route('dian.dashboard') }}" class="btn btn-light">
                                <i class="fas fa-arrow-left"></i> Volver al Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Configuraciones Predefinidas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-rocket"></i> Configuración Rápida
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">Selecciona tu proveedor de email para autocompletar la configuración:</p>
                    
                    <div class="row">
                        @foreach($configuracionesPredefinidas as $key => $config)
                        <div class="col-md-3 mb-3">
                            <div class="card border h-100">
                                <div class="card-body text-center">
                                    <h6 class="card-title">{{ $config['nombre'] }}</h6>
                                    <p class="card-text small text-muted">{{ $config['ejemplo_email'] }}</p>
                                    <button type="button" 
                                            class="btn btn-outline-primary btn-sm btn-configurar-rapida" 
                                            data-config="{{ $key }}"
                                            data-servidor="{{ $config['servidor_imap'] }}"
                                            data-puerto="{{ $config['puerto_imap'] }}"
                                            data-ssl="{{ $config['ssl_enabled'] ? 'true' : 'false' }}">
                                        <i class="fas fa-magic"></i> Usar
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Detección Automática (opcional) -->
                    @if($configExistente['configuracion_encontrada'])
                    <div class="alert alert-info mt-3">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h6 class="alert-heading mb-1">
                                    <i class="fas fa-info-circle"></i> Configuración Detectada
                                </h6>
                                <p class="mb-0 small">
                                    {{ $configExistente['fuente'] }}: <strong>{{ $configExistente['email_detectado'] }}</strong>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <button type="button" class="btn btn-success btn-sm" id="btnAutocompletar">
                                    <i class="fas fa-download"></i> Importar
                                </button>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('dian.configuracion.guardar') }}" method="POST">
        @csrf
        
        <div class="row">
            <!-- Configuración de Email -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-envelope"></i> Configuración de Email DIAN
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="email_dian" class="form-label">Email DIAN *</label>
                            <input type="email" 
                                   class="form-control @error('email_dian') is-invalid @enderror" 
                                   id="email_dian" 
                                   name="email_dian" 
                                   value="{{ old('email_dian', $configuracion->email_dian) }}" 
                                   required>
                            @error('email_dian')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Email para recibir facturas electrónicas. 
                                <strong>Puede ser diferente al email de login.</strong>
                                Puedes usar cualquier cuenta Gmail, Outlook, etc.
                            </small>
                        </div>

                        <div class="mb-3">
                            <label for="password_email" class="form-label">Contraseña del Email *</label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control @error('password_email') is-invalid @enderror" 
                                       id="password_email" 
                                       name="password_email" 
                                       value="{{ old('password_email', $configuracion->password_email) }}" 
                                       required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                            @error('password_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="servidor_imap" class="form-label">Servidor IMAP *</label>
                                    <input type="text" 
                                           class="form-control @error('servidor_imap') is-invalid @enderror" 
                                           id="servidor_imap" 
                                           name="servidor_imap" 
                                           value="{{ old('servidor_imap', $configuracion->servidor_imap ?: 'imap.gmail.com') }}" 
                                           required>
                                    @error('servidor_imap')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="puerto_imap" class="form-label">Puerto *</label>
                                    <input type="number" 
                                           class="form-control @error('puerto_imap') is-invalid @enderror" 
                                           id="puerto_imap" 
                                           name="puerto_imap" 
                                           value="{{ old('puerto_imap', $configuracion->puerto_imap ?: 993) }}" 
                                           min="1" 
                                           max="65535" 
                                           required>
                                    @error('puerto_imap')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="ssl_enabled" 
                                       name="ssl_enabled" 
                                       value="1" 
                                       {{ old('ssl_enabled', $configuracion->ssl_enabled ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="ssl_enabled">
                                    Usar SSL/TLS (Recomendado)
                                </label>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="button" class="btn btn-outline-primary" onclick="probarConexion()">
                                <i class="fas fa-plug"></i> Probar Conexión
                            </button>
                        </div>
                        
                        <div id="resultado-conexion" class="mt-3" style="display: none;"></div>
                    </div>
                </div>
            </div>

            <!-- Configuración de Acuses -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-paper-plane"></i> Configuración de Acuses
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="email_remitente" class="form-label">Email Remitente</label>
                            <input type="email" 
                                   class="form-control @error('email_remitente') is-invalid @enderror" 
                                   id="email_remitente" 
                                   name="email_remitente" 
                                   value="{{ old('email_remitente', $configuracion->email_remitente) }}">
                            @error('email_remitente')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Si no se especifica, se usará el email DIAN</small>
                        </div>

                        <div class="mb-3">
                            <label for="nombre_remitente" class="form-label">Nombre del Remitente</label>
                            <input type="text" 
                                   class="form-control @error('nombre_remitente') is-invalid @enderror" 
                                   id="nombre_remitente" 
                                   name="nombre_remitente" 
                                   value="{{ old('nombre_remitente', $configuracion->nombre_remitente) }}">
                            @error('nombre_remitente')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="plantilla_acuse" class="form-label">Plantilla de Acuse</label>
                            <textarea class="form-control" 
                                      id="plantilla_acuse" 
                                      name="plantilla_acuse" 
                                      rows="8">{{ old('plantilla_acuse', $configuracion->plantilla_acuse) }}</textarea>
                            <small class="form-text text-muted">
                                Variables disponibles: {cufe}, {numero_factura}, {nit_emisor}, {nombre_emisor}, {valor_total}, {fecha_factura}, {nombre_empresa}, {nit_empresa}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configuración de Automatización -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-robot"></i> Configuración de Automatización
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="procesamiento_automatico" 
                                               name="procesamiento_automatico" 
                                               value="1" 
                                               {{ old('procesamiento_automatico', $configuracion->procesamiento_automatico ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="procesamiento_automatico">
                                            <strong>Procesamiento Automático</strong>
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">Activar procesamiento automático de emails</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="frecuencia_minutos" class="form-label">Frecuencia (minutos) *</label>
                                    <select class="form-control @error('frecuencia_minutos') is-invalid @enderror" 
                                            id="frecuencia_minutos" 
                                            name="frecuencia_minutos" 
                                            required>
                                        <option value="15" {{ old('frecuencia_minutos', $configuracion->frecuencia_minutos) == 15 ? 'selected' : '' }}>15 minutos</option>
                                        <option value="30" {{ old('frecuencia_minutos', $configuracion->frecuencia_minutos) == 30 ? 'selected' : '' }}>30 minutos</option>
                                        <option value="60" {{ old('frecuencia_minutos', $configuracion->frecuencia_minutos) == 60 ? 'selected' : '' }}>1 hora</option>
                                        <option value="120" {{ old('frecuencia_minutos', $configuracion->frecuencia_minutos) == 120 ? 'selected' : '' }}>2 horas</option>
                                        <option value="240" {{ old('frecuencia_minutos', $configuracion->frecuencia_minutos) == 240 ? 'selected' : '' }}>4 horas</option>
                                    </select>
                                    @error('frecuencia_minutos')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="hora_inicio" class="form-label">Hora Inicio *</label>
                                    <input type="time" 
                                           class="form-control @error('hora_inicio') is-invalid @enderror" 
                                           id="hora_inicio" 
                                           name="hora_inicio" 
                                           value="{{ old('hora_inicio', $configuracion->hora_inicio ?: '08:00') }}" 
                                           required>
                                    @error('hora_inicio')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="hora_fin" class="form-label">Hora Fin *</label>
                                    <input type="time" 
                                           class="form-control @error('hora_fin') is-invalid @enderror" 
                                           id="hora_fin" 
                                           name="hora_fin" 
                                           value="{{ old('hora_fin', $configuracion->hora_fin ?: '18:00') }}" 
                                           required>
                                    @error('hora_fin')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('dian.dashboard') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Guardar Configuración
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Información de Ayuda -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle"></i> Información Importante</h6>
                <ul class="mb-0">
                    <li><strong>Email DIAN:</strong> Debe ser el email asociado a su cuenta DIAN donde recibe las facturas de proveedores</li>
                    <li><strong>Contraseña:</strong> Use la contraseña de aplicación si tiene autenticación de dos factores activada</li>
                    <li><strong>Procesamiento:</strong> El sistema buscará automáticamente emails con facturas electrónicas</li>
                    <li><strong>Acuses:</strong> Se enviarán automáticamente después de procesar cada factura exitosamente</li>
                    <li><strong>Horario:</strong> El procesamiento solo ocurrirá dentro del horario configurado</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function togglePassword() {
    const passwordField = document.getElementById('password_email');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

function probarConexion() {
    const resultadoDiv = document.getElementById('resultado-conexion');
    const btn = event.target;
    
    // Deshabilitar botón
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Probando...';
    
    // Obtener datos del formulario
    const formData = new FormData();
    formData.append('_token', document.querySelector('input[name="_token"]').value);
    formData.append('email_dian', document.getElementById('email_dian').value);
    formData.append('password_email', document.getElementById('password_email').value);
    formData.append('servidor_imap', document.getElementById('servidor_imap').value);
    formData.append('puerto_imap', document.getElementById('puerto_imap').value);
    formData.append('ssl_enabled', document.getElementById('ssl_enabled').checked ? '1' : '0');
    
    fetch('{{ route("dian.probar-conexion") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        resultadoDiv.style.display = 'block';
        
        if (data.success) {
            resultadoDiv.innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <strong>Conexión Exitosa</strong><br>
                    ${data.message}
                    ${data.detalles ? `<br><small>Mensajes: ${data.detalles.mensajes}, No leídos: ${data.detalles.no_leidos}</small>` : ''}
                </div>
            `;
        } else {
            resultadoDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle"></i> <strong>Error de Conexión</strong><br>
                    ${data.message}
                </div>
            `;
        }
    })
    .catch(error => {
        resultadoDiv.style.display = 'block';
        resultadoDiv.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-times-circle"></i> <strong>Error</strong><br>
                Error de conexión: ${error.message}
            </div>
        `;
    })
    .finally(() => {
        // Rehabilitar botón
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plug"></i> Probar Conexión';
    });
}

// Función para autocompletar desde Gmail
function autocompletarDesdeGmail() {
    const btn = document.getElementById('btnAutocompletar');
    
    // Deshabilitar botón
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Autocompletando...';
    
    fetch('{{ route("dian.autocompletar-gmail") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Preguntar al usuario si quiere usar la configuración sugerida
            const confirmar = confirm(`Se detectó configuración de email:\n\nEmail: ${data.configuracion.email_dian}\nServidor: ${data.configuracion.servidor_imap}\n\n¿Deseas usar esta configuración sugerida?\n\n(Puedes modificarla después de aceptar)`);
            
            if (confirmar && data.configuracion) {
                // Solo completar campos vacíos o si el usuario confirma
                const emailField = document.getElementById('email_dian');
                const servidorField = document.getElementById('servidor_imap');
                const puertoField = document.getElementById('puerto_imap');
                const sslField = document.getElementById('ssl_enabled');
                const remitenteField = document.getElementById('email_remitente');
                const nombreField = document.getElementById('nombre_remitente');
                
                // Completar solo si están vacíos o el usuario confirma
                if (!emailField.value || confirmar) {
                    emailField.value = data.configuracion.email_dian || '';
                }
                if (!servidorField.value || confirmar) {
                    servidorField.value = data.configuracion.servidor_imap || '';
                }
                if (!puertoField.value || confirmar) {
                    puertoField.value = data.configuracion.puerto_imap || '';
                }
                if (confirmar) {
                    sslField.checked = data.configuracion.ssl_enabled || false;
                }
                if (!remitenteField.value || confirmar) {
                    remitenteField.value = data.configuracion.email_remitente || '';
                }
                if (!nombreField.value || confirmar) {
                    nombreField.value = data.configuracion.nombre_remitente || '';
                }
                
                // Resaltar campos completados
                [emailField, servidorField, puertoField, remitenteField, nombreField].forEach(field => {
                    if (field.value) {
                        field.style.backgroundColor = '#e8f5e8';
                        setTimeout(() => {
                            field.style.backgroundColor = '';
                        }, 3000);
                    }
                });
            }
            
            // Mostrar mensaje de éxito
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show';
            alertDiv.innerHTML = `
                <i class="fas fa-check-circle"></i> <strong>¡Éxito!</strong> ${data.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Insertar antes del formulario
            const form = document.querySelector('form');
            form.parentNode.insertBefore(alertDiv, form);
            
            // Ocultar el alert de detección
            const detectionAlert = document.querySelector('.alert-info');
            if (detectionAlert) {
                detectionAlert.style.display = 'none';
            }
            
        } else {
            // Mostrar error
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger alert-dismissible fade show';
            alertDiv.innerHTML = `
                <i class="fas fa-times-circle"></i> <strong>Error:</strong> ${data.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const form = document.querySelector('form');
            form.parentNode.insertBefore(alertDiv, form);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show';
        alertDiv.innerHTML = `
            <i class="fas fa-times-circle"></i> <strong>Error de conexión:</strong> ${error.message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const form = document.querySelector('form');
        form.parentNode.insertBefore(alertDiv, form);
    })
    .finally(() => {
        // Rehabilitar botón
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-magic"></i> Autocompletar Configuración';
    });
}

// Función para configuración rápida
function configurarRapida(servidor, puerto, ssl) {
    // Autocompletar campos del formulario
    document.getElementById('servidor_imap').value = servidor;
    document.getElementById('puerto_imap').value = puerto;
    document.getElementById('ssl_enabled').checked = ssl === 'true';
    
    // Mostrar mensaje de éxito
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show';
    alertDiv.innerHTML = `
        <i class="fas fa-check-circle"></i> <strong>¡Configuración aplicada!</strong> 
        Ahora completa tu email y contraseña.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insertar antes del formulario
    const form = document.querySelector('form');
    form.parentNode.insertBefore(alertDiv, form);
    
    // Enfocar el campo de email
    document.getElementById('email_dian').focus();
    
    // Auto-remover el alert después de 5 segundos
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Botón de autocompletar desde variables de entorno
    const btnAutocompletar = document.getElementById('btnAutocompletar');
    if (btnAutocompletar) {
        btnAutocompletar.addEventListener('click', autocompletarDesdeGmail);
    }
    
    // Botones de configuración rápida
    const botonesConfigRapida = document.querySelectorAll('.btn-configurar-rapida');
    botonesConfigRapida.forEach(btn => {
        btn.addEventListener('click', function() {
            const servidor = this.getAttribute('data-servidor');
            const puerto = this.getAttribute('data-puerto');
            const ssl = this.getAttribute('data-ssl');
            
            configurarRapida(servidor, puerto, ssl);
        });
    });
});
</script>
@endpush
