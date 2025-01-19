@extends('layouts.app')

@section('title', 'Nuevo Movimiento Masivo')

@section('styles')
<style>
    .cantidad-input {
        width: 80px !important;
        text-align: center;
    }
    .table th {
        background-color: #f8f9fa;
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <form id="movimientoForm" action="{{ route('movimientos-masivos.store') }}" method="POST">
                @csrf
                
                <!-- Tipo de Movimiento y Fecha -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Tipo de Movimiento</label>
                            <select name="tipo_movimiento" class="form-control" required>
                                <option value="">Seleccione tipo...</option>
                                <option value="entrada">Entrada</option>
                                <option value="salida">Salida</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Fecha y Hora</label>
                            <input type="text" class="form-control" value="{{ now()->format('d/m/Y h:i A') }}" readonly>
                        </div>
                    </div>
                </div>

                <!-- Búsqueda de Productos -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" id="busqueda-producto" placeholder="Escanee o escriba el código del producto" autofocus>
                            <button class="btn btn-primary" type="button" id="btn-buscar">
                                <i class="fas fa-search"></i>
                            </button>
                            <button class="btn btn-secondary" type="button" data-bs-toggle="modal" data-bs-target="#productosModal">
                                <i class="fas fa-list"></i> Ver Catálogo
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Productos -->
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Descripción</th>
                                <th>Stock</th>
                                <th>Cantidad</th>
                                <th>Precio</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="productos-container">
                            <tr>
                                <td colspan="5" class="text-center">No hay productos agregados</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Botones de acción -->
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <a href="{{ route('movimientos-masivos.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Productos -->
<div class="modal fade" id="productosModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Catálogo de Productos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="modal-busqueda" placeholder="Buscar productos...">
                </div>
                
                <div class="table-responsive" style="max-height: 400px;">
                    <table class="table table-hover">
                        <thead class="sticky-top bg-light">
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th class="text-center">Stock</th>
                                <th class="text-end">Precio</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($productos as $producto)
                            <tr>
                                <td>{{ $producto->codigo }}</td>
                                <td>{{ $producto->nombre }}</td>
                                <td class="text-center">{{ $producto->stock }}</td>
                                <td class="text-end">${{ number_format($producto->precio_compra, 2) }}</td>
                                <td class="text-center">
                                    <button type="button" 
                                            class="btn btn-sm btn-success btn-seleccionar"
                                            data-producto='@json($producto)'>
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let productos = [];

    // Búsqueda de productos
    $('#busqueda-producto').on('keyup', function(e) {
        if (e.keyCode === 13) {
            const codigo = $(this).val().trim();
            if (codigo) {
                $.get(`{{ route('api.productos.search') }}?q=${codigo}`, function(response) {
                    if (response.items && response.items.length > 0) {
                        agregarProducto(response.items[0]);
                        $('#busqueda-producto').val('').focus();
                    } else {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Producto no encontrado',
                            text: 'No se encontró ningún producto con ese código'
                        });
                    }
                });
            }
        }
    });

    // Selección desde el modal
    $(document).on('click', '.btn-seleccionar', function() {
        const producto = $(this).data('producto');
        agregarProducto(producto);
        $('#productosModal').modal('hide');
        $('#busqueda-producto').focus();
    });

    // Búsqueda en el modal
    $('#modal-busqueda').on('keyup', function() {
        const busqueda = $(this).val().toLowerCase().trim();
        $('#tabla-productos-modal tbody tr').each(function() {
            const texto = $(this).text().toLowerCase();
            $(this).toggle(texto.indexOf(busqueda) > -1);
        });
    });

    function agregarProducto(producto) {
        if (productos.some(p => p.id === producto.id)) {
            Swal.fire({
                icon: 'warning',
                title: 'Producto duplicado',
                text: 'Este producto ya ha sido agregado'
            });
            return;
        }

        productos.push({
            id: producto.id,
            codigo: producto.codigo,
            nombre: producto.nombre,
            stock: producto.stock || 0,
            precio_compra: producto.precio_compra || 0,
            cantidad: 1
        });

        actualizarTabla();
    }

    function actualizarTabla() {
        const container = $('#productos-container');
        
        if (productos.length === 0) {
            container.html('<tr><td colspan="5" class="text-center">No hay productos agregados</td></tr>');
            return;
        }

        const html = productos.map((producto, index) => `
            <tr>
                <td>${producto.nombre}</td>
                <td class="text-center">${producto.stock}</td>
                <td>
                    <input type="number" 
                           name="productos[${index}][cantidad]"
                           class="form-control cantidad-input" 
                           value="${producto.cantidad}"
                           min="1"
                           required>
                    <input type="hidden" name="productos[${index}][id]" value="${producto.id}">
                </td>
                <td>
                    <input type="number" 
                           name="productos[${index}][precio_compra]"
                           class="form-control text-end" 
                           value="${producto.precio_compra}"
                           step="0.01"
                           required>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm" onclick="eliminarProducto(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');

        container.html(html);
    }

    window.eliminarProducto = function(index) {
        productos.splice(index, 1);
        actualizarTabla();
    };
});
</script>
@endpush
