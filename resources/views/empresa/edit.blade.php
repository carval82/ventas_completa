<div class="card-body">
    <form action="{{ route('empresa.update', $empresa) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        <!-- ... otros campos ... -->

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Régimen Tributario</label>
                <select name="regimen_tributario" class="form-select" required>
                    <option value="no_responsable_iva" {{ $empresa->regimen_tributario === 'no_responsable_iva' ? 'selected' : '' }}>
                        No Responsable de IVA
                    </option>
                    <option value="responsable_iva" {{ $empresa->regimen_tributario === 'responsable_iva' ? 'selected' : '' }}>
                        Responsable de IVA
                    </option>
                    <option value="regimen_simple" {{ $empresa->regimen_tributario === 'regimen_simple' ? 'selected' : '' }}>
                        Régimen Simple
                    </option>
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Resolución de Facturación</label>
                <input type="text" name="resolucion_facturacion" class="form-control" 
                       value="{{ $empresa->resolucion_facturacion }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Fecha de Resolución</label>
                <input type="date" name="fecha_resolucion" class="form-control" 
                       value="{{ $empresa->fecha_resolucion?->format('Y-m-d') }}">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" name="factura_electronica_habilitada" 
                           class="form-check-input" value="1" 
                           {{ $empresa->factura_electronica_habilitada ? 'checked' : '' }}>
                    <label class="form-check-label">
                        Facturación Electrónica Habilitada
                    </label>
                </div>
            </div>
        </div>

        <!-- Sección de Credenciales de Alegra -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Credenciales de Alegra</h5>
                <p class="text-muted small">Estas credenciales son necesarias para la facturación electrónica</p>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Email de Alegra</label>
                        <input type="email" name="alegra_email" class="form-control" 
                               value="{{ $empresa->alegra_email }}" id="main_alegra_email">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Token de Alegra</label>
                        <input type="password" name="alegra_token" class="form-control" 
                               value="{{ $empresa->alegra_token }}" id="main_alegra_token">
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="d-inline">
                            <input type="hidden" name="alegra_email_copy" id="alegra_email_copy">
                            <input type="hidden" name="alegra_token_copy" id="alegra_token_copy">
                            <button type="button" class="btn btn-info" onclick="probarConexion()">
                                <i class="fas fa-plug"></i> Probar Conexión con Alegra
                            </button>
                        </div>
                        <small class="d-block mt-2 text-muted">
                            <i class="fas fa-info-circle"></i> Este botón verificará si las credenciales de Alegra son válidas sin guardar el resto del formulario.
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
                <a href="{{ route('empresa.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </div>
    </form>
</div> 

<script>
function copyCredentials() {
    const alegraEmail = document.getElementById('main_alegra_email').value;
    const alegraToken = document.getElementById('main_alegra_token').value;
    document.getElementById('alegra_email_copy').value = alegraEmail;
    document.getElementById('alegra_token_copy').value = alegraToken;
}

function probarConexion() {
    // Copiar las credenciales actuales
    copyCredentials();
    
    // Obtener los valores
    const alegraEmail = document.getElementById('alegra_email_copy').value;
    const alegraToken = document.getElementById('alegra_token_copy').value;
    
    // Validar que se hayan ingresado credenciales
    if (!alegraEmail || !alegraToken) {
        alert('Por favor ingrese el email y token de Alegra para probar la conexión');
        return;
    }
    
    // Mostrar indicador de carga
    const btnProbar = document.querySelector('button[onclick="probarConexion()"]');
    const textoOriginal = btnProbar.innerHTML;
    btnProbar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Probando...';
    btnProbar.disabled = true;
    
    // Crear formulario para enviar
    const formData = new FormData();
    formData.append('alegra_email', alegraEmail);
    formData.append('alegra_token', alegraToken);
    formData.append('_token', '{{ csrf_token() }}');
    
    // Enviar solicitud AJAX
    fetch('{{ route('empresa.probar-conexion', $empresa) }}', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la solicitud');
        }
        return response.text();
    })
    .then(html => {
        // Redirigir a la misma página para mostrar los mensajes flash
        window.location.href = '{{ route('empresa.edit', $empresa) }}';
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al probar la conexión: ' + error.message);
        
        // Restaurar el botón
        btnProbar.innerHTML = textoOriginal;
        btnProbar.disabled = false;
    });
}
</script>