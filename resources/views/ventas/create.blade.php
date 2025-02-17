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
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label class="form-label">Tipo de Factura</label>
                            <select class="form-select" name="tipo_factura" id="tipo_factura">
                                <option value="normal">Factura Normal</option>
                                <option value="simplificada">Factura Simplificada</option>
                                <option value="electronica">Factura Electrónica</option>
                            </select>
                        </div>
                        <div class="form-group plantilla-factura-container" style="display: none;">
                            <label for="plantilla_factura">Plantilla de Factura</label>
                            <select class="form-select" name="plantilla_factura" id="plantilla_factura">
                                <option value="">Seleccione una plantilla</option>
                                <option value="FE">Factura Electrónica DIAN</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label class="form-label">N° Factura</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="{{ $ultimo_numero }}" readonly>
                                <button class="btn btn-outline-secondary" type="button" title="Ver últimas facturas" data-bs-toggle="popover" data-bs-html="true" data-bs-content="{{ $ultimas_facturas }}">
                                    <i class="fas fa-history"></i>
                                </button>
                            </div>
                            <small class="text-muted">Último número generado automáticamente</small>
                        </div>
                    </div>
                    <div class="col-md-3">
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
                    <div class="col-md-3">
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
                                <th class="text-center">Cantidad</th>
                                <th class="text-end">Precio</th>
                                <th class="text-end columna-iva">IVA</th>
                                <th class="text-end">Subtotal</th>
                                <th class="text-center">Acciones</th>
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
                                <td class="text-end" colspan="2">$<span id="subtotal-display">0.00</span></td>
                            </tr>
                            <tr class="fila-iva">
                                <td colspan="5"></td>
                                <th class="text-end">IVA:</th>
                                <td class="text-end" colspan="2">$<span id="iva-display">0.00</span></td>
                            </tr>
                            <tr>
                                <td colspan="5"></td>
                                <th class="text-end">Total:</th>
                                <td class="text-end" colspan="2">$<span id="total-display">0.00</span></td>
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
                                <div class="form-group mb-3">
                                    <label>Método de Pago:</label>
                                    <select class="form-control" id="metodoPago">
                                        <option value="efectivo">Efectivo</option>
                                        <option value="credito">Crédito</option>
                                    </select>
                                </div>

                                <!-- Campos para pago en efectivo -->
                                <div id="pagoEfectivo">
                                    <div class="form-group mb-3">
                                        <label>Total a Pagar:</label>
                                        <input type="text" class="form-control" id="totalAPagar" readonly>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label>Paga con:</label>
                                        <input type="number" class="form-control" id="pagaCon">
                                    </div>
                                    <div class="form-group mb-3">
                                        <label>Devuelta:</label>
                                        <input type="text" class="form-control" id="devuelta" readonly>
                                    </div>
                                </div>

                                <!-- Campos para crédito -->
                                <div id="pagoCredito" style="display: none;">
                                    <div class="form-group mb-3">
                                        <label>Días de Crédito:</label>
                                        <input type="number" class="form-control" id="diasCredito" min="1">
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" id="btnConfirmarVenta">
                                    Confirmar e Imprimir
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
            window.productos = [];
            window.listaProductos = @json($productos);
            
            console.log('Productos disponibles:', listaProductos); // Debug
            
            const empresa = @json($empresa);
            const aplicaIVA = empresa.regimen === 'responsable_iva';
            
            async function nuevoCliente() {
                $('.select2-cliente').val(null).trigger('change');
                
                const { value: formValues } = await Swal.fire({
                    title: 'Nuevo Cliente',
                    html: `
                        <form id="clienteForm">
                            <div class="mb-3">
                                <label class="form-label required">Cédula/NIT</label>
                                <input type="text" class="form-control" id="cedula" name="cedula" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Nombres</label>
                                <input type="text" class="form-control" id="nombres" name="nombres" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="telefono" name="telefono">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="direccion" name="direccion">
                            </div>
                        </form>
                    `,
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: 'Guardar',
                    cancelButtonText: 'Cancelar',
                    preConfirm: () => {
                        const form = document.getElementById('clienteForm');
                        const formData = new FormData(form);
                        
                        return $.ajax({
                            url: '{{ route("clientes.store") }}',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false
                        }).catch(error => {
                            Swal.showValidationMessage(
                                `Error: ${error.responseJSON?.message || 'No se pudo crear el cliente'}`
                            );
                        });
                    }
                });

                if (formValues && formValues.success) {
                    const option = new Option(
                        formValues.cliente.cedula + ' - ' + formValues.cliente.nombres,
                        formValues.cliente.id,
                        true,
                        true
                    );
                    $('.select2-cliente').append(option).trigger('change');
                }
            }

            // Inicializar Select2
            $('.select2-cliente').select2({
                placeholder: 'Seleccionar cliente...',
                allowClear: true,
                language: {
                    noResults: function() {
                        return `<div class="nuevo-cliente-option" value="new-client">Nuevo cliente...</div>`;
                    }
                },
                escapeMarkup: function(markup) {
                    return markup;
                }
            }).on('select2:open', function() {
                // Evitar que select2 capture todos los eventos de teclado
                $('.select2-search__field').off('keydown').on('keydown', function(e) {
                    e.stopPropagation();
                });
            });

            $(document).on("click", ".nuevo-cliente-option", nuevoCliente);

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
                    $('.select2-cliente').select2('open');
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
                    mostrarModalConfirmacion();
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

            // Asegurar que el input de búsqueda de productos no sea afectado por select2
            $('#busqueda-producto').on('focus click', function(e) {
                e.stopPropagation();
            }).on('keypress', function(e) {
                if (e.which === 13) { // Enter
                    e.preventDefault();
                    e.stopPropagation();
                    const busqueda = $(this).val().trim().toLowerCase();
                    
                    if (!busqueda) return;
                    
                    // Primero buscar por código exacto (para código de barras)
                    let producto = listaProductos.find(p => 
                        p.codigo.toLowerCase() === busqueda
                    );
                    
                    // Si no encuentra por código, buscar por nombre
                    if (!producto) {
                        producto = listaProductos.find(p => 
                            p.nombre.toLowerCase().includes(busqueda)
                        );
                    }
                    
                    if (producto) {
                        agregarProducto(producto);
                        $(this).val('').focus(); // Limpiar y mantener foco
                    } else {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Producto no encontrado',
                            text: 'No se encontró ningún producto con ese código o nombre',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        $(this).select(); // Seleccionar texto para facilitar nueva búsqueda
                    }
                }
            });

            // Asegurar que el input de búsqueda mantenga el foco
            $(document).ready(function() {
                $('#busqueda-producto').focus();
            });

            // Delegación de eventos para los botones de selección
            $(document).on('click', '.btn-seleccionar', function() {
                const producto = $(this).data('producto');
                agregarProducto(producto);
            });

            // Función para agregar producto (ya sea por escaneo o selección manual)
            function agregarProducto(producto) {
                if (!producto) return;
                
                // Buscar si el producto ya existe
                const existente = productos.find(p => p.id === producto.id);
                
                if (existente) {
                    // Si existe, incrementar cantidad si hay stock
                    if (existente.cantidad < producto.stock) {
                        existente.cantidad++;
                        actualizarTablaProductos();
                    } else {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Stock insuficiente',
                            text: 'No hay más unidades disponibles de este producto',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                } else {
                    // Si no existe, agregarlo como nuevo
                    productos.push({
                        id: producto.id,
                        codigo: producto.codigo,
                        nombre: producto.nombre,
                        stock: parseInt(producto.stock),
                        cantidad: 1,
                        precio_venta: parseFloat(producto.precio_venta)
                    });
                    actualizarTablaProductos();
                }
            }

            // Modificar la función actualizarTablaProductos
            function actualizarTablaProductos() {
                const tbody = $('#productos-table tbody');
                
                // Ocultar/Mostrar columnas de IVA según régimen
                if (!aplicaIVA) {
                    $('.columna-iva').hide();
                    $('.fila-iva').hide();
                }
                
                tbody.empty();

                // Limpiar campos ocultos de productos anteriores
                $('input[name^="productos"]').remove();

                if (productos.length === 0) {
                    tbody.html(`<tr><td colspan="${aplicaIVA ? 8 : 7}" class="text-center">No hay productos agregados</td></tr>`);
                    return;
                }

                productos.forEach((producto, index) => {
                    const subtotal = producto.cantidad * producto.precio_venta;
                    const iva = aplicaIVA ? Math.round(subtotal * (empresa.porcentaje_iva / 100)) : 0;
                    const subtotal_sin_iva = aplicaIVA ? subtotal - iva : subtotal;

                    tbody.append(`
                        <tr id="producto-${producto.id}">
                            <td>${producto.codigo}</td>
                            <td>${producto.nombre}</td>
                            <td class="text-center">${producto.stock}</td>
                            <td class="text-center">
                                <input type="number" 
                                       class="form-control cantidad-input" 
                                       value="${producto.cantidad}"
                                       min="1"
                                       max="${producto.stock}"
                                       onchange="actualizarCantidad(${index}, this.value)"
                                       onclick="this.select()"
                                       style="width: 80px; text-align: center; margin: 0 auto;">
                            </td>
                            <td class="text-end">${producto.precio_venta.toLocaleString()}</td>
                            ${aplicaIVA ? `<td class="text-end columna-iva">${iva.toLocaleString()}</td>` : ''}
                            <td class="text-end">${subtotal_sin_iva.toLocaleString()}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger" onclick="eliminarProducto(${index})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `);

                    // Agregar campos ocultos para enviar al servidor
                    $('#ventaForm').append(`
                        <input type="hidden" name="productos[${index}][id]" value="${producto.id}">
                        <input type="hidden" name="productos[${index}][cantidad]" value="${producto.cantidad}">
                        <input type="hidden" name="productos[${index}][precio]" value="${producto.precio_venta}">
                    `);
                });

                calcularTotales();
            }

            // Modificar la función actualizarCantidad para que sea accesible globalmente
            window.actualizarCantidad = function(index, cantidad) {
                cantidad = parseInt(cantidad);
                
                if (!productos[index]) {
                    console.error('Producto no encontrado en índice:', index);
                    return;
                }

                if (cantidad < 1) cantidad = 1;
                if (cantidad > productos[index].stock) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Stock Insuficiente',
                        text: 'La cantidad excede el stock disponible',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    cantidad = productos[index].stock;
                }

                productos[index].cantidad = cantidad;
                actualizarTablaProductos();
                calcularTotales(); // Asegurarnos que se actualicen los totales
            };

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
                        actualizarTablaProductos();
                        calcularTotales();
                    }
                });
            }

            // Modificar la función calcularTotales
            function calcularTotales() {
                let total = 0;
                let iva_total = 0;

                productos.forEach(producto => {
                    const subtotal = producto.cantidad * producto.precio_venta;
                    if (aplicaIVA) {
                        const iva = Math.round(subtotal * (empresa.porcentaje_iva / 100));
                        iva_total += iva;
                        total += subtotal;
                    } else {
                        total += subtotal;
                    }
                });

                const subtotal = aplicaIVA ? total - iva_total : total;

                $('#subtotal-display').text(subtotal.toLocaleString());
                $('#iva-display').text(iva_total.toLocaleString());
                $('#total-display').text(total.toLocaleString());

                $('input[name="subtotal"]').val(subtotal);
                $('input[name="iva"]').val(iva_total);
                $('input[name="total"]').val(total);
            }

            // Modificar el evento change del tipo de factura
            $('#tipo_factura').on('change', function() {
                const tipoFactura = $(this).val();
                
                // Ocultar/Mostrar columnas de IVA según régimen
                if (tipoFactura === 'simplificada') {
                    $('.columna-iva').hide();
                    $('.fila-iva').hide();
                } else {
                    $('.columna-iva').show();
                    $('.fila-iva').show();
                }

                // Manejar plantilla de factura electrónica
                if (tipoFactura === 'electronica') {
                    // Establecer el valor de la plantilla directamente
                    $('#plantilla_factura').val('FE');
                    $('.plantilla-factura-container').show();
                    
                    // Verificar con Alegra (opcional)
                    $.get('{{ route("empresa.verificar-fe") }}')
                        .done(function(response) {
                            if (!response.success || !response.habilitado) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'No habilitado',
                                    text: 'Error al verificar facturación electrónica'
                                });
                                $(this).val('normal');
                                $('#plantilla_factura').val('');
                                $('.plantilla-factura-container').hide();
                            }
                        })
                        .fail(function() {
                            console.log('Error al verificar facturación electrónica');
                        });
                } else {
                    $('#plantilla_factura').val('');
                    $('.plantilla-factura-container').hide();
                }

                actualizarTablaProductos();
            });

            // Asegurarnos que el valor de la plantilla se incluya en el formulario
            $('#ventaForm').on('submit', function(e) {
                e.preventDefault();
                
                if (productos.length === 0) {
                    Swal.fire('Error', 'Debe agregar al menos un producto', 'error');
                    return;
                }

                if (!$('select[name="cliente_id"]').val()) {
                    Swal.fire('Error', 'Debe seleccionar un cliente', 'error');
                    return;
                }

                // Validar plantilla para factura electrónica
                if ($('#tipo_factura').val() === 'electronica' && !$('#plantilla_factura').val()) {
                    Swal.fire('Error', 'Debe seleccionar una plantilla para factura electrónica', 'error');
                    return;
                }

        mostrarModalConfirmacion();
    });
    function mostrarModalConfirmacion() {
        if (productos.length === 0) {
            Swal.fire('Error', 'Debe agregar productos a la venta', 'error');
            return;
        }

        // Obtener el total y limpiarlo de formato
        const total = $('#total-display').text()
            .replace('$', '')
            .replace(/,/g, '')
            .trim();
        
        // Actualizar campos del modal
        $('#totalAPagar').val(total);
        $('#pagaCon').val('');
        $('#devuelta').val('');
        
        // Mostrar modal
        $('#confirmacionModal').modal('show');
        
        // Asignar evento al campo pagaCon
        $('#pagaCon').off('input').on('input', function() {
            const totalPagar = parseFloat($('#totalAPagar').val().replace(/[$.]/g, '').replace(/,/g, '')) || 0;
            const pagaCon = parseFloat($(this).val()) || 0;
            const devuelta = pagaCon - totalPagar;
            
            $('#devuelta').val(devuelta >= 0 ? devuelta.toLocaleString('es-CO') : '0');
            $('#btnConfirmarVenta').prop('disabled', pagaCon < totalPagar);
        });

        // Agregar evento al botón confirmar
        $('#btnConfirmarVenta').off('click').on('click', function() {
            const form = $('#ventaForm');
            
            // Obtener valores según el método de pago
            const metodoPago = $('#metodoPago').val();
            if (metodoPago === 'efectivo') {
                const pago = parseFloat($('#pagaCon').val()) || 0;
                const devuelta = parseFloat($('#devuelta').val().replace(/[$.]/g, '').replace(/,/g, '')) || 0;
                
                form.find('input[name="pago"]').remove();
                form.find('input[name="devuelta"]').remove();
                form.append(`<input type="hidden" name="pago" value="${pago}">`);
                form.append(`<input type="hidden" name="devuelta" value="${devuelta}">`);
            } else {
                const diasCredito = $('#diasCredito').val();
                if (!diasCredito) {
                    Swal.fire('Error', 'Debe especificar los días de crédito', 'error');
                    return;
                }
                form.append(`<input type="hidden" name="dias_credito" value="${diasCredito}">`);
            }

            // Agregar método de pago al formulario
            form.find('input[name="metodo_pago"]').remove();
            form.append(`<input type="hidden" name="metodo_pago" value="${metodoPago}">`);

            // Cerrar modal y enviar
            $('#confirmacionModal').modal('hide');
            
            // Enviar formulario con AJAX
            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: form.serialize(),
                success: function(response) {
                    console.log('Respuesta completa:', response); // Debug

                    try {
                        if (typeof response === 'string') {
                            response = JSON.parse(response);
                        }

                        console.log('URLs:', {
                            print: response.print_url,
                            redirect: response.redirect_url
                        }); // Debug

                        Swal.fire({
                            icon: 'success',
                            title: 'Venta Realizada',
                            text: 'La venta se ha registrado correctamente',
                            showCancelButton: true,
                            confirmButtonText: 'Imprimir Factura',
                            cancelButtonText: 'Nueva Venta',
                            allowOutsideClick: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                                const printWindow = window.open(response.print_url, '_blank');
                                if (!printWindow) {
                                    console.error('Bloqueador de popups detectado');
                                    alert('Por favor, permita las ventanas emergentes para imprimir');
                                }
                            }
                            
                            // Pequeño delay antes de redireccionar
                            setTimeout(() => {
                                window.location.href = response.redirect_url;
                            }, 1000);
                        });
                    } catch (e) {
                        console.error('Error al procesar respuesta:', e, response);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error al procesar la respuesta del servidor'
                        });
                    }
                },
                error: function(xhr) {
                    console.error('Error completo:', xhr);
                    let errorMessage = 'Hubo un error al procesar la venta';
                    
                    try {
                        const errorResponse = JSON.parse(xhr.responseText);
                        errorMessage = errorResponse.message || errorMessage;
                    } catch (e) {}
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage
                    });
                }
            });
        });
    }

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
                        actualizarTablaProductos();
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
                    actualizarTablaProductos();
                    calcularTotales();

                    // Cargar la venta seleccionada
                    productos = venta.productos;
                    $('select[name="cliente_id"]').val(venta.cliente_id).trigger('change');
                    actualizarTablaProductos();
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

