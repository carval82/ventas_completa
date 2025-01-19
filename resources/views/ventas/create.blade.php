@extends('layouts.app')

@section('title', 'Nueva Venta')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<style>
    .cantidad-input {
        width: 80px !important;
        text-align: center;
        margin: 0 auto;
    }
    
    .table th {
        background-color: #f8f9fa;
    }
    
    .required:after {
        content: " *";
        color: red;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0,0,0,.075);
        cursor: pointer;
    }

    .stock-bajo {
        color: #dc3545;
        font-weight: bold;
    }

    .sin-stock {
        background-color: #fee2e2;
        opacity: 0.7;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Nueva Venta</h5>
                <a href="{{ route('ventas.index') }}" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <div class="card-body">
            <form id="ventaForm" action="{{ route('ventas.store') }}" method="POST">
                @csrf
                <!-- Franja de Accesos Rápidos -->
                <div class="bg-light border-bottom p-2 mb-3">
                    <div class="row">
                        <div class="col-auto">
                            <span class="badge bg-secondary me-2">F1</span> Guardar Venta Temporal
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-secondary me-2">F2</span> Ver Ventas Guardadas
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-secondary me-2">F6</span> Nuevo Producto
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-secondary me-2">F8</span> Buscar Cliente
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-secondary me-2">F9</span> Eliminar Producto
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-secondary me-2">F10</span> Ver Catálogo
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-secondary me-2">F12</span> Guardar Venta
                        </div>
                    </div>
                </div>

                <!-- Cliente y Fecha -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group mb-3">
                            <label class="form-label required">Cliente</label>
                            <select class="form-control select2" name="cliente_id" required>
                                <option value="">Seleccionar cliente...</option>
                                @foreach($clientes as $cliente)
                                    <option value="{{ $cliente->id }}">
                                        {{ $cliente->cedula }} - {{ $cliente->nombres }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label class="form-label">Fecha y Hora</label>
                            <input type="text" class="form-control" value="{{ now()->format('d/m/Y h:i A') }}" readonly>
                        </div>
                    </div>
                </div>
                <!-- Búsqueda de Productos -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="input-group mb-3">
                            <input type="text" 
                                   class="form-control" 
                                   id="busqueda-producto" 
                                   placeholder="Escanee o escriba el código del producto"
                                   autofocus>
                            <button class="btn btn-primary" type="button" id="btn-buscar">
                                <i class="fas fa-search"></i>
                            </button>
                            <button class="btn btn-secondary" type="button" data-bs-toggle="modal" data-bs-target="#productosModal">
                                <i class="fas fa-list"></i> Ver Catálogo
                            </button>
                            <a href="{{ route('productos.create', ['return_to' => 'ventas']) }}" 
                               class="btn btn-success">
                                <i class="fas fa-plus"></i> Nuevo Producto
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Botones de Guardar Temporal -->
                <div class="d-flex gap-2 mb-3">
                    <button type="button" class="btn btn-info" onclick="guardarVentaTemp()">
                        <i class="fas fa-save"></i> Guardar Venta
                    </button>

                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-folder-open"></i> Ventas Guardadas
                        </button>
                        <div class="dropdown-menu p-3" style="width: 300px">
                            <div id="ventas-guardadas-list">
                                <!-- Aquí se cargarán las ventas guardadas -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Productos -->
                <div class="table-responsive mt-4">
                    <table class="table table-bordered" id="productos-table">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Descripción</th>
                                <th class="text-center">Stock</th>
                                <th class="text-center" style="width: 120px;">Cantidad</th>
                                <th class="text-end">Precio</th>
                                <th class="text-end">IVA</th>
                                <th class="text-end">Subtotal</th>
                                <th class="text-center" style="width: 60px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr id="no-productos">
                                <td colspan="8" class="text-center">No hay productos agregados</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5"></td>
                                <th class="text-end">Subtotal:</th>
                                <td class="text-end">$<span id="subtotal-display">0.00</span></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="5"></td>
                                <th class="text-end">IVA:</th>
                                <td class="text-end">$<span id="iva-display">0.00</span></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="5"></td>
                                <th class="text-end">Total:</th>
                                <td class="text-end">
                                    <h4 class="m-0">$<span id="total-display">0.00</span></h4>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                             
        
                </div>
                

                <!-- Campos ocultos -->
                <input type="hidden" name="subtotal" value="0">
                <input type="hidden" name="iva" value="0">
                <input type="hidden" name="total" value="0">

               
<!-- Botones de acción -->
<div class="mt-4">
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save"></i> Guardar Venta
    </button>
    <a href="{{ route('compras.index') }}" class="btn btn-secondary">
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
                                    <input type="text" 
                                           class="form-control" 
                                           id="modal-busqueda" 
                                           placeholder="Buscar productos...">
                                </div>
                                
                                <div class="table-responsive" style="max-height: 400px;">
                                    <table class="table table-hover" id="tabla-productos-modal">
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
                                            <tr class="{{ $producto->stock <= 0 ? 'sin-stock' : '' }} {{ $producto->stock <= $producto->stock_minimo ? 'stock-bajo' : '' }}">
                                                <td>{{ $producto->codigo }}</td>
                                                <td>
                                                    {{ $producto->nombre }}
                                                    @if($producto->stock <= $producto->stock_minimo && $producto->stock > 0)
                                                        <span class="badge bg-warning">Stock Bajo</span>
                                                    @elseif($producto->stock <= 0)
                                                        <span class="badge bg-danger">Sin Stock</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">{{ $producto->stock }}</td>
                                                <td class="text-end">${{ number_format($producto->precio_venta, 2) }}</td>
                                                <td class="text-center">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-success btn-seleccionar"
                                                            data-producto='@json($producto)'
                                                            {{ $producto->stock <= 0 ? 'disabled' : '' }}>
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

                <!-- Modal de Confirmación -->
                <div class="modal fade" id="confirmacionModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirmar Venta</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>¿Está seguro de realizar esta venta?</p>
                                <div class="alert alert-info">
                                    <strong>Total a cobrar: $<span id="modal-total">0.00</span></strong>
                                </div>
                                <div id="stock-warnings" class="alert alert-warning d-none">
                                    <strong>Advertencias de Stock:</strong>
                                    <ul class="mb-0"></ul>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times"></i> Cancelar
                                </button>
                                <button type="button" class="btn btn-primary" id="btn-confirmar-venta">
                                    <i class="fas fa-check"></i> Confirmar Venta
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Variables globales
    let productos = [];
    let medicamentos = @json($productos);

    // Inicializar Select2
    $('.select2').select2({
        placeholder: 'Seleccionar cliente...',
        allowClear: true
    });

    // Recuperar estado guardado
    const estadoVentaTemp = localStorage.getItem('estadoVentaTemp');
    if (estadoVentaTemp) {
        const estado = JSON.parse(estadoVentaTemp);
        productos = estado.productos;
        
        if (estado.cliente_id) {
            $('select[name="cliente_id"]').val(estado.cliente_id).trigger('change');
        }
        
        actualizarTabla();
        calcularTotales();
    }

    // Si hay un producto recién creado, agregarlo a la lista
    @if(session('producto_creado'))
    const producto = JSON.parse(@json(session('producto_creado')));
    seleccionarProducto(producto);
    localStorage.removeItem('estadoVentaTemp');
    @endif

    // Atajos de teclado
    $(document).on('keydown', function(e) {
        // F8 - Buscar Cliente
        if (e.which === 119) {
            e.preventDefault();
            $('.select2').select2('open');
        }
        
        // F9 - Eliminar último producto
        if (e.which === 120) {
            e.preventDefault();
            if (productos.length > 0) {
                eliminarProducto(productos.length - 1);
            }
        }
        
        // F10 - Ver Catálogo
        if (e.which === 121) {
            e.preventDefault();
            $('#productosModal').modal('show');
        }
        
        // F12 - Guardar Venta
        if (e.which === 123) {
            e.preventDefault();
            if (productos.length > 0) {
                mostrarModalConfirmacion();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Debe agregar al menos un producto a la venta',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
            }
        }
    });
    // Evento para guardar estado antes de ir a crear nuevo producto
    $('a[href*="productos/create"]').on('click', function(e) {
        e.preventDefault();
        
        // Guardar el estado actual de la venta
        const estadoVenta = {
            productos: productos,
            cliente_id: $('select[name="cliente_id"]').val(),
            cliente_texto: $('select[name="cliente_id"] option:selected').text()
        };
        
        localStorage.setItem('estadoVentaTemp', JSON.stringify(estadoVenta));
        
        // Redirigir a la creación del producto
        window.location.href = $(this).attr('href');
    });

    // Búsqueda con scanner o manual
    $('#busqueda-producto').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            const busqueda = $(this).val().trim();
            console.log('Búsqueda:', busqueda);
            
            if(!busqueda) return;

            const producto = medicamentos.find(p => 
                p.codigo.toLowerCase() === busqueda.toLowerCase() || 
                p.nombre.toLowerCase().includes(busqueda.toLowerCase())
            );
            
            console.log('Producto encontrado:', producto);

            if(producto) {
                seleccionarProducto(producto);
                $(this).val('').focus();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Producto no encontrado',
                    text: 'El código o nombre ingresado no corresponde a ningún producto.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                }).then(() => {
                    $(this).val('').focus();
                });
            }
        }
    });

    $('#btn-buscar').on('click', function() {
        const busqueda = $('#busqueda-producto').val().trim();
        if (!busqueda) {
            $('#productosModal').modal('show');
            return;
        }

        const producto = medicamentos.find(p => 
            p.codigo.toLowerCase() === busqueda.toLowerCase() || 
            p.nombre.toLowerCase().includes(busqueda.toLowerCase())
        );

        if(producto) {
            seleccionarProducto(producto);
            $('#busqueda-producto').val('').focus();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Producto no encontrado',
                text: 'El código o nombre ingresado no corresponde a ningún producto.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            }).then(() => {
                $('#busqueda-producto').val('').focus();
            });
        }
    });

    // Búsqueda en modal
    $('#modal-busqueda').on('keyup', function() {
        const busqueda = $(this).val().toLowerCase().trim();
        
        $('#tabla-productos-modal tbody tr').each(function() {
            const texto = $(this).text().toLowerCase();
            $(this).toggle(texto.indexOf(busqueda) > -1);
        });
    });

    // Seleccionar producto desde el modal
    $('.btn-seleccionar').on('click', function() {
        const producto = $(this).data('producto');
        seleccionarProducto(producto);
        $('#productosModal').modal('hide');
        $('#busqueda-producto').focus();
    });
    function seleccionarProducto(producto) {
        if (producto.stock <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'Sin stock',
                text: 'Este producto no tiene stock disponible.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
            return;
        }

        // Buscar si el producto ya existe en la tabla
        const productoExistente = productos.find(p => p.id === producto.id);
        
        if (productoExistente) {
            // Verificar que no exceda el stock disponible
            if (productoExistente.cantidad + 1 <= producto.stock) {
                productoExistente.cantidad += 1;
                productoExistente.subtotal = productoExistente.cantidad * productoExistente.precio;
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Stock Insuficiente',
                    text: 'No hay suficiente stock disponible.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
            }
        } else {
            const precioTotal = parseFloat(producto.precio_venta);
            const iva = precioTotal * 0.19 / 1.19;
            const precioBase = precioTotal - iva;

            productos.push({
                id: producto.id,
                codigo: producto.codigo,
                nombre: producto.nombre,
                cantidad: 1,
                precio: precioTotal,
                iva_valor: iva,
                subtotal: precioTotal,
                stock: producto.stock
            });
        }

        actualizarTabla();
        calcularTotales();
    }

    function actualizarTabla() {
        const tbody = document.querySelector('#productos-table tbody');
        tbody.innerHTML = '';

        if (productos.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">No hay productos agregados</td></tr>';
            return;
        }

        productos.forEach((producto, index) => {
            const subtotal = producto.cantidad * producto.precio;
            const iva = subtotal * 0.19 / 1.19;
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${producto.codigo}</td>
                <td>${producto.nombre}</td>
                <td class="text-center">${producto.stock}</td>
                <td class="text-center">
                    <input type="number" 
                           class="form-control form-control-sm cantidad-input" 
                           value="${producto.cantidad}" 
                           min="1" 
                           max="${producto.stock}"
                           onchange="actualizarCantidad(${index}, this.value)">
                </td>
                <td class="text-end">${producto.precio.toLocaleString()}</td>
                <td class="text-end">${iva.toLocaleString()}</td>
                <td class="text-end">${subtotal.toLocaleString()}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm" onclick="eliminarProducto(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                    <input type="hidden" name="productos[${index}][id]" value="${producto.id}">
                    <input type="hidden" name="productos[${index}][cantidad]" value="${producto.cantidad}">
                    <input type="hidden" name="productos[${index}][precio]" value="${producto.precio}">
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    window.actualizarCantidad = function(index, cantidad) {
        cantidad = parseInt(cantidad);
        if (cantidad < 1) cantidad = 1;
        if (cantidad > productos[index].stock) {
            Swal.fire({
                icon: 'warning',
                title: 'Stock Insuficiente',
                text: 'La cantidad excede el stock disponible.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
            cantidad = productos[index].stock;
        }
        productos[index].cantidad = cantidad;
        productos[index].subtotal = cantidad * productos[index].precio;
        actualizarTabla();
        calcularTotales();
    }
    window.eliminarProducto = function(index) {
        Swal.fire({
            title: '¿Está seguro?',
            text: "¿Desea eliminar este producto de la lista?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                productos.splice(index, 1);
                actualizarTabla();
                calcularTotales();
            }
        });
    }

    function calcularTotales() {
        let total = 0;
        let iva_total = 0;

        productos.forEach(producto => {
            const subtotal = producto.cantidad * producto.precio;
            total += subtotal;
            iva_total += subtotal * 0.19 / 1.19;
        });

        const subtotal = total - iva_total;

        document.getElementById('subtotal-display').textContent = subtotal.toLocaleString();
        document.getElementById('iva-display').textContent = iva_total.toLocaleString();
        document.getElementById('total-display').textContent = total.toLocaleString();
        document.getElementById('modal-total').textContent = total.toLocaleString();

        document.querySelector('input[name="subtotal"]').value = subtotal.toFixed(2);
        document.querySelector('input[name="iva"]').value = iva_total.toFixed(2);
        document.querySelector('input[name="total"]').value = total.toFixed(2);
    }

    // Validación del formulario
    document.getElementById('ventaForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (productos.length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe agregar al menos un producto a la venta',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
            return;
        }

        if (!$('select[name="cliente_id"]').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe seleccionar un cliente',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
            return;
        }

        mostrarModalConfirmacion();
    });
    function mostrarModalConfirmacion() {
        const modal = new bootstrap.Modal(document.getElementById('confirmacionModal'));
        modal.show();
    }

    document.getElementById('btn-confirmar-venta').addEventListener('click', function() {
        document.getElementById('ventaForm').submit();
    });

    // Mantener el foco en el input de búsqueda
    $('#productosModal').on('shown.bs.modal', function () {
        $('#modal-busqueda').focus();
    });

    $('#productosModal').on('hidden.bs.modal', function () {
        $('#busqueda-producto').focus();
    });

    // Prevenir envío del formulario al presionar Enter
    $('#ventaForm').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            return false;
        }
    });
    // Funciones de ventas temporales
