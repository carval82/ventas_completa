@extends('layouts.app')

@section('title', 'Alerta de Stock Bajo')

@section('styles')
<style>
    .card-alert {
        border-left: 4px solid #dc3545;
    }
    .sin-stock {
        background-color: #fee2e2;
    }
    .stock-bajo {
        background-color: #fff3cd;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-danger text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-exclamation-triangle"></i> Alerta de Stock Bajo
                </h5>
                <div>
                    <a href="{{ route('movimientos.index') }}" class="btn btn-light">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    <button onclick="enviarAlertaEmail()" class="btn btn-light ms-2">
                        <i class="fas fa-envelope"></i> Enviar Reporte
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Resumen -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card card-alert">
                        <div class="card-body">
                            <h6 class="card-title">Productos sin Stock</h6>
                            <h2 class="mb-0" id="count-sin-stock">0</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-alert">
                        <div class="card-body">
                            <h6 class="card-title">Productos con Stock Bajo</h6>
                            <h2 class="mb-0" id="count-stock-bajo">0</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-alert">
                        <div class="card-body">
                            <h6 class="card-title">Total Ubicaciones Afectadas</h6>
                            <h2 class="mb-0" id="count-ubicaciones">0</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="mb-4">
                <div class="row">
                    <div class="col-md-4">
                        <select class="form-control" id="filtro-ubicacion">
                            <option value="">Todas las ubicaciones</option>
                            @foreach($ubicaciones as $ubicacion)
                                <option value="{{ $ubicacion->id }}">{{ $ubicacion->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-control" id="filtro-estado">
                            <option value="">Todos los estados</option>
                            <option value="sin-stock">Sin Stock</option>
                            <option value="stock-bajo">Stock Bajo</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Lista de Productos -->
            <div class="table-responsive">
                <table class="table" id="tabla-stock-bajo">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Ubicación</th>
                            <th class="text-center">Stock Actual</th>
                            <th class="text-center">Stock Mínimo</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productosStockBajo as $stock)
                            @php
                                $estado = $stock->stock == 0 ? 'sin-stock' : 'stock-bajo';
                            @endphp
                            <tr class="{{ $estado }}">
                                <td>{{ $stock->producto->codigo }}</td>
                                <td>{{ $stock->producto->nombre }}</td>
                                <td>{{ $stock->ubicacion->nombre }}</td>
                                <td class="text-center">{{ $stock->stock }}</td>
                                <td class="text-center">{{ $stock->producto->stock_minimo }}</td>
                                <td class="text-center">
                                    @if($stock->stock == 0)
                                        <span class="badge bg-danger">Sin Stock</span>
                                    @else
                                        <span class="badge bg-warning">Stock Bajo</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('movimientos.create', ['producto_id' => $stock->producto->id, 'tipo' => 'entrada']) }}" 
                                       class="btn btn-sm btn-success">
                                        <i class="fas fa-plus"></i> Agregar Stock
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No hay productos con stock bajo</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    actualizarContadores();

    // Filtros
    $('#filtro-ubicacion, #filtro-estado').change(function() {
        const ubicacion = $('#filtro-ubicacion').val();
        const estado = $('#filtro-estado').val();
        
        $('#tabla-stock-bajo tbody tr').each(function() {
            let mostrar = true;
            
            if (ubicacion && $(this).find('td:eq(2)').text() !== ubicacion) {
                mostrar = false;
            }
            
            if (estado && !$(this).hasClass(estado)) {
                mostrar = false;
            }
            
            $(this).toggle(mostrar);
        });
    });

    function actualizarContadores() {
        const sinStock = $('#tabla-stock-bajo tbody tr.sin-stock').length;
        const stockBajo = $('#tabla-stock-bajo tbody tr.stock-bajo').length;
        const ubicacionesUnicas = new Set();
        
        $('#tabla-stock-bajo tbody tr').each(function() {
            ubicacionesUnicas.add($(this).find('td:eq(2)').text());
        });

        $('#count-sin-stock').text(sinStock);
        $('#count-stock-bajo').text(stockBajo);
        $('#count-ubicaciones').text(ubicacionesUnicas.size);
    }
});

function enviarAlertaEmail() {
    // Aquí puedes implementar la lógica para enviar el reporte por email
    alert('Funcionalidad de envío de reporte pendiente de implementar');
}
</script>
@endpush