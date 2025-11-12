@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-plus-circle"></i>
                        Nueva Configuración de Email
                    </h4>
                    <a href="{{ route('email-configurations.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>

                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Por favor corrige los siguientes errores:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('email-configurations.store') }}">
                        @csrf
                        
                        <!-- Información Básica -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Información Básica</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="nombre" class="form-label">Nombre de la Configuración *</label>
                                            <input type="text" class="form-control @error('nombre') is-invalid @enderror" 
                                                   id="nombre" name="nombre" value="{{ old('nombre') }}" required
                                                   placeholder="Ej: SendGrid Principal">
                                            @error('nombre')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="proveedor" class="form-label">Proveedor de Email *</label>
                                            <select class="form-select @error('proveedor') is-invalid @enderror" 
                                                    id="proveedor" name="proveedor" required onchange="toggleProviderFields()">
                                                <option value="">Seleccionar proveedor...</option>
                                                <option value="sendgrid" {{ old('proveedor') == 'sendgrid' ? 'selected' : '' }}>
                                                    SendGrid (Recomendado)
                                                </option>
                                                <option value="smtp" {{ old('proveedor') == 'smtp' ? 'selected' : '' }}>
                                                    SMTP Personalizado
                                                </option>
                                                <option value="mailgun" {{ old('proveedor') == 'mailgun' ? 'selected' : '' }}>
                                                    Mailgun
                                                </option>
                                                <option value="ses" {{ old('proveedor') == 'ses' ? 'selected' : '' }}>
                                                    Amazon SES
                                                </option>
                                                <option value="postmark" {{ old('proveedor') == 'postmark' ? 'selected' : '' }}>
                                                    Postmark
                                                </option>
                                            </select>
                                            @error('proveedor')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="from_address" class="form-label">Email Remitente *</label>
                                            <input type="email" class="form-control @error('from_address') is-invalid @enderror" 
                                                   id="from_address" name="from_address" value="{{ old('from_address') }}" required
                                                   placeholder="sistema@tuempresa.com">
                                            @error('from_address')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">
                                                Este email debe estar verificado en tu proveedor de email
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="from_name" class="form-label">Nombre Remitente *</label>
                                            <input type="text" class="form-control @error('from_name') is-invalid @enderror" 
                                                   id="from_name" name="from_name" value="{{ old('from_name') }}" required
                                                   placeholder="Sistema DIAN">
                                            @error('from_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <!-- Configuración SMTP -->
                                <div class="card mb-4" id="smtp-config" style="display: none;">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-server"></i> Configuración SMTP</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="host" class="form-label">Servidor SMTP</label>
                                            <input type="text" class="form-control @error('host') is-invalid @enderror" 
                                                   id="host" name="host" value="{{ old('host') }}"
                                                   placeholder="smtp.gmail.com">
                                            @error('host')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="port" class="form-label">Puerto</label>
                                                    <input type="number" class="form-control @error('port') is-invalid @enderror" 
                                                           id="port" name="port" value="{{ old('port', 587) }}"
                                                           placeholder="587">
                                                    @error('port')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="encryption" class="form-label">Encriptación</label>
                                                    <select class="form-select @error('encryption') is-invalid @enderror" 
                                                            id="encryption" name="encryption">
                                                        <option value="tls" {{ old('encryption', 'tls') == 'tls' ? 'selected' : '' }}>TLS</option>
                                                        <option value="ssl" {{ old('encryption') == 'ssl' ? 'selected' : '' }}>SSL</option>
                                                        <option value="none" {{ old('encryption') == 'none' ? 'selected' : '' }}>Ninguna</option>
                                                    </select>
                                                    @error('encryption')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="username" class="form-label">Usuario SMTP</label>
                                            <input type="text" class="form-control @error('username') is-invalid @enderror" 
                                                   id="username" name="username" value="{{ old('username') }}"
                                                   placeholder="tu-email@gmail.com">
                                            @error('username')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="password" class="form-label">Contraseña SMTP</label>
                                            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                                   id="password" name="password"
                                                   placeholder="Contraseña o App Password">
                                            @error('password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">
                                                Para Gmail, usa una App Password, no tu contraseña normal
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Configuración API -->
                                <div class="card mb-4" id="api-config" style="display: none;">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-key"></i> Configuración API</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="api_key" class="form-label">API Key</label>
                                            <input type="password" class="form-control @error('api_key') is-invalid @enderror" 
                                                   id="api_key" name="api_key" value="{{ old('api_key') }}"
                                                   placeholder="SG.xxxxxxxxxxxxxxxxxx">
                                            @error('api_key')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text" id="api-help">
                                                Obtén tu API Key desde el panel de tu proveedor
                                            </div>
                                        </div>

                                        <div class="alert alert-info" id="sendgrid-help" style="display: none;">
                                            <h6><i class="fas fa-info-circle"></i> Configuración SendGrid:</h6>
                                            <ol class="mb-0">
                                                <li>Ve a <a href="https://sendgrid.com" target="_blank">SendGrid</a></li>
                                                <li>Crea cuenta gratuita (100 emails/día)</li>
                                                <li>Ve a Settings → API Keys</li>
                                                <li>Crea API Key con permisos "Mail Send"</li>
                                                <li>Verifica tu email remitente</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Configuración de Uso -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-cogs"></i> Configuración de Uso</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="limite_diario" class="form-label">Límite Diario de Emails</label>
                                            <input type="number" class="form-control @error('limite_diario') is-invalid @enderror" 
                                                   id="limite_diario" name="limite_diario" value="{{ old('limite_diario') }}"
                                                   placeholder="100" min="1">
                                            @error('limite_diario')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">
                                                Deja vacío para sin límite. SendGrid gratuito: 100/día
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tipos de Email</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="es_backup" name="es_backup" value="1" 
                                                   {{ old('es_backup') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="es_backup">
                                                <i class="fas fa-database"></i> Envío de Backups
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="es_acuses" name="es_acuses" value="1"
                                                   {{ old('es_acuses') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="es_acuses">
                                                <i class="fas fa-receipt"></i> Acuses de Recibo DIAN
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="es_notificaciones" name="es_notificaciones" value="1"
                                                   {{ old('es_notificaciones') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="es_notificaciones">
                                                <i class="fas fa-bell"></i> Notificaciones Generales
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('email-configurations.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Configuración
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function toggleProviderFields() {
    const proveedor = document.getElementById('proveedor').value;
    const smtpConfig = document.getElementById('smtp-config');
    const apiConfig = document.getElementById('api-config');
    const sendgridHelp = document.getElementById('sendgrid-help');
    const apiHelp = document.getElementById('api-help');
    
    // Ocultar todos primero
    smtpConfig.style.display = 'none';
    apiConfig.style.display = 'none';
    sendgridHelp.style.display = 'none';
    
    if (proveedor === 'smtp') {
        smtpConfig.style.display = 'block';
    } else if (['sendgrid', 'mailgun', 'ses', 'postmark'].includes(proveedor)) {
        apiConfig.style.display = 'block';
        
        if (proveedor === 'sendgrid') {
            sendgridHelp.style.display = 'block';
            apiHelp.textContent = 'Tu API Key de SendGrid (empieza con SG.)';
            document.getElementById('api_key').placeholder = 'SG.xxxxxxxxxxxxxxxxxx';
        } else if (proveedor === 'mailgun') {
            apiHelp.textContent = 'Tu API Key de Mailgun';
            document.getElementById('api_key').placeholder = 'key-xxxxxxxxxxxxxxxxxx';
        } else if (proveedor === 'ses') {
            apiHelp.textContent = 'Tu Access Key de Amazon SES';
            document.getElementById('api_key').placeholder = 'AKIA...';
        } else if (proveedor === 'postmark') {
            apiHelp.textContent = 'Tu Server Token de Postmark';
            document.getElementById('api_key').placeholder = 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx';
        }
    }
}

// Ejecutar al cargar la página si hay un valor seleccionado
document.addEventListener('DOMContentLoaded', function() {
    toggleProviderFields();
});
</script>
@endsection