window.guardarVentaTemp = function() {
    if (!productos.length) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No hay productos en la venta'
        });
        return;
    }

    Swal.fire({
        title: 'Guardar Venta Temporal',
        input: 'text',
        inputLabel: 'Referencia (opcional)',
        inputPlaceholder: 'Ingrese una referencia para identificar la venta',
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const ventaActual = {
                id: Date.now(),
                fecha: new Date().toLocaleString(),
                referencia: result.value || 'Venta temporal',
                productos: productos,
                cliente_id: $('select[name="cliente_id"]').val(),
                cliente_texto: $('select[name="cliente_id"] option:selected').text(),
                total: $('input[name="total"]').value
            };

            let ventasGuardadas = JSON.parse(localStorage.getItem('ventasGuardadas') || '[]');
            ventasGuardadas.unshift(ventaActual);
            if (ventasGuardadas.length > 10) ventasGuardadas.pop();
            
            localStorage.setItem('ventasGuardadas', JSON.stringify(ventasGuardadas));
            actualizarListaVentas();

            // Limpiar formulario actual
            productos = [];
            $('select[name="cliente_id"]').val('').trigger('change');
            actualizarTabla();
            calcularTotales();

            Swal.fire({
                icon: 'success',
                title: 'Venta guardada',
                text: 'La venta se ha guardado temporalmente',
                timer: 1500
            });
        }
    });
};

