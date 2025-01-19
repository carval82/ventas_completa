@extends('layouts.app')

@section('title', 'Reporte de Stock por Ubicación')

@section('styles')
<style>
    .stock-bajo { color: #dc3545; }
    .sin-stock { background-color: #fee2e2; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Reporte de Stock por Ubicación</h5>
                <div>
                    <a href="{{ route('movimientos.index') }}" class="btn btn-light">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    <button onclick="exportarPDF()" class="btn btn-light ms-2">
                        <i class="fas fa-file-pdf"></i> Exportar PDF
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="tabla-stock">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            @foreach($ubicaciones as $ubicacion)
                                <th class="text-center">{{ $ubicacion->nombre }}</th>
                            @endforeach
                            <th class="text-center">Total</th>
                            <th class="text-center">Stock Mínimo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($productos as $producto)
                            <tr class="{{ $producto->stock_total < $producto->stock_minimo ? 'stock-bajo' : '' }}">
                                <td>{{ $producto->codigo }}</td>
                                <td>{{ $producto->nombre }}</td>
                                @foreach($ubicaciones as $ubicacion)
                                    @php
                                        $stock = $producto->ubicaciones->firstWhere('id', $ubicacion->id)?->pivot->stock ?? 0;
                                    @endphp
                                    <td class="text-center {{ $stock == 0 ? 'sin-stock' : '' }}">
                                        {{ $stock }}
                                    </td>
                                @endforeach
                                <td class="text-center">{{ $producto->stock_total }}</td>
                                <td class="text-center">{{ $producto->stock_minimo }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function exportarPDF() {
    // Aquí puedes implementar la lógica para exportar a PDF
    alert('Funcionalidad de exportación a PDF pendiente de implementar');
}
</script>
@endpush