<!-- resources/views/configuracion/empresa/edit.blade.php -->
@extends('layouts.app')

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
            <form action="{{ route('empresa.update', $empresa) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row">
                    <!-- Logo -->
                    <div class="col-md-12 mb-4">
                        <div class="text-center">
                            <div class="mb-3">
                                @if($empresa->logo)
                                <img src="{{ asset('images/logo.png') }}" 
                                         id="preview" 
                                         class="img-fluid"
                                         style="max-width: 200px; max-height: 200px;">
                                @else
                                    <img id="preview" 
                                         class="d-none" 
                                         style="max-width: 200px; max-height: 200px;">
                                @endif
                            </div>
                            <div class="input-group">
                                <input type="file" 
                                       class="form-control @error('logo') is-invalid @enderror" 
                                       name="logo" 
                                       accept="image/*"
                                       onchange="previewImage(this);">
                                @error('logo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="text-muted">Formato: JPG, PNG. Tamaño máximo: 1MB</small>
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
                                <label class="form-label">Resolución de Facturación</label>
                                <input type="text" 
                                       name="resolucion_facturacion" 
                                       class="form-control @error('resolucion_facturacion') is-invalid @enderror"
                                       value="{{ old('resolucion_facturacion', $empresa->resolucion_facturacion) }}">
                                @error('resolucion_facturacion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha de Resolución</label>
                                <input type="date" 
                                       name="fecha_resolucion" 
                                       class="form-control @error('fecha_resolucion') is-invalid @enderror"
                                       value="{{ old('fecha_resolucion', $empresa->fecha_resolucion?->format('Y-m-d')) }}">
                                @error('fecha_resolucion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
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
                    <button type="submit" class="btn btn-primary">
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
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        
        reader.onload = function(e) {
            $('#preview').attr('src', e.target.result).removeClass('d-none');
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

$(document).ready(function() {
    const $checkboxFE = $('input[name="factura_electronica_habilitada"]');
    const $resolucion = $('input[name="resolucion_facturacion"]');
    const $fecha = $('input[name="fecha_resolucion"]');

    // Verificar estado inicial
    if ($checkboxFE.is(':checked')) {
        $resolucion.prop('required', true);
        $fecha.prop('required', true);
    }

    // Manejar el cambio en el checkbox
    $checkboxFE.change(function() {
        if ($(this).is(':checked')) {
            // Hacer los campos obligatorios
            $resolucion.prop('required', true);
            $fecha.prop('required', true);

            // Verificar si están vacíos
            if (!$resolucion.val() || !$fecha.val()) {
                Swal.fire({
                    title: 'Campos Requeridos',
                    text: 'Para habilitar la facturación electrónica debe ingresar la resolución y su fecha',
                    icon: 'warning'
                });
                $(this).prop('checked', false);
                $resolucion.prop('required', false);
                $fecha.prop('required', false);
            }
        } else {
            // Quitar obligatoriedad si se desmarca
            $resolucion.prop('required', false);
            $fecha.prop('required', false);
        }
    });

    // Validar antes de enviar el formulario
    $('form').submit(function(e) {
        if ($checkboxFE.is(':checked')) {
            if (!$resolucion.val() || !$fecha.val()) {
                e.preventDefault();
                Swal.fire({
                    title: 'Error',
                    text: 'Debe completar la resolución y fecha para habilitar facturación electrónica',
                    icon: 'error'
                });
            }
        }
    });
});
</script>
@endpush