window.cargarVenta = function(id) {
    const ventasGuardadas = JSON.parse(localStorage.getItem('ventasGuardadas') || '[]');
    const venta = ventasGuardadas.find(v => v.id === id);
    
    if (venta) {
        // Limpiar estado actual
        productos = [];
        $('select[name="cliente_id"]').val('').trigger('change');
        actualizarTabla();
        calcularTotales();

        // Cargar la venta seleccionada
        productos = venta.productos;
        $('select[name="cliente_id"]').val(venta.cliente_id).trigger('change');
        actualizarTabla();
        calcularTotales();

        // Eliminar la venta del almacenamiento temporal
        const ventasActualizadas = ventasGuardadas.filter(v => v.id !== id);
        localStorage.setItem('ventasGuardadas', JSON.stringify(ventasActualizadas));
        actualizarListaVentas();

        Swal.fire({
            icon: 'success',
            title: 'Venta cargada',
            text: 'La venta se ha cargado correctamente',
            timer: 2000,
            showConfirmButton: false
        });
    }
};

function actualizarListaVentas() {
    const ventas = JSON.parse(localStorage.getItem('ventasGuardadas') || '[]');
    const lista = ventas.map(v => `
        <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
            <div>
                <strong>${v.referencia}</strong><br>
                <small class="text-muted">${v.fecha}</small><br>
                ${v.cliente_texto}<br>
                Total: $${parseFloat(v.total).toLocaleString()}
            </div>
            <div>
                <button class="btn btn-sm btn-primary" onclick="cargarVenta(${v.id})">
                    <i class="fas fa-sync"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="eliminarVenta(${v.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
    
    $('#ventas-guardadas-list').html(lista || '<p class="text-center">No hay ventas guardadas</p>');
}

// También debemos agregar los atajos de teclado F1 y F2
$(document).on('keydown', function(e) {
    // F1 - Guardar Venta Temporal
    if (e.which === 112) {
        e.preventDefault();
        guardarVentaTemp();
    }
    
    // F2 - Ver Ventas Guardadas
    if (e.which === 113) {
        e.preventDefault();
        $('.dropdown-toggle[data-bs-toggle="dropdown"]').dropdown('toggle');
    }
});

// Inicializar lista de ventas guardadas
actualizarListaVentas();
});
</script>
@endpush