@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Sugeridos de Compra</h2>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <form action="{{ route('sugeridos.index') }}" method="GET">
                <select name="proveedor_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Seleccione un proveedor</option>
                    @foreach($proveedores as $proveedor)
                        <option value="{{ $proveedor->id }}" 
                            {{ request('proveedor_id') == $proveedor->id ? 'selected' : '' }}>
                            {{ $proveedor->razon_social }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    @if($proveedorActual)
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Proveedor: {{ $proveedorActual->razon_social }}</h4>
                <button class="btn btn-light" onclick="confirmarGenerarOrden()">
                    Generar Orden Completa
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Stock Actual</th>
                                <th>Stock Mínimo</th>
                                <th>Consumo Semanal</th>
                                <th>Cantidad Sugerida</th>
                                <th>Precio Compra</th>
                                <th>Total</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sugeridos as $sugerido)
                            <tr>
                                <td>{{ $sugerido->producto->codigo }}</td>
                                <td>{{ $sugerido->producto->nombre }}</td>
                                <td>{{ $sugerido->producto->stock }}</td>
                                <td>{{ $sugerido->producto->stock_minimo }}</td>
                                <td>{{ number_format($sugerido->consumo_semanal, 2) }}</td>
                                <td>
                                    <input type="number" 
                                           class="form-control cantidad-sugerida" 
                                           value="{{ $sugerido->cantidad_sugerida }}"
                                           data-id="{{ $sugerido->id }}"
                                           min="1">
                                </td>
                                <td>{{ number_format($sugerido->producto->precio_compra ?? 0, 2) }}</td>
                                <td>{{ number_format($sugerido->cantidad_sugerida * $sugerido->producto->precio_compra, 2) }}</td>
                                <td>
                                    <button class="btn btn-primary btn-sm" 
                                            onclick="generarOrdenIndividual({{ $sugerido->id }})">
                                        Generar Orden
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection 

@push('scripts')
<script>
function confirmarGenerarOrden() {
    Swal.fire({
        title: '¿Generar orden de compra?',
        text: "¿Está seguro de generar la orden de compra para todos los productos sugeridos?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, generar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const proveedorId = document.querySelector('select[name="proveedor_id"]').value;
            window.location.href = "{{ route('sugeridos.generar-orden') }}?proveedor_id=" + proveedorId;
        }
    });
}

function generarOrdenIndividual(sugeridoId) {
    Swal.fire({
        title: '¿Generar orden individual?',
        text: "¿Está seguro de generar la orden para este producto?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, generar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "{{ route('sugeridos.generar-orden-individual', '') }}/" + sugeridoId;
        }
    });
}
</script>
@endpush 