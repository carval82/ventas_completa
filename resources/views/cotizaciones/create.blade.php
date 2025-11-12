@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-file-invoice"></i> Nueva Cotización
                    </h4>
                    <a href="{{ route('cotizaciones.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>

                <div class="card-body">
                    <form id="cotizacionForm" action="{{ route('cotizaciones.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <!-- Información del Cliente -->
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">Información del Cliente</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Cliente *</label>
                                            <select name="cliente_id" class="form-select" required>
                                                <option value="">Seleccionar cliente...</option>
                                                @foreach($clientes as $cliente)
                                                    <option value="{{ $cliente->id }}">
                                                        {{ $cliente->nombres }} {{ $cliente->apellidos }} - {{ $cliente->cedula }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Información de la Cotización -->
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">Información de la Cotización</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Fecha de Cotización *</label>
                                                    <input type="date" name="fecha_cotizacion" class="form-control" 
                                                           value="{{ date('Y-m-d') }}" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Días de Validez *</label>
                                                    <input type="number" name="dias_validez" class="form-control" 
                                                           value="30" min="1" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Forma de Pago</label>
                                            <select name="forma_pago" class="form-select">
                                                <option value="efectivo">Efectivo</option>
                                                <option value="transferencia">Transferencia</option>
                                                <option value="cheque">Cheque</option>
                                                <option value="credito">Crédito</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Productos -->
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Productos</h6>
                                <button type="button" class="btn btn-sm btn-primary" onclick="agregarProducto()">
                                    <i class="fas fa-plus"></i> Agregar Producto
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="tablaProductos">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="25%">Producto</th>
                                                <th width="10%">Cantidad</th>
                                                <th width="10%">Unidad</th>
                                                <th width="15%">Precio Unit.</th>
                                                <th width="10%">Desc. %</th>
                                                @if($empresa->regimen_tributario === 'responsable_iva')
                                                    <th width="10%">IVA %</th>
                                                @endif
                                                <th width="15%">Total</th>
                                                <th width="5%">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="productosBody">
                                            <!-- Los productos se agregan dinámicamente -->
                                        </tbody>
                                    </table>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-8"></div>
                                    <div class="col-md-4">
                                        <table class="table table-sm">
                                            <tr>
                                                <td><strong>Subtotal:</strong></td>
                                                <td class="text-end"><span id="subtotalDisplay">$0</span></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Descuento:</strong></td>
                                                <td class="text-end"><span id="descuentoDisplay">$0</span></td>
                                            </tr>
                                            @if($empresa->regimen_tributario === 'responsable_iva')
                                                <tr>
                                                    <td><strong>IVA:</strong></td>
                                                    <td class="text-end"><span id="ivaDisplay">$0</span></td>
                                                </tr>
                                            @endif
                                            <tr class="table-dark">
                                                <td><strong>TOTAL:</strong></td>
                                                <td class="text-end"><strong><span id="totalDisplay">$0</span></strong></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Observaciones y Condiciones -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Observaciones</label>
                                    <textarea name="observaciones" class="form-control" rows="4" 
                                              placeholder="Observaciones adicionales..."></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Condiciones Comerciales</label>
                                    <textarea name="condiciones_comerciales" class="form-control" rows="4" 
                                              placeholder="Condiciones comerciales..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="row">
                            <div class="col-md-12 text-end">
                                <button type="button" class="btn btn-secondary me-2" onclick="window.history.back()">
                                    <i class="fas fa-times"></i> Cancelar
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar Cotización
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para seleccionar producto -->
<div class="modal fade" id="productoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Seleccionar Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" id="buscarProducto" class="form-control" 
                           placeholder="Buscar producto por nombre o código...">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Stock</th>
                                <th>Precio</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="listaProductos">
                            @foreach($productos as $producto)
                                <tr class="producto-row" data-nombre="{{ strtolower($producto->nombre) }}" 
                                    data-codigo="{{ strtolower($producto->codigo_barras ?? '') }}">
                                    <td>{{ $producto->codigo_barras ?? 'N/A' }}</td>
                                    <td>{{ $producto->nombre }}</td>
                                    <td>{{ $producto->stock }}</td>
                                    <td>${{ number_format($producto->precio_venta, 0, ',', '.') }}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                onclick="seleccionarProducto({{ $producto->id }}, '{{ $producto->nombre }}', {{ $producto->precio_venta }}, {{ $producto->stock }})">
                                            Seleccionar
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
let productos = [];
let contadorProductos = 0;
const aplicaIVA = {{ $empresa->regimen_tributario === 'responsable_iva' ? 'true' : 'false' }};

$(document).ready(function() {
    // Configurar CSRF token para AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Búsqueda de productos
    $('#buscarProducto').on('keyup', function() {
        const valor = $(this).val().toLowerCase();
        $('.producto-row').each(function() {
            const nombre = $(this).data('nombre');
            const codigo = $(this).data('codigo');
            if (nombre.includes(valor) || codigo.includes(valor)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});

function agregarProducto() {
    $('#productoModal').modal('show');
}

function seleccionarProducto(id, nombre, precio, stock) {
    // Verificar si el producto ya está agregado
    if (productos.find(p => p.id === id)) {
        Swal.fire({
            title: 'Producto ya agregado',
            text: 'Este producto ya está en la cotización',
            icon: 'warning'
        });
        return;
    }

    const producto = {
        id: id,
        nombre: nombre,
        precio: precio,
        stock: stock,
        cantidad: 1,
        unidad: 'UND',
        descuento: 0,
        iva: aplicaIVA ? 19 : 0
    };

    productos.push(producto);
    actualizarTablaProductos();
    calcularTotales();
    $('#productoModal').modal('hide');
}

function actualizarTablaProductos() {
    const tbody = $('#productosBody');
    tbody.empty();

    productos.forEach((producto, index) => {
        const subtotal = producto.cantidad * producto.precio;
        const descuentoValor = (subtotal * producto.descuento) / 100;
        const baseIva = subtotal - descuentoValor;
        const ivaValor = aplicaIVA ? (baseIva * producto.iva) / 100 : 0;
        const total = baseIva + ivaValor;

        let row = `
            <tr>
                <td>
                    ${producto.nombre}
                    <input type="hidden" name="productos[${index}][producto_id]" value="${producto.id}">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm" 
                           value="${producto.cantidad}" min="0.001" step="0.001"
                           onchange="actualizarCantidad(${index}, this.value)">
                    <input type="hidden" name="productos[${index}][cantidad]" value="${producto.cantidad}">
                </td>
                <td>
                    <select class="form-select form-select-sm" onchange="actualizarUnidad(${index}, this.value)">
                        <option value="UND" ${producto.unidad === 'UND' ? 'selected' : ''}>UND</option>
                        <option value="KG" ${producto.unidad === 'KG' ? 'selected' : ''}>KG</option>
                        <option value="LT" ${producto.unidad === 'LT' ? 'selected' : ''}>LT</option>
                        <option value="MT" ${producto.unidad === 'MT' ? 'selected' : ''}>MT</option>
                    </select>
                    <input type="hidden" name="productos[${index}][unidad_medida]" value="${producto.unidad}">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm" 
                           value="${producto.precio}" min="0" step="0.01"
                           onchange="actualizarPrecio(${index}, this.value)">
                    <input type="hidden" name="productos[${index}][precio_unitario]" value="${producto.precio}">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm" 
                           value="${producto.descuento}" min="0" max="100" step="0.01"
                           onchange="actualizarDescuento(${index}, this.value)">
                    <input type="hidden" name="productos[${index}][descuento_porcentaje]" value="${producto.descuento}">
                </td>`;

        if (aplicaIVA) {
            row += `
                <td>
                    <input type="number" class="form-control form-control-sm" 
                           value="${producto.iva}" min="0" max="100" step="0.01"
                           onchange="actualizarIva(${index}, this.value)">
                    <input type="hidden" name="productos[${index}][impuesto_porcentaje]" value="${producto.iva}">
                </td>`;
        }

        row += `
                <td class="text-end">
                    <strong>$${formatearNumero(total)}</strong>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger" onclick="eliminarProducto(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>`;

        tbody.append(row);
    });
}

function actualizarCantidad(index, cantidad) {
    productos[index].cantidad = parseFloat(cantidad) || 0;
    actualizarTablaProductos();
    calcularTotales();
}

function actualizarUnidad(index, unidad) {
    productos[index].unidad = unidad;
}

function actualizarPrecio(index, precio) {
    productos[index].precio = parseFloat(precio) || 0;
    actualizarTablaProductos();
    calcularTotales();
}

function actualizarDescuento(index, descuento) {
    productos[index].descuento = parseFloat(descuento) || 0;
    actualizarTablaProductos();
    calcularTotales();
}

function actualizarIva(index, iva) {
    productos[index].iva = parseFloat(iva) || 0;
    actualizarTablaProductos();
    calcularTotales();
}

function eliminarProducto(index) {
    productos.splice(index, 1);
    actualizarTablaProductos();
    calcularTotales();
}

function calcularTotales() {
    let subtotal = 0;
    let totalDescuento = 0;
    let totalIva = 0;

    productos.forEach(producto => {
        const subtotalProducto = producto.cantidad * producto.precio;
        const descuentoProducto = (subtotalProducto * producto.descuento) / 100;
        const baseIva = subtotalProducto - descuentoProducto;
        const ivaProducto = aplicaIVA ? (baseIva * producto.iva) / 100 : 0;

        subtotal += subtotalProducto;
        totalDescuento += descuentoProducto;
        totalIva += ivaProducto;
    });

    const total = subtotal - totalDescuento + totalIva;

    $('#subtotalDisplay').text('$' + formatearNumero(subtotal));
    $('#descuentoDisplay').text('$' + formatearNumero(totalDescuento));
    $('#ivaDisplay').text('$' + formatearNumero(totalIva));
    $('#totalDisplay').text('$' + formatearNumero(total));
}

function formatearNumero(numero) {
    return new Intl.NumberFormat('es-CO').format(Math.round(numero));
}

// Envío del formulario
$('#cotizacionForm').on('submit', function(e) {
    e.preventDefault();

    if (productos.length === 0) {
        Swal.fire({
            title: 'Error',
            text: 'Debe agregar al menos un producto',
            icon: 'error'
        });
        return;
    }

    const formData = $(this).serialize();

    $.ajax({
        url: $(this).attr('action'),
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    title: 'Éxito',
                    text: `Cotización ${response.numero_cotizacion} creada exitosamente`,
                    icon: 'success'
                }).then(() => {
                    window.location.href = '{{ route("cotizaciones.index") }}';
                });
            }
        },
        error: function(xhr) {
            Swal.fire({
                title: 'Error',
                text: xhr.responseJSON?.message || 'Error al crear la cotización',
                icon: 'error'
            });
        }
    });
});
</script>
@endpush
