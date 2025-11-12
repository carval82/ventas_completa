@extends('layouts.app')

@section('title', isset($comprobante) ? 'Editar Comprobante' : 'Nuevo Comprobante')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-file-invoice"></i> 
                {{ isset($comprobante) ? 'Editar Comprobante' : 'Nuevo Comprobante' }}
            </h5>
        </div>

        <div class="card-body">
            <form id="comprobanteForm" action="{{ isset($comprobante) ? route('comprobantes.update', $comprobante) : route('comprobantes.store') }}" method="POST">
                @csrf
                @if(isset($comprobante))
                    @method('PUT')
                @endif

                <!-- Cabecera -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <label class="form-label">Prefijo</label>
                        <input type="text" 
                               class="form-control @error('prefijo') is-invalid @enderror" 
                               name="prefijo" 
                               value="{{ old('prefijo', $comprobante->prefijo ?? '') }}" 
                               placeholder="Ej: COM">
                        @error('prefijo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-2">
                        <label class="form-label required">Número</label>
                        <input type="text" 
                               class="form-control @error('numero') is-invalid @enderror" 
                               name="numero" 
                               value="{{ old('numero', $numero ?? '') }}" 
                               readonly>
                        @error('numero')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label required">Fecha</label>
                        <input type="date" 
                               class="form-control @error('fecha') is-invalid @enderror" 
                               name="fecha" 
                               value="{{ old('fecha', isset($comprobante) ? $comprobante->fecha->format('Y-m-d') : date('Y-m-d')) }}" 
                               required>
                        @error('fecha')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label required">Tipo</label>
                        <select class="form-select @error('tipo') is-invalid @enderror" 
                                name="tipo" 
                                required>
                            <option value="">Seleccione...</option>
                            <option value="Ingreso" {{ old('tipo', $comprobante->tipo ?? '') == 'Ingreso' ? 'selected' : '' }}>Ingreso</option>
                            <option value="Egreso" {{ old('tipo', $comprobante->tipo ?? '') == 'Egreso' ? 'selected' : '' }}>Egreso</option>
                            <option value="Diario" {{ old('tipo', $comprobante->tipo ?? '') == 'Diario' ? 'selected' : '' }}>Diario</option>
                        </select>
                        @error('tipo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-12">
                        <label class="form-label required">Descripción</label>
                        <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                  name="descripcion" 
                                  rows="2" 
                                  required>{{ old('descripcion', $comprobante->descripcion ?? '') }}</textarea>
                        @error('descripcion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Movimientos -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Movimientos</h6>
                            <button type="button" class="btn btn-primary btn-sm" onclick="agregarMovimiento()">
                                <i class="fas fa-plus"></i> Agregar Movimiento
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0" id="tablaMovimientos">
                                <thead class="table-light">
                                    <tr>
                                        <th>Cuenta</th>
                                        <th>Descripción</th>
                                        <th class="text-end">Débito</th>
                                        <th class="text-end">Crédito</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Los movimientos se agregarán dinámicamente -->
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="2" class="text-end">Totales:</th>
                                        <th class="text-end" id="totalDebito">0.00</th>
                                        <th class="text-end" id="totalCredito">0.00</th>
                                        <th></th>
                                    </tr>
                                    <tr id="diferenciaTr" style="display: none;">
                                        <th colspan="2" class="text-end text-danger">Diferencia:</th>
                                        <th colspan="2" class="text-end text-danger" id="diferencia">0.00</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="text-end">
                    <a href="{{ route('comprobantes.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary" id="btnGuardar">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Template para nuevo movimiento -->
<template id="movimientoTemplate">
    <tr>
        <td>
            <select class="form-select cuenta-select" name="movimientos[{index}][cuenta_id]" required>
                <option value="">Seleccione cuenta...</option>
                @foreach($cuentas as $cuenta)
                    <option value="{{ $cuenta->id }}">
                        {{ $cuenta->codigo }} - {{ $cuenta->nombre }}
                    </option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="text" 
                   class="form-control" 
                   name="movimientos[{index}][descripcion]" 
                   required>
        </td>
        <td>
            <input type="number" 
                   class="form-control text-end debito" 
                   name="movimientos[{index}][debito]" 
                   step="0.01" 
                   min="0" 
                   value="0" 
                   onchange="calcularTotales()">
        </td>
        <td>
            <input type="number" 
                   class="form-control text-end credito" 
                   name="movimientos[{index}][credito]" 
                   step="0.01" 
                   min="0" 
                   value="0" 
                   onchange="calcularTotales()">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm" onclick="eliminarMovimiento(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>
</template>
@endsection

@push('scripts')
<script>
let movimientoIndex = 0;

function agregarMovimiento() {
    const template = document.getElementById('movimientoTemplate').innerHTML;
    const tbody = document.querySelector('#tablaMovimientos tbody');
    const newRow = template.replace(/{index}/g, movimientoIndex++);
    tbody.insertAdjacentHTML('beforeend', newRow);

    // Inicializar Select2 en la nueva fila si lo estás usando
    $('.cuenta-select').select2({
        placeholder: 'Seleccione cuenta...',
        width: '100%'
    });
}

function eliminarMovimiento(button) {
    button.closest('tr').remove();
    calcularTotales();
}

function calcularTotales() {
    let totalDebito = 0;
    let totalCredito = 0;
    
    // Sumar débitos
    document.querySelectorAll('.debito').forEach(input => {
        totalDebito += parseFloat(input.value) || 0;
    });
    
    // Sumar créditos
    document.querySelectorAll('.credito').forEach(input => {
        totalCredito += parseFloat(input.value) || 0;
    });
    
    // Actualizar totales
    document.getElementById('totalDebito').textContent = totalDebito.toFixed(2);
    document.getElementById('totalCredito').textContent = totalCredito.toFixed(2);
    
    // Calcular diferencia
    const diferencia = Math.abs(totalDebito - totalCredito);
    document.getElementById('diferencia').textContent = diferencia.toFixed(2);
    
    // Mostrar/ocultar fila de diferencia
    const diferenciaTr = document.getElementById('diferenciaTr');
    if (diferencia > 0) {
        diferenciaTr.style.display = 'table-row';
        document.getElementById('btnGuardar').disabled = true;
    } else {
        diferenciaTr.style.display = 'none';
        document.getElementById('btnGuardar').disabled = false;
    }
}

// Inicializar Select2 para las cuentas
$(document).ready(function() {
    $('.cuenta-select').select2({
        placeholder: 'Seleccione cuenta...',
        width: '100%'
    });
    
    // Agregar al menos un movimiento al cargar la página
    if (document.querySelectorAll('#tablaMovimientos tbody tr').length === 0) {
        agregarMovimiento();
    }
    
    // Validar formulario antes de enviar
    $('#comprobanteForm').on('submit', function(e) {
        const totalDebito = parseFloat(document.getElementById('totalDebito').textContent);
        const totalCredito = parseFloat(document.getElementById('totalCredito').textContent);
        
        if (totalDebito !== totalCredito) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'El comprobante no está cuadrado. La diferencia es de ' + 
                      Math.abs(totalDebito - totalCredito).toFixed(2)
            });
        }
    });
});
</script>
@endpush