@extends('layouts.app')

@section('title', 'Nueva Compra')

@section('styles')
<!-- SweetAlert2 CSS ya está incluido en el layout principal -->
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
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Nueva Compra</h5>
                <a href="{{ route('compras.index') }}" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <div class="card-body">
            <form id="compraForm" action="{{ route('compras.store') }}" method="POST">
                @csrf
               <!-- Franja de Accesos Rápidos -->
<div class="bg-light border-bottom p-2 mb-3">
    <div class="row">
        <div class="col-auto">
            <span class="badge bg-secondary me-2">F1</span> Guardar Factura
        </div>
        <div class="col-auto">
            <span class="badge bg-secondary me-2">F2</span> Ver Facturas Guardadas
        </div>
        <div class="col-auto">
            <span class="badge bg-secondary me-2">F6</span> Nuevo Producto
        </div>
        <div class="col-auto">
            <span class="badge bg-secondary me-2">F8</span> Buscar Proveedor
        </div>
        <div class="col-auto">
            <span class="badge bg-secondary me-2">F9</span> Eliminar Producto
        </div>
        <div class="col-auto">
            <span class="badge bg-secondary me-2">F10</span> Ver Catálogo
        </div>
        <div class="col-auto">
            <span class="badge bg-secondary me-2">F12</span> Guardar Compra
        </div>
    </div>
