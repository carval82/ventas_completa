<!-- resources/views/configuracion/empresa/edit.blade.php -->
@extends('layouts.app')

@section('title', 'Editar Empresa')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Editar Empresa</h5>
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

                    <!-- ... Repetir para todos los campos ... -->

                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Actualizar
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