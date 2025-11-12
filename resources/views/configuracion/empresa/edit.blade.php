<!-- resources/views/configuracion/empresa/edit.blade.php -->
@extends('layouts.app')

@php
use Illuminate\Support\Str;
@endphp

@section('title', 'Editar Empresa')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Editar Información de la Empresa</h5>
                <a href="{{ route('empresa.index') }}" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <div class="card-body">
            <form action="{{ route('empresa.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row">
                    <!-- Logo -->
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-image"></i> Logo de la Empresa</h6>
                            </div>
                            <div class="card-body text-center">
                                <div class="mb-3 p-3" style="background-color: #f8f9fa; border-radius: 8px; min-height: 150px; display: flex; align-items: center; justify-content: center;">
                                    @if($empresa->logo)
                                        <img src="{{ asset('storage/' . $empresa->logo) }}" 
                                             id="preview" 
                                             class="img-fluid"
                                             style="max-width: 250px; max-height: 120px; object-fit: contain;">
                                    @else
                                        <div id="no-logo-placeholder" class="text-muted">
                                            <i class="fas fa-image fa-3x mb-2"></i>
                                            <p>No hay logo configurado</p>
                                        </div>
                                        <img id="preview" 
                                             class="d-none img-fluid" 
                                             style="max-width: 250px; max-height: 120px; object-fit: contain;">
                                    @endif
                                </div>
                                <div class="input-group mb-2">
                                    <input type="file" 
                                           class="form-control @error('logo') is-invalid @enderror" 
                                           id="logoInput"
                                           name="logo" 
                                           accept="image/*"
                                           onchange="previewImage(this);">
                                    <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('logoInput').click();">
                                        <i class="fas fa-upload"></i> Seleccionar
                                    </button>
                                    @error('logo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> 
                                    <strong>Formatos:</strong> JPG, PNG, SVG | 
                                    <strong>Tamaño máximo:</strong> 1MB | 
                                    <strong>Recomendado:</strong> 250x100 px (horizontal)
                                </small>
                                @if($empresa->logo)
                                    <div class="mt-2">
                                        <small class="text-success">
                                            <i class="fas fa-check-circle"></i> 
                                            Logo actual: <code>{{ basename($empresa->logo) }}</code>
                                        </small>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Configuración de Impresión -->
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="fas fa-print"></i> Configuración de Impresión</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label required">
                                            <i class="fas fa-receipt"></i> Formato de Impresión Predeterminado
                                        </label>
                                        <select name="formato_impresion" 
                                                class="form-select @error('formato_impresion') is-invalid @enderror" 
                                                required>
                                            <option value="58mm" {{ old('formato_impresion', $empresa->formato_impresion) === '58mm' ? 'selected' : '' }}>
                                                📄 Ticket 58mm (Impresora térmica pequeña)
                                            </option>
                                            <option value="80mm" {{ old('formato_impresion', $empresa->formato_impresion ?? '80mm') === '80mm' ? 'selected' : '' }}>
                                                📄 Ticket 80mm (Impresora térmica estándar)
                                            </option>
                                            <option value="media_carta" {{ old('formato_impresion', $empresa->formato_impresion) === 'media_carta' ? 'selected' : '' }}>
                                                📋 Media Carta (Formato A5 / 5.5" x 8.5")
                                            </option>
                                        </select>
                                        @error('formato_impresion')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle"></i> 
                                            Este formato se usará por defecto al imprimir facturas. 
                                            Puedes cambiar el formato manualmente en cada factura si lo necesitas.
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="alert alert-info">
                                            <h6 class="alert-heading"><i class="fas fa-lightbulb"></i> Guía de Formatos:</h6>
                                            <ul class="mb-0" style="font-size: 0.875rem;">
                                                <li><strong>58mm:</strong> Impresoras térmicas compactas (POS pequeño)</li>
                                                <li><strong>80mm:</strong> Impresoras térmicas estándar (Más común)</li>
                                                <li><strong>Media Carta:</strong> Impresoras láser o inyección de tinta</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Configuración de QR Local -->
                    <div class="col-md-12 mb-4">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="fas fa-qrcode"></i> QR y CUFE Local para Facturas Normales</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch" style="font-size: 1.1rem;">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   name="generar_qr_local" 
                                                   id="generar_qr_local"
                                                   value="1"
                                                   {{ old('generar_qr_local', $empresa->generar_qr_local) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="generar_qr_local">
                                                <strong>Generar QR y CUFE simulado en facturas locales</strong>
                                            </label>
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle"></i> 
                                            Cuando está activado, todas las facturas normales (no electrónicas) 
                                            incluirán automáticamente un código QR y un CUFE simulado en la impresión.
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="alert alert-success mb-0">
                                            <h6 class="alert-heading"><i class="fas fa-check-circle"></i> Beneficios del QR Local:</h6>
                                            <ul class="mb-0" style="font-size: 0.875rem;">
                                                <li><strong>Verificación rápida:</strong> Escanea y verifica datos de la factura</li>
                                                <li><strong>Apariencia profesional:</strong> Aspecto similar a factura electrónica</li>
                                                <li><strong>Trazabilidad:</strong> CUFE único para cada factura</li>
                                                <li><strong>Sin costos adicionales:</strong> Generado localmente</li>
                                            </ul>
                                            <hr>
                                            <p class="mb-0" style="font-size: 0.75rem;">
                                                <strong>Nota:</strong> Este QR es solo informativo. 
                                                NO es un QR oficial de DIAN y solo aplica a facturas locales.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resto de campos igual que en create pero con value="{{ old('campo', $empresa->campo) }}" -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label required">Nombre Comercial</label>
                            <input type="text" 
                                   class="form-control @error('nombre_comercial') is-invalid @enderror" 
                                   name="nombre_comercial" 
                                   value="{{ old('nombre_comercial', $empresa->nombre_comercial) }}" 
                                   required>
                            @error('nombre_comercial')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Régimen Tributario</label>
                        <select name="regimen_tributario" class="form-select @error('regimen_tributario') is-invalid @enderror" required>
                            <option value="no_responsable_iva" {{ $empresa->regimen_tributario === 'no_responsable_iva' ? 'selected' : '' }}>
                                No Responsable de IVA
                            </option>
                            <option value="responsable_iva" {{ $empresa->regimen_tributario === 'responsable_iva' ? 'selected' : '' }}>
                                Responsable de IVA
                            </option>
                            <option value="regimen_simple" {{ $empresa->regimen_tributario === 'regimen_simple' ? 'selected' : '' }}>
                                Régimen Simple de Tributación
                            </option>
                        </select>
                        @error('regimen_tributario')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Sección de Facturación Electrónica -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">Configuración de Facturación Electrónica</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="resolucion_facturacion" class="form-label">Resolución de Facturación</label>
                                <input type="text" class="form-control @error('resolucion_facturacion') is-invalid @enderror" 
                                       id="resolucion_facturacion" name="resolucion_facturacion" 
                                       value="{{ old('resolucion_facturacion', $empresa->resolucion_facturacion) }}" readonly>
                                <div class="form-text">
                                    Este campo se completará automáticamente al guardar si se configuran las credenciales de Alegra.
                                </div>
                                @error('resolucion_facturacion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha de Resolución</label>
                                <input type="date" 
                                       name="fecha_resolucion" 
                                       id="fecha_resolucion"
                                       class="form-control @error('fecha_resolucion') is-invalid @enderror"
                                       value="{{ old('fecha_resolucion', $empresa->fecha_resolucion?->format('Y-m-d')) }}" readonly>
                                @error('fecha_resolucion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha de Vencimiento de Resolución</label>
                                <input type="date" 
                                       name="fecha_vencimiento_resolucion" 
                                       id="fecha_vencimiento_resolucion"
                                       class="form-control @error('fecha_vencimiento_resolucion') is-invalid @enderror"
                                       value="{{ old('fecha_vencimiento_resolucion', $empresa->fecha_vencimiento_resolucion?->format('Y-m-d')) }}" readonly>
                                <div class="form-text">
                                    Fecha de vencimiento de la resolución de facturación electrónica.
                                </div>
                                @error('fecha_vencimiento_resolucion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Prefijo de Facturación</label>
                                <input type="text" 
                                       name="prefijo_factura" 
                                       id="prefijo_factura"
                                       class="form-control"
                                       value="{{ old('prefijo_factura', $empresa->prefijo_factura) }}">
                                <div class="form-text">
                                    Este campo se completará automáticamente al probar la conexión con Alegra, pero también puede editarlo manualmente.
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">ID Resolución Alegra</label>
                                <input type="text" 
                                       name="id_resolucion_alegra" 
                                       id="id_resolucion_alegra"
                                       class="form-control"
                                       value="{{ old('id_resolucion_alegra', $empresa->id_resolucion_alegra) }}">
                                <div class="form-text">
                                    Este campo se completará automáticamente al probar la conexión con Alegra, pero también puede editarlo manualmente.
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Número de Resolución DIAN</label>
                                <input type="text" 
                                       name="numero_resolucion" 
                                       id="numero_resolucion"
                                       class="form-control"
                                       value="{{ old('numero_resolucion', $empresa->resolucion_facturacion ? (is_string($empresa->resolucion_facturacion) && Str::startsWith($empresa->resolucion_facturacion, '{') ? json_decode($empresa->resolucion_facturacion, true)['numero_resolucion'] ?? '' : '') : '') }}">
                                <div class="form-text">
                                    Número de resolución DIAN para la facturación electrónica.
                                </div>
                            </div>

                            <div class="col-12">
                                <div id="conexion-result" class="alert alert-info d-none" role="alert">
                                </div>
                                <div id="conexion-loading" class="d-none">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                    <span class="ms-2">Verificando conexión con Alegra...</span>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Correo Electrónico Alegra</label>
                                <input type="email" 
                                       name="alegra_email" 
                                       class="form-control @error('alegra_email') is-invalid @enderror"
                                       value="{{ old('alegra_email', $empresa->alegra_email) }}"
                                       placeholder="Correo registrado en Alegra">
                                @error('alegra_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Correo electrónico utilizado para la autenticación en Alegra</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Token de API Alegra</label>
                                <input type="password" 
                                       name="alegra_token" 
                                       class="form-control @error('alegra_token') is-invalid @enderror"
                                       value="{{ old('alegra_token', $empresa->alegra_token) }}"
                                       placeholder="Token de API de Alegra">
                                @error('alegra_token')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Token de API generado en la configuración de tu cuenta Alegra</small>
                            </div>

                            <div class="col-md-12 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <button type="button" class="btn btn-info" onclick="probarConexion()">
                                            <i class="fas fa-sync"></i> Probar Conexión con Alegra
                                        </button>
                                        <a href="{{ route('empresa.sincronizar-alegra', $empresa) }}" class="btn btn-success ms-2">
                                            <i class="fas fa-download"></i> Importar Datos de Alegra
                                        </a>
                                    </div>
                                    <div class="form-text">
                                        Al probar la conexión se completarán automáticamente los campos de resolución.
                                    </div>
                                </div>
                                
                                <!-- Campos ocultos para la prueba de conexión -->
                                <input type="hidden" id="alegra_email_copy" name="alegra_email_copy">
                                <input type="hidden" id="alegra_token_copy" name="alegra_token_copy">
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" 
                                           name="factura_electronica_habilitada" 
                                           class="form-check-input" 
                                           value="1" 
                                           {{ old('factura_electronica_habilitada', $empresa->factura_electronica_habilitada) ? 'checked' : '' }}>
                                    <label class="form-check-label">
                                        Habilitar Facturación Electrónica
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary" id="btnGuardar">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <a href="{{ route('empresa.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Previsualizar la imagen seleccionada
function previewImage(input) {
    if (input.files && input.files[0]) {
        // Verificar tamaño del archivo (1MB = 1048576 bytes)
        if (input.files[0].size > 1048576) {
            Swal.fire({
                icon: 'error',
                title: 'Archivo muy grande',
                text: 'El logo no debe superar 1 MB. Por favor, selecciona un archivo más pequeño.',
            });
            input.value = '';
            return;
        }
        
        var reader = new FileReader();
        reader.onload = function(e) {
            // Ocultar placeholder si existe
            $('#no-logo-placeholder').addClass('d-none');
            // Mostrar preview
            $('#preview').attr('src', e.target.result)
                        .removeClass('d-none')
                        .css({
                            'max-width': '250px',
                            'max-height': '120px',
                            'object-fit': 'contain'
                        });
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Depuración del formulario
$(document).ready(function() {
    $('form').on('submit', function(e) {
        // Mostrar los datos del formulario en la consola para depuración
        console.log('Datos del formulario:');
        var formData = {};
        $(this).serializeArray().forEach(function(item) {
            formData[item.name] = item.value;
        });
        console.log(formData);
        
        // Continuar con el envío normal del formulario
    });
});

function probarConexion() {
    // Mostrar indicador de carga
    $('#conexion-result').removeClass('d-none alert-success alert-danger').addClass('alert-info').html('<i class="fas fa-spinner fa-spin"></i> Probando conexión con Alegra...');
    $('#conexion-loading').removeClass('d-none');
    
    // Usar el script de prueba en la carpeta pública
    $.ajax({
        url: '{{ url("/test_alegra.php") }}',
        method: 'GET',
        success: function(response) {
            console.log('Respuesta recibida:', response);
            
            // Ocultar indicador de carga
            $('#conexion-loading').addClass('d-none');
            
            // Limpiar el indicador de carga
            $('#conexion-result').removeClass('alert-info');
            
            // Mostrar el resultado
            if (response.success) {
                $('#conexion-result').removeClass('d-none').addClass('alert-success').html('<i class="fas fa-check-circle"></i> ' + response.message);
                
                // Actualizar los campos de email y token con las credenciales correctas
                $('input[name="alegra_email"]').val('{{ $empresa->alegra_email ?? config("alegra.user") }}');
                $('input[name="alegra_token"]').val('{{ $empresa->alegra_token ?? config("alegra.token") }}');
            } else {
                $('#conexion-result').removeClass('d-none').addClass('alert-danger').html('<i class="fas fa-times-circle"></i> ' + response.message);
            }
            
            // Si la conexión fue exitosa y hay datos de resolución
            if (response.success && response.resolucion) {
                // Actualizar campos de resolución
                $('#resolucion_facturacion').val(response.resolucion.texto);
                $('#prefijo_factura').val(response.resolucion.prefijo);
                $('#id_resolucion_alegra').val(response.resolucion.id);
                
                // Actualizar fechas si están disponibles
                if (response.resolucion.fecha_inicio && response.resolucion.fecha_inicio !== 'No disponible') {
                    // Convertir fecha de formato dd/mm/yyyy a yyyy-mm-dd para el input date
                    var fechaInicio = convertirFechaAFormatoInput(response.resolucion.fecha_inicio);
                    $('#fecha_resolucion').val(fechaInicio);
                }
                
                if (response.resolucion.fecha_fin && response.resolucion.fecha_fin !== 'No disponible') {
                    // Convertir fecha de formato dd/mm/yyyy a yyyy-mm-dd para el input date
                    var fechaFin = convertirFechaAFormatoInput(response.resolucion.fecha_fin);
                    $('#fecha_vencimiento_resolucion').val(fechaFin);
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Error en la solicitud:', xhr.responseText);
            
            // Ocultar indicador de carga
            $('#conexion-loading').addClass('d-none');
            
            // Mostrar error
            $('#conexion-result').removeClass('alert-info d-none').addClass('alert-danger').html('<i class="fas fa-times-circle"></i> Error al conectar con el servidor: ' + error);
            
            // Intentar con la ruta del controlador como fallback
            console.log('Intentando con la ruta del controlador como fallback...');
            $.ajax({
                url: '{{ route("empresa.probar_conexion") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    alegra_email: '{{ $empresa->alegra_email ?? config("alegra.user") }}',
                    alegra_token: '{{ $empresa->alegra_token ?? config("alegra.token") }}',
                    solo_verificar: 'true'
                },
                success: function(response) {
                    console.log('Respuesta recibida del fallback:', response);
                    
                    // Mostrar el resultado
                    if (response.success) {
                        $('#conexion-result').removeClass('alert-danger d-none').addClass('alert-success').html('<i class="fas fa-check-circle"></i> ' + response.message);
                        
                        // Actualizar los campos de email y token con las credenciales correctas
                        $('input[name="alegra_email"]').val('{{ $empresa->alegra_email ?? config("alegra.user") }}');
                        $('input[name="alegra_token"]').val('{{ $empresa->alegra_token ?? config("alegra.token") }}');
                        
                        // Si hay datos de resolución, actualizarlos
                        if (response.resolucion) {
                            $('#resolucion_facturacion').val(response.resolucion.texto);
                            $('#prefijo_factura').val(response.resolucion.prefijo);
                            $('#id_resolucion_alegra').val(response.resolucion.id);
                            
                            // Actualizar fechas si están disponibles
                            if (response.resolucion.fecha_inicio && response.resolucion.fecha_inicio !== 'No disponible') {
                                var fechaInicio = convertirFechaAFormatoInput(response.resolucion.fecha_inicio);
                                $('#fecha_resolucion').val(fechaInicio);
                            }
                            
                            if (response.resolucion.fecha_fin && response.resolucion.fecha_fin !== 'No disponible') {
                                var fechaFin = convertirFechaAFormatoInput(response.resolucion.fecha_fin);
                                $('#fecha_vencimiento_resolucion').val(fechaFin);
                            }
                        }
                    } else {
                        $('#conexion-result').removeClass('d-none').addClass('alert-danger').html('<i class="fas fa-times-circle"></i> ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error en la solicitud de fallback:', xhr.responseText);
                    $('#conexion-result').removeClass('alert-info d-none').addClass('alert-danger').html('<i class="fas fa-times-circle"></i> Error al conectar con el servidor (fallback): ' + error);
                }
            });
        }
    });
}

// Función para convertir fecha de formato dd/mm/yyyy a yyyy-mm-dd
function convertirFechaAFormatoInput(fecha) {
    if (!fecha) return '';
    
    var partes = fecha.split('/');
    if (partes.length !== 3) return fecha;
    
    return partes[2] + '-' + partes[1] + '-' + partes[0];
}

$(document).ready(function() {
    const $checkboxFE = $('input[name="factura_electronica_habilitada"]');
    const $alegraEmail = $('input[name="alegra_email"]');
    const $alegraToken = $('input[name="alegra_token"]');

    // Verificar estado inicial
    if ($checkboxFE.is(':checked')) {
        $alegraEmail.prop('required', true);
        $alegraToken.prop('required', true);
    }

    // Manejar el cambio en el checkbox
    $checkboxFE.change(function() {
        if ($(this).is(':checked')) {
            // Hacer los campos obligatorios
            $alegraEmail.prop('required', true);
            $alegraToken.prop('required', true);

            // Verificar si están vacíos
            if (!$alegraEmail.val() || !$alegraToken.val()) {
                Swal.fire({
                    title: 'Campos Requeridos',
                    text: 'Para habilitar la facturación electrónica debe ingresar el correo electrónico y token de API de Alegra',
                    icon: 'warning'
                });
                $(this).prop('checked', false);
            }
        } else {
            // Quitar obligatoriedad
            $alegraEmail.prop('required', false);
            $alegraToken.prop('required', false);
        }
    });
    
    // Asegurar que el formulario se envíe correctamente
    $('form').on('submit', function(e) {
        // Validar campos requeridos
        if ($checkboxFE.is(':checked') && (!$alegraEmail.val() || !$alegraToken.val())) {
            e.preventDefault();
            Swal.fire({
                title: 'Campos Requeridos',
                text: 'Para habilitar la facturación electrónica debe ingresar el correo electrónico y token de API de Alegra',
                icon: 'warning'
            });
            return false;
        }
        
        // Continuar con el envío del formulario
        return true;
    });
});
</script>
@endpush