// Agregar el evento change al select de método de pago
$('#metodoPago').on('change', function() {
    const metodo = $(this).val();
    if (metodo === 'efectivo') {
        $('#pagoEfectivo').show();
        $('#pagoCredito').hide();
        // Reactivar validación de pago completo
        $('#pagaCon').trigger('input');
    } else {
        $('#pagoEfectivo').hide();
        $('#pagoCredito').show();
        // Desactivar validación de pago completo para créditos
        $('#btnConfirmarVenta').prop('disabled', false);
    }
});

// Modificar el evento click del botón confirmar
$('#btnConfirmarVenta').off('click').on('click', function() {
    const form = $('#ventaForm');
    const metodoPago = $('#metodoPago').val();
    
    // Obtener valores según el método de pago
    if (metodoPago === 'efectivo') {
        const pago = parseFloat($('#pagaCon').val()) || 0;
        const devuelta = parseFloat($('#devuelta').val().replace(/[$.]/g, '').replace(/,/g, '')) || 0;
        
        form.find('input[name="pago"]').remove();
        form.find('input[name="devuelta"]').remove();
        form.append(`<input type="hidden" name="pago" value="${pago}">`);
        form.append(`<input type="hidden" name="devuelta" value="${devuelta}">`);
    } else {
        const diasCredito = $('#diasCredito').val();
        if (!diasCredito) {
            Swal.fire('Error', 'Debe especificar los días de crédito', 'error');
            return;
        }
        form.append(`<input type="hidden" name="dias_credito" value="${diasCredito}">`);
    }

    // Agregar método de pago al formulario
    form.find('input[name="metodo_pago"]').remove();
    form.append(`<input type="hidden" name="metodo_pago" value="${metodoPago}">`);

    // Cerrar modal y enviar
    $('#confirmacionModal').modal('hide');
    
    // Enviar formulario con AJAX
    $.ajax({
        url: form.attr('action'),
        method: 'POST',
        data: form.serialize(),
        success: function(response) {
            console.log('Respuesta completa:', response); // Debug

            try {
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }

                console.log('URLs:', {
                    print: response.print_url,
                    redirect: response.redirect_url
                }); // Debug

                Swal.fire({
                    icon: 'success',
                    title: 'Venta Realizada',
                    text: 'La venta se ha registrado correctamente',
                    showCancelButton: true,
                    confirmButtonText: 'Imprimir Factura',
                    cancelButtonText: 'Nueva Venta',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        const printWindow = window.open(response.print_url, '_blank');
                        if (!printWindow) {
                            console.error('Bloqueador de popups detectado');
                            alert('Por favor, permita las ventanas emergentes para imprimir');
                        }
                    }
                    
                    // Pequeño delay antes de redireccionar
                    setTimeout(() => {
                        window.location.href = response.redirect_url;
                    }, 1000);
                });
            } catch (e) {
                console.error('Error al procesar respuesta:', e, response);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al procesar la respuesta del servidor'
                });
            }
        },
        error: function(xhr) {
            console.error('Error completo:', xhr);
            let errorMessage = 'Hubo un error al procesar la venta';
            
            try {
                const errorResponse = JSON.parse(xhr.responseText);
                errorMessage = errorResponse.message || errorMessage;
            } catch (e) {}
            
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorMessage
            });
        }
    });
});
});
</script>
@endpush