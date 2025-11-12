@extends('layouts.app')

@section('title', 'Nuevo Movimiento Interno')

@section('styles')
<!-- Select2 CSS ya está incluido en el layout principal -->
<style>
    .required:after {
        content: " *";
        color: red;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Nuevo Movimiento Interno</h5>
                <a href="{{ route('movimientos.index') }}" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <div class="card-body">
            <form action="{{ route('movimientos.store') }}" method="POST" id="movimientoForm">
                @csrf
                
                <div class="row">
                    <!-- Tipo de Movimiento -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label required">Tipo de Movimiento</label>
                        <select class="form-control" name="tipo_movimiento" id="tipo_movimiento" required>
                            <option value="">Seleccione...</option>
                            @foreach($tipos_movimiento as $key => $tipo)
                                <option value="{{ $key }}">{{ $tipo }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Producto -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label required">Producto</label>
                        <select class="form-control select2" name="producto_id" id="producto_id" required>
                            <option value="">Seleccione...</option>
                            @foreach($productos as $producto)
                                <option value="{{ $producto->id }}">
                                    {{ $producto->codigo }} - {{ $producto->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Cantidad -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label required">Cantidad</label>
                        <input type="number" class="form-control" name="cantidad" min="1" required>
                    </div>
                </div>

                <div class="row">
                <div class="col-md-4 mb-3" id="origen-container" style="display: none;">
    <label class="form-label required">Ubicación Origen</label>
    <select class="form-control select2" name="ubicacion_origen_id" id="ubicacion_origen_id">
        <option value="">Seleccione...</option>
        @foreach($ubicaciones as $ubicacion)
            <option value="{{ $ubicacion->id }}">{{ $ubicacion->nombre }}</option>
        @endforeach
    </select>
    <div class="mt-2">
        <label class="form-label fw-bold">Stock disponible:</label>
        <div id="stock-origen" class="badge bg-secondary">0 unidades</div>
    </div>
</div>

                    <!-- Ubicación Destino -->
                    <div class="col-md-4 mb-3" id="destino-container" style="display: none;">
                        <label class="form-label required">Ubicación Destino</label>
                        <select class="form-control select2" name="ubicacion_destino_id" id="ubicacion_destino_id">
                            <option value="">Seleccione...</option>
                            @foreach($ubicaciones as $ubicacion)
                                <option value="{{ $ubicacion->id }}">{{ $ubicacion->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Motivo -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label required">Motivo</label>
                        <select class="form-control" name="motivo" required>
                            <option value="">Seleccione...</option>
                            @foreach($motivos as $key => $motivo)
                                <option value="{{ $key }}">{{ $motivo }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Observaciones -->
                <div class="mb-3">
                    <label class="form-label">Observaciones</label>
                    <textarea class="form-control" name="observaciones" rows="3"></textarea>
                </div>

                <!-- Botones -->
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <a href="{{ route('movimientos.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Select2 JS ya está incluido en el layout principal -->
<script>
$(document).ready(function() {
    // Inicializar Select2
    $('.select2').select2({
        width: '100%'
    });

    // Manejar visibilidad de campos según tipo de movimiento
    $('#tipo_movimiento').change(function() {
        const tipo = $(this).val();
        actualizarCamposSegunTipo(tipo);
        actualizarStockDisponible(); // Actualizar stock cuando cambia el tipo
    });

    function actualizarCamposSegunTipo(tipo) {
        // Resetear campos
        $('#origen-container').hide();
        $('#destino-container').hide();
        $('#ubicacion_origen_id').prop('required', false);
        $('#ubicacion_destino_id').prop('required', false);
        
        switch(tipo) {
            case 'entrada':
                $('#destino-container').show();
                $('#ubicacion_destino_id').prop('required', true);
                break;
            case 'salida':
                $('#origen-container').show();
                $('#ubicacion_origen_id').prop('required', true);
                break;
            case 'traslado':
                $('#origen-container').show();
                $('#destino-container').show();
                $('#ubicacion_origen_id').prop('required', true);
                $('#ubicacion_destino_id').prop('required', true);
                break;
        }
    }

    // Función para actualizar el stock disponible
    function actualizarStockDisponible() {
        const producto_id = $('#producto_id').val();
        const ubicacion_id = $('#ubicacion_origen_id').val();
        const tipo = $('#tipo_movimiento').val();
        
        // Solo mostrar stock para salida y traslado
        if (producto_id && ubicacion_id && (tipo === 'salida' || tipo === 'traslado')) {
            // Mostrar indicador de carga
            $('#stock-origen').html('<i class="fas fa-spinner fa-spin"></i> Consultando...');
            
            $.get('/get-stock-ubicacion', {
                producto_id: producto_id,
                ubicacion_id: ubicacion_id
            })
            .done(function(response) {
                $('#stock-origen').html(`<span class="${response.stock > 0 ? 'text-success' : 'text-danger'} fw-bold">
                    ${response.stock} unidades</span>`);
            })
            .fail(function() {
                $('#stock-origen').html('<span class="text-danger">Error al consultar stock</span>');
            });
        } else {
            $('#stock-origen').text('0');
        }
    }

    // Eventos que disparan la actualización del stock
    $('#producto_id').change(function() {
        actualizarStockDisponible();
        // Limpiar ubicaciones al cambiar de producto
        $('#ubicacion_origen_id, #ubicacion_destino_id').val('').trigger('change');
    });

    $('#ubicacion_origen_id').change(actualizarStockDisponible);

    // Validación del formulario
    $('#movimientoForm').on('submit', function(e) {
        const tipo = $('#tipo_movimiento').val();
        const cantidad = parseInt($('input[name="cantidad"]').val());
        const stockDisponible = parseInt($('#stock-origen').text());

        if (!tipo || !cantidad) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Por favor complete todos los campos requeridos'
            });
            return;
        }

        if ((tipo === 'salida' || tipo === 'traslado') && cantidad > stockDisponible) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: `La cantidad (${cantidad}) excede el stock disponible (${stockDisponible})`
            });
        }
    });

    // Evitar que ubicación origen y destino sean iguales en traslado
    $('#ubicacion_destino_id').change(function() {
        const tipo = $('#tipo_movimiento').val();
        if (tipo === 'traslado') {
            const origen = $('#ubicacion_origen_id').val();
            const destino = $(this).val();
            
            if (origen && destino && origen === destino) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Advertencia',
                    text: 'La ubicación de origen y destino no pueden ser la misma'
                });
                $(this).val('').trigger('change');
            }
        }
    });
});
</script>
@endpush