</div>
                
                <!-- Proveedor y Fecha -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label required">N° Factura</label>
                            <input type="text" class="form-control" name="numero_factura" required>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="mb-3">
                            <label class="form-label required">Proveedor</label>
                            <select class="form-control select2" name="proveedor_id" required>
                                <option value="">Seleccionar proveedor...</option>
                                @foreach($proveedores as $proveedor)
                                    <option value="{{ $proveedor->id }}">
                                        {{ $proveedor->nit }} - {{ $proveedor->razon_social }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
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
                                   placeholder="Buscar producto por código o nombre"
                                   autofocus>
                            <button class="btn btn-primary" type="button" id="btn-buscar">
                                <i class="fas fa-search"></i>
                            </button>
                            <button class="btn btn-secondary" type="button" data-bs-toggle="modal" data-bs-target="#productosModal">
                                <i class="fas fa-list"></i> Ver Catálogo
                            </button>
                            
                            <a href="{{ route('productos.create', ['return_to' => 'compras']) }}" 
   class="btn btn-success">
    <i class="fas fa-plus"></i> Nuevo Producto
</a>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2 mb-3">
    <button type="button" class="btn btn-info" onclick="guardarFacturaTemp()">
        <i class="fas fa-save"></i> Guardar Factura
    </button>

    <div class="btn-group">
        <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
            <i class="fas fa-folder-open"></i> Facturas Guardadas
        </button>
        <div class="dropdown-menu p-3" style="width: 300px">
            <div id="facturas-guardadas-list">
                <!-- Aquí se cargarán las facturas guardadas -->
            </div>
        </div>
    </div>
</div>
                <!-- Tabla de Productos -->
    <table class="table table-bordered" id="productos-table">
        <thead class="table-light">
            <tr>
                <th>Código</th>
                <th>Descripción</th>
                <th class="text-center">Cantidad</th>
                <th class="text-center">Unidad</th>
                <th class="text-center">Factor Conv.</th>
                <th class="text-end">Precio Compra</th>
                <th class="text-end">IVA</th>
                <th class="text-end">Subtotal</th>
                <th class="text-center" style="width: 60px;"></th>
            </tr>
        </thead>
        <tbody>
            <tr id="no-productos">
                <td colspan="9" class="text-center">No hay productos agregados</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6"></td>
                <th class="text-end">Subtotal:</th>
                <td class="text-end">$<span id="subtotal-display">0.00</span></td>
                <td></td>
            </tr>
            <tr>
                <td colspan="6"></td>
                <th class="text-end">IVA:</th>
                <td class="text-end">$<span id="iva-display">0.00</span></td>
                <td></td>
            </tr>
            <tr>
                <td colspan="6"></td>
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
        <i class="fas fa-save"></i> Guardar Compra
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
                                <th>Descripción</th>
                                <th class="text-end">Precio Compra</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($productos as $producto)
                            <tr>
                                <td>{{ $producto->codigo }}</td>
                                <td>{{ $producto->nombre }}</td>
                                <td>{{ $producto->descripcion }}</td>
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

<!-- Modal Nuevo Producto -->
<div class="modal fade" id="nuevoProductoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="productoForm" novalidate>
                    <div class="mb-3">
                        <label class="form-label required">Código</label>
                        <input type="text" class="form-control" id="codigo" name="codigo" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Precio Compra</label>
                        <input type="number" step="0.01" class="form-control" id="precio_compra" name="precio_compra" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Precio Venta</label>
                        <input type="number" step="0.01" class="form-control" id="precio_venta" name="precio_venta" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stock Mínimo</label>
                        <input type="number" class="form-control" id="stock_minimo" name="stock_minimo" value="5">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="guardarProducto">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación -->
<div class="modal fade" id="confirmacionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Compra</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de registrar esta compra?</p>
                <div class="alert alert-info">
                    <strong>Total a pagar: $<span id="modal-total">0.00</span></strong>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-confirmar-compra">
                    Confirmar Compra
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<!-- SweetAlert2 JS ya está incluido en el layout principal -->
<script>
$(document).ready(function() {
    // Variables globales
    let productos = [];
    let listaProductos = @json($productos);
    let procesandoPeticion = false;

    console.log('Script de compras iniciado');

    // Configuración global de AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        }
    });

    // Inicializar Select2
    $('.select2').select2({
        placeholder: 'Seleccionar proveedor...',
        allowClear: true
    });

    // Recuperar estado de la compra si existe
    const estadoCompraTemp = localStorage.getItem('estadoCompraTemp');
    if (estadoCompraTemp) {
        const estado = JSON.parse(estadoCompraTemp);
        
        // Restaurar productos
        productos = estado.productos;
        
        // Restaurar proveedor
        if (estado.proveedor_id) {
            $('select[name="proveedor_id"]').val(estado.proveedor_id).trigger('change');
        }
        
        // Restaurar número de factura
        if (estado.numero_factura) {
            $('input[name="numero_factura"]').val(estado.numero_factura);
        }
        
        // Actualizar la tabla y totales
        actualizarTabla();
        calcularTotales();
        
        // Limpiar el estado temporal
        localStorage.removeItem('estadoCompraTemp');
    }

    // Prevenir envío del formulario al presionar Enter
    $('#compraForm').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            return false;
        }
    });
   // Atajos de teclado
   $(document).on('keydown', function(e) {
        // F1 - Guardar Factura Temporal
        if (e.which === 112) {
            e.preventDefault();
            guardarFacturaTemp();
        }
        
        // F2 - Ver Facturas Guardadas
        if (e.which === 113) {
            e.preventDefault();
            $('.dropdown-toggle[data-bs-toggle="dropdown"]').dropdown('toggle');
        }

        // F6 - Nuevo Producto
        if (e.which === 117) {
            e.preventDefault();
            const estadoCompra = {
                productos: productos,
                proveedor_id: $('select[name="proveedor_id"]').val(),
                proveedor_texto: $('select[name="proveedor_id"] option:selected').text(),
                numero_factura: $('input[name="numero_factura"]').val()
            };
            
            localStorage.setItem('estadoCompraTemp', JSON.stringify(estadoCompra));
            window.location.href = $('a[href*="productos/create"]').attr('href');
        }
        
        // F8 - Buscar Proveedor
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
        
        // F12 - Guardar Compra
        if (e.which === 123) {
            e.preventDefault();
            if (productos.length > 0) {
                mostrarModalConfirmacion();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Debe agregar al menos un producto a la compra'
                });
            }
        }
    });
    
    // Evento para guardar estado antes de ir a crear nuevo producto
    $('a[href*="productos/create"]').on('click', function(e) {
        e.preventDefault();
        
        // Guardar el estado actual de la compra
        const estadoCompra = {
            productos: productos,
            proveedor_id: $('select[name="proveedor_id"]').val(),
            proveedor_texto: $('select[name="proveedor_id"] option:selected').text(),
            numero_factura: $('input[name="numero_factura"]').val()
        };
        
        localStorage.setItem('estadoCompraTemp', JSON.stringify(estadoCompra));
        
        // Redirigir a la creación del producto
        window.location.href = $(this).attr('href');
    });

    // Búsqueda de productos
    $('#busqueda-producto').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            const busqueda = $(this).val().trim();
            
            if (!busqueda) return;

            const producto = listaProductos.find(p => 
                p.codigo.toLowerCase() === busqueda.toLowerCase() || 
                p.nombre.toLowerCase().includes(busqueda.toLowerCase())
            );

            if (producto) {
                seleccionarProducto(producto);
                $(this).val('').focus();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Producto no encontrado',
                    text: 'El código o nombre ingresado no corresponde a ningún producto.'
                }).then(() => {
                    $(this).val('').focus();
                });
            }
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
    // Función seleccionar producto
    function seleccionarProducto(producto) {
        const productoExistente = productos.find(p => p.id === producto.id);
        
        if (productoExistente) {
            productoExistente.cantidad += 1;
            productoExistente.subtotal = productoExistente.cantidad * productoExistente.precio;
        } else {
            const precioTotal = parseFloat(producto.precio_compra);
            const iva = precioTotal * 0.19 / 1.19;
            const precioBase = precioTotal - iva;

            productos.push({
                id: producto.id,
                codigo: producto.codigo,
                nombre: producto.nombre,
                descripcion: producto.descripcion,
                cantidad: 1,
                unidad_medida: producto.unidad_medida || 'UND',
                factor_conversion: 1,
                precio: precioTotal,
                precio_original: precioTotal, // Guardamos el precio original
                iva_valor: iva,
                subtotal: precioTotal
            });
        }

        actualizarTabla();
        calcularTotales();
    }

    // Función actualizar tabla
    function actualizarTabla() {
        const tbody = document.querySelector('#productos-table tbody');
        tbody.innerHTML = '';

        if (productos.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center">No hay productos agregados</td></tr>';
            return;
        }

        productos.forEach((producto, index) => {
            const subtotal = producto.cantidad * producto.precio;
            const iva = subtotal * 0.19 / 1.19;
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${producto.codigo}</td>
                <td>${producto.nombre}</td>
                <td class="text-center">
                    <input type="number" 
                           class="form-control form-control-sm cantidad-input" 
                           value="${producto.cantidad}" 
                           min="0.001" 
                           step="0.001"
                           onchange="actualizarCantidad(${index}, this.value)">
                </td>
                <td class="text-center">
                    <select class="form-select form-select-sm" onchange="actualizarUnidad(${index}, this.value)">
                        <option value="UND" ${producto.unidad_medida === 'UND' ? 'selected' : ''}>UND</option>
                        <option value="KG" ${producto.unidad_medida === 'KG' ? 'selected' : ''}>KG</option>
                        <option value="LT" ${producto.unidad_medida === 'LT' ? 'selected' : ''}>LT</option>
                        <option value="MT" ${producto.unidad_medida === 'MT' ? 'selected' : ''}>MT</option>
                        <option value="CAJA" ${producto.unidad_medida === 'CAJA' ? 'selected' : ''}>CAJA</option>
                        <option value="PAQUETE" ${producto.unidad_medida === 'PAQUETE' ? 'selected' : ''}>PAQUETE</option>
                    </select>
                </td>
                <td class="text-center">
                    <input type="number" 
                           class="form-control form-control-sm text-center" 
                           value="${producto.factor_conversion}" 
                           min="0.001" 
                           step="0.001"
                           onchange="actualizarFactorConversion(${index}, this.value)"
                           title="Factor de conversión a unidad base">
                </td>
                <td class="text-end">
                    <input type="number"
                           class="form-control form-control-sm text-end precio-input"
                           value="${producto.precio}"
                           min="0.01"
                           step="0.01"
                           onchange="actualizarPrecio(${index}, this.value)">
                </td>
                <td class="text-end">${iva.toLocaleString()}</td>
                <td class="text-end">${subtotal.toLocaleString()}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm" onclick="eliminarProducto(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                    <input type="hidden" name="productos[${index}][id]" value="${producto.id}">
                    <input type="hidden" name="productos[${index}][cantidad]" value="${producto.cantidad}">
                    <input type="hidden" name="productos[${index}][unidad_medida]" value="${producto.unidad_medida}">
                    <input type="hidden" name="productos[${index}][factor_conversion]" value="${producto.factor_conversion}">
                    <input type="hidden" name="productos[${index}][precio]" value="${producto.precio}">
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    // Funciones de actualización de cantidad y precio
    window.actualizarCantidad = function(index, cantidad) {
        cantidad = parseFloat(cantidad);
        if (cantidad < 0.001) cantidad = 0.001;
        
        productos[index].cantidad = cantidad;
        productos[index].subtotal = cantidad * productos[index].precio;
        actualizarTabla();
        calcularTotales();
    }

    window.actualizarUnidad = function(index, unidad) {
        productos[index].unidad_medida = unidad;
        actualizarTabla();
    }

    window.actualizarFactorConversion = function(index, factor) {
        factor = parseFloat(factor);
        if (factor < 0.001) factor = 0.001;
        
        productos[index].factor_conversion = factor;
        actualizarTabla();
    }

    window.actualizarPrecio = function(index, precio) {
        precio = parseFloat(precio);
        if (precio <= 0) precio = productos[index].precio_original || productos[index].precio;
        
        productos[index].precio = precio;
        productos[index].subtotal = productos[index].cantidad * precio;
        actualizarTabla();
        calcularTotales();
    }
    // Función eliminar producto
    window.eliminarProducto = function(index) {
        if (procesandoPeticion) return;
        procesandoPeticion = true;

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
            procesandoPeticion = false;
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

        // Actualizar displays
        document.getElementById('subtotal-display').textContent = subtotal.toLocaleString();
        document.getElementById('iva-display').textContent = iva_total.toLocaleString();
        document.getElementById('total-display').textContent = total.toLocaleString();
        document.getElementById('modal-total').textContent = total.toLocaleString();

        // Actualizar inputs hidden
        document.querySelector('input[name="subtotal"]').value = subtotal.toFixed(2);
        document.querySelector('input[name="iva"]').value = iva_total.toFixed(2);
        document.querySelector('input[name="total"]').value = total.toFixed(2);
    }

    // Validación del formulario
    $('#compraForm').on('submit', function(e) {
        if (procesandoPeticion) {
            e.preventDefault();
            return;
        }
        
        e.preventDefault();
        procesandoPeticion = true;
        
        if (productos.length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe agregar al menos un producto'
            });
            procesandoPeticion = false;
            return;
        }

        if (!$('select[name="proveedor_id"]').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe seleccionar un proveedor'
            });
            procesandoPeticion = false;
            return;
        }

        if (!$('input[name="numero_factura"]').val().trim()) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe ingresar el número de factura'
            });
            procesandoPeticion = false;
            return;
        }

        mostrarModalConfirmacion();
    });

    // Funciones de confirmación y foco
    function mostrarModalConfirmacion() {
        const modal = new bootstrap.Modal(document.getElementById('confirmacionModal'));
        modal.show();
        procesandoPeticion = false;
    }

    $('#btn-confirmar-compra').on('click', function() {
        if (procesandoPeticion) return;
        procesandoPeticion = true;

        document.getElementById('compraForm').submit();
    });

    $('#productosModal').on('shown.bs.modal', function () {
        $('#modal-busqueda').focus();
    });

    $('#productosModal, #nuevoProductoModal').on('hidden.bs.modal', function () {
        $('#busqueda-producto').focus();
    });
    // Funciones de facturas temporales
    window.guardarFacturaTemp = function() {
        if (procesandoPeticion) return;
        procesandoPeticion = true;

        if (!productos.length) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No hay productos en la factura'
            });
            procesandoPeticion = false;
            return;
        }

        Swal.fire({
            title: 'Guardar Factura Temporal',
            input: 'text',
            inputLabel: 'Referencia (opcional)',
            inputPlaceholder: 'Ingrese una referencia para identificar la factura',
            showCancelButton: true,
            confirmButtonText: 'Guardar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const facturaActual = {
                    id: Date.now(),
                    fecha: new Date().toLocaleString(),
                    referencia: result.value || 'Factura temporal',
                    productos: productos,
                    proveedor_id: $('select[name="proveedor_id"]').val(),
                    proveedor_texto: $('select[name="proveedor_id"] option:selected').text(),
                    numero_factura: $('input[name="numero_factura"]').val(),
                    total: $('input[name="total"]').val()
                };

                let facturasGuardadas = JSON.parse(localStorage.getItem('facturasGuardadas') || '[]');
                facturasGuardadas.unshift(facturaActual);
                if (facturasGuardadas.length > 10) facturasGuardadas.pop();
                
                localStorage.setItem('facturasGuardadas', JSON.stringify(facturasGuardadas));
                actualizarListaFacturas();

                // Limpiar formulario actual
                productos = [];
                $('select[name="proveedor_id"]').val('').trigger('change');
                $('input[name="numero_factura"]').val('');
                actualizarTabla();
                calcularTotales();

                Swal.fire({
                    icon: 'success',
                    title: 'Factura guardada',
                    text: 'La factura se ha guardado temporalmente',
                    timer: 1500
                });
            }
            procesandoPeticion = false;
        });
    };

    window.cargarFactura = function(id) {
        if (procesandoPeticion) return;
        procesandoPeticion = true;

        const facturasGuardadas = JSON.parse(localStorage.getItem('facturasGuardadas') || '[]');
        const factura = facturasGuardadas.find(f => f.id === id);
        
        if (factura) {
            // Limpiar estado actual
            productos = [];
            $('select[name="proveedor_id"]').val('').trigger('change');
            $('input[name="numero_factura"]').val('');
            actualizarTabla();
            calcularTotales();

            // Cargar la factura seleccionada
            productos = factura.productos;
            $('select[name="proveedor_id"]').val(factura.proveedor_id).trigger('change');
            $('input[name="numero_factura"]').val(factura.numero_factura);
            actualizarTabla();
            calcularTotales();

            // Eliminar la factura del almacenamiento temporal
            const facturasActualizadas = facturasGuardadas.filter(f => f.id !== id);
            localStorage.setItem('facturasGuardadas', JSON.stringify(facturasActualizadas));
            actualizarListaFacturas();

            Swal.fire({
                icon: 'success',
                title: 'Factura cargada',
                text: 'La factura se ha cargado correctamente y eliminado del almacenamiento temporal',
                timer: 2000,
                showConfirmButton: false
            });
        }
        
        procesandoPeticion = false;
    };

    function actualizarListaFacturas() {
        const facturas = JSON.parse(localStorage.getItem('facturasGuardadas') || '[]');
        const lista = facturas.map(f => `
            <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                <div>
                    <strong>${f.referencia}</strong><br>
                    <small class="text-muted">${f.fecha}</small><br>
                    ${f.proveedor_texto}<br>
                    Total: $${parseFloat(f.total).toLocaleString()}
                </div>
                <div>
                    <button class="btn btn-sm btn-primary" onclick="cargarFactura(${f.id})">
                        <i class="fas fa-sync"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="eliminarFactura(${f.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
        
        $('#facturas-guardadas-list').html(lista || '<p class="text-center">No hay facturas guardadas</p>');
    }

    // Inicializar lista de facturas guardadas
    actualizarListaFacturas();

    // Si hay un producto recién creado, agregarlo a la lista
    @if(session('producto_creado'))
    document.addEventListener('DOMContentLoaded', function() {
        const producto = JSON.parse(@json(session('producto_creado')));
        seleccionarProducto(producto);
    });
    @endif
});

</script>
@endpush
@endsection