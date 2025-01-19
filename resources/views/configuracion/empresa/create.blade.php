<!-- resources/views/configuracion/empresa/create.blade.php -->
@extends('layouts.app')

@section('title', 'Nueva Empresa')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Registrar Empresa</h5>
                <a href="{{ route('empresa.index') }}" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <div class="card-body">
            <form action="{{ route('empresa.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <!-- Logo -->
                    <div class="col-md-12 mb-4">
                        <div class="text-center">
                            <div class="mb-3">
                                <img id="preview" class="d-none" style="max-width: 200px; max-height: 200px;">
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

                    <!-- Información Básica -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label required">Nombre Comercial</label>
                            <input type="text" 
                                   class="form-control @error('nombre_comercial') is-invalid @enderror" 
                                   name="nombre_comercial" 
                                   value="{{ old('nombre_comercial') }}" 
                                   required>
                            @error('nombre_comercial')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label required">Razón Social</label>
                            <input type="text" 
                                   class="form-control @error('razon_social') is-invalid @enderror" 
                                   name="razon_social" 
                                   value="{{ old('razon_social') }}" 
                                   required>
                            @error('razon_social')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label required">NIT</label>
                            <input type="text" 
                                   class="form-control @error('nit') is-invalid @enderror" 
                                   name="nit" 
                                   value="{{ old('nit') }}" 
                                   required>
                            @error('nit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label required">Teléfono</label>
                            <input type="text" 
                                   class="form-control @error('telefono') is-invalid @enderror" 
                                   name="telefono" 
                                   value="{{ old('telefono') }}" 
                                   required>
                            @error('telefono')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   name="email" 
                                   value="{{ old('email') }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label required">Dirección</label>
                            <input type="text" 
                                   class="form-control @error('direccion') is-invalid @enderror" 
                                   name="direccion" 
                                   value="{{ old('direccion') }}" 
                                   required>
                            @error('direccion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Sitio Web</label>
                            <input type="url" 
                                   class="form-control @error('sitio_web') is-invalid @enderror" 
                                   name="sitio_web" 
                                   value="{{ old('sitio_web') }}">
                            @error('sitio_web')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label required">Régimen Tributario</label>
                            <select class="form-select @error('regimen_tributario') is-invalid @enderror" 
                                    name="regimen_tributario" 
                                    required>
                                <option value="">Seleccione...</option>
                                <option value="comun" {{ old('regimen_tributario') == 'comun' ? 'selected' : '' }}>
                                    Régimen Común
                                </option>
                                <option value="simplificado" {{ old('regimen_tributario') == 'simplificado' ? 'selected' : '' }}>
                                    Régimen Simplificado
                                </option>
                            </select>
                            @error('regimen_tributario')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
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
</script>
@endpush