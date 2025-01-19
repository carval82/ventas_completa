@extends('layouts.app')

@section('title', isset($planCuenta) ? 'Editar Cuenta' : 'Nueva Cuenta')

@section('styles')
<style>
    .required::after {
        content: ' *';
        color: red;
    }
    .cuenta-padre-info {
        background: #f8f9fa;
        border-radius: 4px;
        padding: 10px;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-{{ isset($planCuenta) ? 'edit' : 'plus' }}"></i>
                        {{ isset($planCuenta) ? 'Editar Cuenta' : 'Nueva Cuenta' }}
                    </h5>
                </div>

                <div class="card-body">
                    <form action="{{ isset($planCuenta) ? route('plan-cuentas.update', $planCuenta) : route('plan-cuentas.store') }}"
                          method="POST"
                          id="cuentaForm">
                        @csrf
                        @if(isset($planCuenta))
                            @method('PUT')
                        @endif

                        <div class="row">
                            <!-- Cuenta Padre -->
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Cuenta Padre</label>
                                <select name="cuenta_padre_id" 
                                        class="form-select" 
                                        id="cuentaPadre">
                                    <option value="">Ninguna (Cuenta Principal)</option>
                                    @foreach($cuentasPadre as $cuentaPadre)
                                        <option value="{{ $cuentaPadre->id }}"
                                                data-tipo="{{ $cuentaPadre->tipo }}"
                                                data-nivel="{{ $cuentaPadre->nivel }}"
                                                data-codigo="{{ $cuentaPadre->codigo }}"
                                                {{ old('cuenta_padre_id', $planCuenta->cuenta_padre_id ?? '') == $cuentaPadre->id ? 'selected' : '' }}>
                                            {{ $cuentaPadre->codigo }} - {{ $cuentaPadre->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Info Cuenta Padre -->
                            <div class="col-md-12 mb-3 cuenta-padre-info" id="cuentaPadreInfo" style="display: none;">
                                <h6 class="mb-2">Información de Cuenta Padre:</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>Tipo:</strong> <span id="tipoPadre"></span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Nivel:</strong> <span id="nivelPadre"></span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Código Base:</strong> <span id="codigoPadre"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Código -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Código</label>
                                <input type="text" 
                                       name="codigo" 
                                       class="form-control @error('codigo') is-invalid @enderror"
                                       value="{{ old('codigo', $planCuenta->codigo ?? '') }}"
                                       required>
                                @error('codigo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Tipo -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Tipo</label>
                                <select name="tipo" 
                                        class="form-select @error('tipo') is-invalid @enderror"
                                        required>
                                    <option value="">Seleccione...</option>
                                    <option value="Activo" {{ old('tipo', $planCuenta->tipo ?? '') == 'Activo' ? 'selected' : '' }}>Activo</option>
                                    <option value="Pasivo" {{ old('tipo', $planCuenta->tipo ?? '') == 'Pasivo' ? 'selected' : '' }}>Pasivo</option>
                                    <option value="Patrimonio" {{ old('tipo', $planCuenta->tipo ?? '') == 'Patrimonio' ? 'selected' : '' }}>Patrimonio</option>
                                    <option value="Ingreso" {{ old('tipo', $planCuenta->tipo ?? '') == 'Ingreso' ? 'selected' : '' }}>Ingreso</option>
                                    <option value="Gasto" {{ old('tipo', $planCuenta->tipo ?? '') == 'Gasto' ? 'selected' : '' }}>Gasto</option>
                                </select>
                                @error('tipo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Nombre -->
                            <div class="col-md-8 mb-3">
                                <label class="form-label required">Nombre</label>
                                <input type="text" 
                                       name="nombre" 
                                       class="form-control @error('nombre') is-invalid @enderror"
                                       value="{{ old('nombre', $planCuenta->nombre ?? '') }}"
                                       required>
                                @error('nombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Nivel -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label required">Nivel</label>
                                <input type="number" 
                                       name="nivel" 
                                       class="form-control @error('nivel') is-invalid @enderror"
                                       value="{{ old('nivel', $planCuenta->nivel ?? 1) }}"
                                       min="1"
                                       required>
                                @error('nivel')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Estado -->
                            <div class="col-md-12 mb-3">
                                <div class="form-check">
                                    <input type="checkbox" 
                                           class="form-check-input" 
                                           name="estado" 
                                           id="estado"
                                           value="1"
                                           {{ old('estado', $planCuenta->estado ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="estado">
                                        Cuenta Activa
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <a href="{{ route('plan-cuentas.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Manejar cambio de cuenta padre
    $('#cuentaPadre').change(function() {
        var selected = $(this).find('option:selected');
        
        if (selected.val()) {
            $('#tipoPadre').text(selected.data('tipo'));
            $('#nivelPadre').text(selected.data('nivel'));
            $('#codigoPadre').text(selected.data('codigo'));
            $('#cuentaPadreInfo').show();
            
            // Actualizar tipo y nivel según cuenta padre
            $('select[name="tipo"]').val(selected.data('tipo'));
            $('input[name="nivel"]').val(parseInt(selected.data('nivel')) + 1);
            
            // Sugerir código basado en cuenta padre
            var codigoPadre = selected.data('codigo');
            if (!$('input[name="codigo"]').val()) {
                $('input[name="codigo"]').val(codigoPadre + '.');
            }
        } else {
            $('#cuentaPadreInfo').hide();
            $('input[name="nivel"]').val(1);
        }
    });

    // Disparar cambio inicial si hay cuenta padre seleccionada
    if ($('#cuentaPadre').val()) {
        $('#cuentaPadre').trigger('change');
    }

    // Validaciones adicionales antes de enviar
    $('#cuentaForm').submit(function(e) {
        var codigo = $('input[name="codigo"]').val();
        var nivel = parseInt($('input[name="nivel"]').val());
        
        // Verificar formato del código según nivel
        var partes = codigo.split('.');
        if (partes.length !== nivel) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error de Validación',
                text: 'El código debe tener partes separadas por puntos según el nivel de la cuenta'
            });
        }
    });
});
</script>
@endpush