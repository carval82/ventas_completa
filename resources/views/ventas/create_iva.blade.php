@extends('layouts.app')

@section('title', 'Nueva Venta con IVA')

@section('styles')
<!-- SweetAlert2 CSS ya está incluido en el layout principal -->
<style>
    .cantidad-input {
        width: 120px !important;
        text-align: center;
        margin: 0 auto;
        font-weight: 500;
    }
    
    .cantidad-input:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
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
        .sin-stock {
            background-color: #f8d7da !important;
            opacity: 0.6;
        }
        
        .stock-bajo {
            background-color: #fff3cd !important;
        }

        /* Estilos para el filtro de búsqueda */
        #modal-busqueda:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        #limpiar-busqueda {
            border-left: none;
        }

        #no-resultados-modal td {
            padding: 2rem !important;
        }
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
                <h5 class="mb-0">Nueva Venta con IVA</h5>
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
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="generar_fe" id="generar_fe" value="1">
                                <label class="form-check-label" for="generar_fe">
                                    Generar factura electrónica
                                </label>
                                <small class="form-text text-muted d-block">
                                    Si no marca esta opción, se creará la venta sin enviarla a Alegra.
                                </small>
                            </div>
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
                                <th class="text-center">Unidad</th>
                                <th class="text-end">Precio (IVA inc.)</th>
                                <th class="text-end columna-iva">IVA</th>
                                <th class="text-end">Subtotal</th>
                                <th class="text-center">Acciones</th>
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
                                <td class="text-end" colspan="2">$<span id="subtotal-display">0.00</span></td>
                            </tr>
                            <tr class="fila-iva">
                                <td colspan="6"></td>
                                <th class="text-end">IVA:</th>
                                <td class="text-end" colspan="2">$<span id="iva-display">0.00</span></td>
                            </tr>
                            <tr>
                                <td colspan="6"></td>
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
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="modal-busqueda" 
                                           placeholder="Buscar por código o nombre del producto...">
                                    <button class="btn btn-outline-secondary" type="button" id="limpiar-busqueda">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                
                                <div class="table-responsive" style="max-height: 400px;">
                                    <table class="table table-hover" id="tabla-productos-modal">
                                        <thead class="sticky-top bg-light">
                                            <tr>
                                                <th>Código</th>
                                                <th>Nombre</th>
                                                <th class="text-center">Stock</th>
                                                <th class="text-end">Precio (IVA inc.)</th>
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
                                                <td class="text-end">${{ number_format($producto->precio_final, 2) }}</td>
                                                <td class="text-center">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-success btn-seleccionar"
                                                            data-producto='@json($producto)'
                                                            data-id="{{ $producto->id }}"
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
    <!-- SweetAlert2 JS ya está incluido en el layout principal -->
    <!-- Scripts de diagnóstico y pruebas -->
    <!-- <script src="{{ asset('js/diagnostico-ventas.js') }}"></script> DESACTIVADO TEMPORALMENTE -->
    <!-- <script src="{{ asset('js/ventas-unidades.js') }}"></script> DESACTIVADO TEMPORALMENTE -->
    <!-- <script src="{{ asset('js/test-unidades.js') }}"></script> DESACTIVADO TEMPORALMENTE -->
    <script>
        $(document).ready(function() {
            // Variables globales
            window.productos = [];
            window.listaProductos = @json($productos);
            
            console.log('Productos disponibles:', listaProductos);
            
            const empresa = @json($empresa);
            // En este formulario, siempre aplicamos IVA
            window.aplicaIVA = true;
            window.porcentajeIVA = empresa.porcentaje_iva || 19; // Usar 19% como valor predeterminado si no está configurado
            console.log('Formulario con IVA activado, porcentaje:', window.porcentajeIVA);
            
            // Asegurar que las columnas de IVA siempre estén visibles
            $('.columna-iva').show();
            $('.fila-iva').show();
            
            // Inicializar visibilidad de columnas IVA
            function inicializarColumnasIVA() {
                if (window.aplicaIVA) {
                    $('.columna-iva').show();
                    $('.fila-iva').show();
                } else {
                    $('.columna-iva').hide();
                    $('.fila-iva').hide();
                }
                console.log('Columnas IVA inicializadas. Visible:', window.aplicaIVA);
            }
            
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
        
        // actualizarTabla();
        calcularTotales();
    }

    // Si hay un producto recién creado, agregarlo a la lista
    @if(session('producto_creado'))
    const producto = JSON.parse(@json(session('producto_creado')));
    agregarProducto(producto);
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
                if (producto) {
                    agregarProducto(producto);
                } else {
                    console.error('No se encontró el producto');
                }
            });

            // Función para identificar si un producto es un servicio
            function esServicio(nombre) {
                const nombreLower = nombre.toLowerCase();
                const palabrasServicio = [
                    'servicio', 'instalacion', 'instalación', 'mantenimiento', 
                    'reparacion', 'reparación', 'soporte', 'configuracion', 
                    'configuración', 'mano de obra', 'licencia', 'internet', 
                    'kaspersky', 'office', 'windows', 'implementacion', 
                    'implementación', 'revision', 'revisión', 'reubicacion',
                    'reubicación', 'desinstalacion', 'desinstalación'
                ];
                
                return palabrasServicio.some(palabra => nombreLower.includes(palabra));
            }

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
                    const precioConIVA = parseFloat(producto.precio_final || producto.precio_venta);
                    console.log('Agregando producto con precio IVA incluido:', precioConIVA);
                    
                    productos.push({
                        id: producto.id,
                        codigo: producto.codigo,
                        nombre: producto.nombre,
                        stock: parseInt(producto.stock),
                        cantidad: 1,
                        precio_venta: precioConIVA,
                        precio_original: precioConIVA, // Guardar precio original
                        iva: parseFloat(producto.iva || 0),
                        es_servicio: esServicio(producto.nombre)
                    });
                    actualizarTablaProductos();
                }
                
                // Asegurar visibilidad de columnas IVA
                inicializarColumnasIVA();
                
                // Mantener foco en el input de búsqueda
                $('#busqueda-producto').val('').focus();
            }

            // Función para actualizar precio de servicios
            window.actualizarPrecioServicio = function(index, nuevoPrecio) {
                nuevoPrecio = parseFloat(nuevoPrecio);
                
                if (!productos[index]) {
                    console.error('Producto no encontrado en índice:', index);
                    return;
                }

                if (isNaN(nuevoPrecio) || nuevoPrecio < 1) {
                    nuevoPrecio = productos[index].precio_original || productos[index].precio_venta;
                }

                // Actualizar el precio del servicio (en vista con IVA, el precio ya incluye IVA)
                productos[index].precio_venta = nuevoPrecio;
                productos[index].precio_final = nuevoPrecio;
                
                console.log(`💰 Precio de servicio actualizado (con IVA):`, {
                    producto: productos[index].nombre,
                    precio_anterior: productos[index].precio_original,
                    precio_nuevo: nuevoPrecio,
                    precio_final: productos[index].precio_final
                });

                // Actualizar la fila visual inmediatamente
                actualizarFilaProductoIVA(index);
                
                // Recalcular totales
                calcularTotales();
                
                // Actualizar campos ocultos
                actualizarCamposOcultosIVA();
            };

            // Función para actualizar una fila específica del producto (vista con IVA)
            function actualizarFilaProductoIVA(index) {
                const producto = productos[index];
                if (!producto) return;

                // En vista con IVA, el precio ya incluye IVA
                const precio_con_iva = producto.precio_venta;
                const subtotal_producto = producto.cantidad * precio_con_iva;
                
                // Calcular IVA extraído del precio
                const iva = Math.round(subtotal_producto * window.porcentajeIVA / (100 + window.porcentajeIVA));
                const subtotal_sin_iva = subtotal_producto - iva;

                // Actualizar la celda del precio
                const filaProducto = $(`#producto-${producto.id}`);
                if (filaProducto.length) {
                    // Actualizar precio (mantener el input editable para servicios)
                    if (producto.es_servicio) {
                        filaProducto.find('.precio-servicio-input').val(precio_con_iva.toFixed(0));
                    }
                    
                    // Actualizar IVA
                    filaProducto.find('.columna-iva').text('$' + iva.toLocaleString());
                    
                    // Actualizar subtotal
                    filaProducto.find('td:last-child').prev().text('$' + subtotal_producto.toLocaleString());
                }

                console.log(`🔄 Fila actualizada para producto ${producto.nombre} (con IVA):`, {
                    precio_con_iva: precio_con_iva,
                    subtotal: subtotal_producto,
                    iva: iva
                });
            }

            // Función para actualizar campos ocultos (vista con IVA)
            function actualizarCamposOcultosIVA() {
                // Limpiar campos ocultos existentes
                $('input[name^="productos"]').remove();
                
                // Recrear campos ocultos con valores actualizados
                productos.forEach((producto, index) => {
                    $('#ventaForm').append(`
                        <input type="hidden" name="productos[${index}][id]" value="${producto.id}">
                        <input type="hidden" name="productos[${index}][cantidad]" value="${producto.cantidad}">
                        <input type="hidden" name="productos[${index}][precio]" value="${producto.precio_venta}">
                        <input type="hidden" name="productos[${index}][precio_final]" value="${producto.precio_final || producto.precio_venta}">
                        <input type="hidden" name="productos[${index}][precio_original]" value="${producto.precio_original || producto.precio_venta}">
                        <input type="hidden" name="productos[${index}][es_servicio]" value="${producto.es_servicio ? 1 : 0}">
                        <input type="hidden" name="productos[${index}][iva]" value="${producto.iva || 0}">
                    `);
                });
            }

            // Modificar la función actualizarTablaProductos
            function actualizarTablaProductos() {
                const tbody = $('#productos-table tbody');
                
                // Asegurar que las columnas de IVA siempre estén visibles
                $('.columna-iva').show();
                $('.fila-iva').show();
                
                tbody.empty();

                // Limpiar campos ocultos de productos anteriores
                $('input[name^="productos"]').remove();

                if (productos.length === 0) {
                    tbody.html(`<tr><td colspan="9" class="text-center">No hay productos agregados</td></tr>`);
                    return;
                }

                productos.forEach((producto, index) => {
                    const subtotal_producto = producto.cantidad * producto.precio_venta;
                    // El precio ya incluye IVA, así que extraemos el IVA del precio
                    const iva = Math.round(subtotal_producto * window.porcentajeIVA / (100 + window.porcentajeIVA));
                    const subtotal_sin_iva = subtotal_producto - iva;

                    tbody.append(`
                        <tr id="producto-${producto.id}">
                            <td>${producto.codigo}</td>
                            <td>${producto.nombre}</td>
                            <td class="text-center">${producto.stock}</td>
                            <td class="text-center">
                                <input type="number" 
                                       class="form-control cantidad-input" 
                                       value="${parseFloat(producto.cantidad).toFixed(3)}"
                                       min="0.001"
                                       step="0.001"
                                       max="${producto.stock}"
                                       onchange="actualizarCantidad(${index}, this.value)"
                                       oninput="recalcularEnTiempoReal(${index}, this.value)"
                                       onclick="this.select()"
                                       style="width: 120px; text-align: center; margin: 0 auto;">
                            </td>
                            <td class="text-center">
                                <select class="form-select form-select-sm selector-unidades" 
                                        data-producto-id="${producto.id}"
                                        data-index="${index}"
                                        onchange="cambiarUnidadNueva(${index}, this.value)"
                                        style="width: 80px; font-size: 11px;">
                                    <option value="${producto.unidad_medida || 'unidad'}" selected>
                                        ${(producto.unidad_medida || 'unidad').toUpperCase()}
                                    </option>
                                </select>
                            </td>
                            <td class="text-end">
                                ${producto.es_servicio ? 
                                    `<input type="number" 
                                           class="form-control precio-servicio-input" 
                                           value="${producto.precio_venta.toFixed(0)}"
                                           min="1"
                                           step="1"
                                           onchange="actualizarPrecioServicio(${index}, this.value)"
                                           onclick="this.select()"
                                           style="width: 120px; text-align: right; margin: 0 auto;"
                                           title="Precio editable para servicio (incluye IVA)">` 
                                    : 
                                    `$${producto.precio_venta.toLocaleString()}`
                                }
                            </td>
                            <td class="text-end columna-iva">${iva.toLocaleString()}</td>
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
                        <input type="hidden" name="productos[${index}][precio_final]" value="${producto.precio_venta}">
                        <input type="hidden" name="productos[${index}][precio_original]" value="${producto.precio_original || producto.precio_venta}">
                        <input type="hidden" name="productos[${index}][es_servicio]" value="${producto.es_servicio ? 1 : 0}">
                        <input type="hidden" name="productos[${index}][iva]" value="${producto.iva || 0}">
                    `);
                });

                // Cargar unidades disponibles para cada selector
                productos.forEach((producto, index) => {
                    cargarUnidadesDisponibles(producto.id, index);
                });

                calcularTotales();
            }

            // Modificar la función actualizarCantidad para que sea accesible globalmente
            window.actualizarCantidad = function(index, cantidad) {
                cantidad = parseFloat(cantidad);
                
                if (!productos[index]) {
                    console.error('Producto no encontrado en índice:', index);
                    return;
                }

                if (isNaN(cantidad) || cantidad < 0.001) cantidad = 0.001;
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

                // Redondear a 3 decimales
                productos[index].cantidad = Math.round(cantidad * 1000) / 1000;
                actualizarTablaProductos();
                calcularTotales(); // Asegurarnos que se actualicen los totales
            };

            // Función para recálculo en tiempo real mientras se escribe
            window.recalcularEnTiempoReal = function(index, cantidad) {
                if (!productos[index]) return;
                
                cantidad = parseFloat(cantidad);
                if (isNaN(cantidad) || cantidad < 0) return;
                
                // Actualizar solo los cálculos sin regenerar toda la tabla
                const fila = document.getElementById(`producto-${productos[index].id}`);
                if (!fila) return;
                
                const producto = productos[index];
                const subtotal_producto = cantidad * producto.precio_venta;
                // El precio ya incluye IVA, así que extraemos el IVA del precio
                const iva = Math.round(subtotal_producto * window.porcentajeIVA / (100 + window.porcentajeIVA));
                const subtotal_sin_iva = subtotal_producto - iva;
                
                // Actualizar subtotal en la fila
                const celdaSubtotal = fila.querySelector('td:nth-last-child(2)');
                if (celdaSubtotal) {
                    celdaSubtotal.textContent = subtotal_sin_iva.toLocaleString();
                }
                
                // Actualizar IVA (siempre visible en esta vista)
                const celdaIva = fila.querySelector('.columna-iva');
                if (celdaIva) {
                    celdaIva.textContent = iva.toLocaleString();
                }
                
                // Recalcular totales generales
                calcularTotalesRapido();
            };
            
            // Función para calcular totales sin regenerar tabla
            function calcularTotalesRapido() {
                let subtotal_sin_iva = 0;
                let iva_total = 0;
                let total = 0;

                // Leer valores actuales de la tabla
                document.querySelectorAll('#productos-table tbody tr').forEach(fila => {
                    if (fila.id && fila.id.startsWith('producto-')) {
                        const inputCantidad = fila.querySelector('.cantidad-input');
                        const celdaSubtotal = fila.querySelector('td:nth-last-child(2)');
                        const celdaIva = fila.querySelector('.columna-iva');
                        
                        if (inputCantidad && celdaSubtotal && celdaIva) {
                            const cantidad = parseFloat(inputCantidad.value) || 0;
                            const subtotalTexto = celdaSubtotal.textContent.replace(/[,]/g, '');
                            const subtotalProducto = parseFloat(subtotalTexto) || 0;
                            const ivaTexto = celdaIva.textContent.replace(/[,]/g, '');
                            const ivaProducto = parseFloat(ivaTexto) || 0;
                            
                            subtotal_sin_iva += subtotalProducto;
                            iva_total += ivaProducto;
                            total += (subtotalProducto + ivaProducto);
                        }
                    }
                });

                // Actualizar displays
                $('#subtotal-display').text(subtotal_sin_iva.toLocaleString());
                $('#iva-display').text(iva_total.toLocaleString());
                $('#total-display').text(total.toLocaleString());

                // Actualizar campos ocultos
                $('input[name="subtotal"]').val(subtotal_sin_iva);
                $('input[name="iva"]').val(iva_total);
                $('input[name="total"]').val(total);
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
                        actualizarTablaProductos();
                        calcularTotales();
                    }
                });
            }

            // Modificar la función calcularTotales
            function calcularTotales() {
                let total = 0;
                let subtotal = 0;
                let iva_total = 0;

                productos.forEach(producto => {
                    // El precio total (con IVA incluido)
                    const precio_total = producto.cantidad * producto.precio_venta;
                    total += precio_total;
                    
                    // El IVA está incluido en el precio, así que lo extraemos
                    const iva = Math.round(precio_total * window.porcentajeIVA / (100 + window.porcentajeIVA));
                    iva_total += iva;
                });

                // El subtotal es el total menos el IVA
                subtotal = total - iva_total;

                $('#subtotal-display').text(subtotal.toLocaleString());
                $('#iva-display').text(iva_total.toLocaleString());
                $('#total-display').text(total.toLocaleString());

                $('input[name="subtotal"]').val(subtotal);
                $('input[name="iva"]').val(iva_total);
                $('input[name="total"]').val(total);
                
                // Asegurar que las columnas de IVA tengan la visibilidad correcta
                inicializarColumnasIVA();
            }

            // Modificar el evento change del tipo de factura
            $('#tipo_factura').on('change', function() {
                const tipoFactura = $(this).val();
                
                // Asegurar que las columnas de IVA siempre estén visibles
                $('.columna-iva').show();
                $('.fila-iva').show();

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
                    // Procesar respuesta del servidor

                    try {
                        if (typeof response === 'string') {
                            response = JSON.parse(response);
                        }

                        // URLs de respuesta procesadas

                        // Verificar si hubo éxito en la venta pero error en la factura electrónica
                        if (response.success && response.fe_success === false) {
                            Swal.fire({
                                title: 'Venta Creada',
                                text: 'La venta se ha creado correctamente, pero hubo un error al generar la factura electrónica. ¿Desea intentar generar la factura electrónica más tarde?',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Sí, intentar más tarde',
                                cancelButtonText: 'No, continuar sin factura electrónica',
                                allowOutsideClick: false
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Redirigir a la página de la venta
                                    window.location.href = response.data.show_url || '/ventas/' + response.data.id;
                                } else {
                                    // Redirigir al listado de ventas o a nueva venta
                                    window.location.href = response.redirect_url || '/ventas/create';
                                }
                            });
                            return;
                        }

                        // Si todo fue exitoso
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
                                // Abrir ventana de impresión
                                const printUrl = response.print_url || `/ventas/print/${response.data.id}`;
                                const printWindow = window.open(printUrl, '_blank');
                                
                                if (!printWindow) {
                                    console.error('Bloqueador de popups detectado');
                                    alert('Por favor, permita las ventanas emergentes para imprimir');
                                    // Redirigir después de mostrar el mensaje
                                    window.location.href = response.redirect_url || '/ventas/create';
                                } else {
                                    // No redirigir automáticamente, permitir que el usuario vea la impresión
                                    // y luego decida manualmente volver a la página de ventas
                                }
                            } else {
                                // Redirigir inmediatamente si no se imprime
                                window.location.href = response.redirect_url || '/ventas/create';
                            }
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
        $('#modal-busqueda').val('');
        // Mostrar todas las filas al cerrar el modal
        $('#tabla-productos-modal tbody tr').show();
    });

    // Función de filtrado de productos en el modal
    $('#modal-busqueda').on('keyup input', function() {
        const filtro = $(this).val().toLowerCase().trim();
        console.log(' Filtrando productos con:', filtro);
        
        let productosVisibles = 0;
        
        $('#tabla-productos-modal tbody tr').each(function() {
            const fila = $(this);
            const codigo = fila.find('td:nth-child(1)').text().toLowerCase();
            const nombre = fila.find('td:nth-child(2)').text().toLowerCase();
            
            // Buscar en código y nombre
            const coincide = codigo.includes(filtro) || nombre.includes(filtro);
            
            if (coincide || filtro === '') {
                fila.show();
                productosVisibles++;
            } else {
                fila.hide();
            }
        });
        
        console.log(' Productos visibles:', productosVisibles);
        
        // Mostrar mensaje si no hay resultados
        if (productosVisibles === 0 && filtro !== '') {
            if ($('#no-resultados-modal').length === 0) {
                $('#tabla-productos-modal tbody').append(`
                    <tr id="no-resultados-modal">
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="fas fa-search fa-2x mb-2"></i><br>
                            No se encontraron productos que coincidan con "${filtro}"
                        </td>
                    </tr>
                `);
            }
        } else {
            $('#no-resultados-modal').remove();
        }
    });

    // Botón para limpiar búsqueda
    $('#limpiar-busqueda').on('click', function() {
        $('#modal-busqueda').val('').trigger('input').focus();
        console.log('🧹 Búsqueda limpiada');
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
            // Procesar respuesta del servidor

            try {
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }

                // URLs de respuesta procesadas

                // Verificar si hubo éxito en la venta pero error en la factura electrónica
                if (response.success && response.fe_success === false) {
                    Swal.fire({
                        title: 'Venta Creada',
                        text: 'La venta se ha creado correctamente, pero hubo un error al generar la factura electrónica. ¿Desea intentar generar la factura electrónica más tarde?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, intentar más tarde',
                        cancelButtonText: 'No, continuar sin factura electrónica',
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Redirigir a la página de la venta
                            window.location.href = response.data.show_url || '/ventas/' + response.data.id;
                        } else {
                            // Redirigir al listado de ventas o a nueva venta
                            window.location.href = response.redirect_url || '/ventas/create';
                        }
                    });
                    return;
                }

                // Si todo fue exitoso
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
                        // Abrir ventana de impresión
                        const printUrl = response.print_url || `/ventas/print/${response.data.id}`;
                        const printWindow = window.open(printUrl, '_blank');
                        
                        if (!printWindow) {
                            console.error('Bloqueador de popups detectado');
                            alert('Por favor, permita las ventanas emergentes para imprimir');
                            // Redirigir después de mostrar el mensaje
                            window.location.href = response.redirect_url || '/ventas/create';
                        } else {
                            // No redirigir automáticamente, permitir que el usuario vea la impresión
                            // y luego decida manualmente volver a la página de ventas
                        }
                    } else {
                        // Redirigir inmediatamente si no se imprime
                        window.location.href = response.redirect_url || '/ventas/create';
                    }
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

// Función para cargar unidades disponibles
function cargarUnidadesDisponibles(productoId, index) {
    $.ajax({
        url: '/api/conversiones/unidades-disponibles',
        method: 'GET',
        data: { producto_id: productoId },
        success: function(response) {
            if (response.success && response.data.unidades) {
                const selector = document.querySelector(`select[data-index="${index}"]`);
                if (selector) {
                    const unidadActual = selector.value;
                    
                    // Limpiar opciones existentes
                    selector.innerHTML = '';
                    
                    // Agregar nuevas opciones
                    response.data.unidades.forEach(unidad => {
                        const option = document.createElement('option');
                        option.value = unidad.codigo;
                        option.textContent = unidad.codigo.toUpperCase();
                        
                        if (unidad.codigo === unidadActual) {
                            option.selected = true;
                        }
                        
                        selector.appendChild(option);
                    });
                }
            }
        },
        error: function(xhr) {
            console.warn('No se pudieron cargar las unidades disponibles:', xhr);
            // Mantener la unidad actual si falla la carga
        }
    });
}

// Función NUEVA para cambiar unidad de medida - SIN CACHÉ
window.cambiarUnidadNueva = function(index, nuevaUnidad) {
    try {
        console.log('🚀🚀🚀 FUNCIÓN NUEVA SIN CACHÉ - FUNCIONANDO EN CREATE_IVA 🚀🚀🚀');
        console.log(`🔄 cambiarUnidad llamada - Índice: ${index}, Nueva unidad: ${nuevaUnidad}`);
        
        if (!productos[index]) {
            console.error('❌ Producto no encontrado en índice:', index);
            return;
        }
        
        const producto = productos[index];
        const unidadOriginal = producto.unidad_medida || 'unidad';
        
        console.log(`📦 Producto: ${producto.nombre}`);
        console.log(`🔄 Cambio: ${unidadOriginal} → ${nuevaUnidad}`);
        console.log(`📊 Cantidad actual: ${producto.cantidad}`);
        console.log(`🔍 Verificando unidades - Original: "${unidadOriginal}", Nueva: "${nuevaUnidad}"`);
        console.log(`🔍 Son iguales?: ${unidadOriginal === nuevaUnidad}`);
        
        // Si es la misma unidad, no hacer nada
        if (unidadOriginal === nuevaUnidad) {
            console.log('⚠️ Misma unidad, no se hace conversión');
            return;
        }
        
        console.log('✅ Unidades diferentes, continuando con conversión...');
    
        // Mostrar indicador de carga
        const selector = document.querySelector(`select[data-index="${index}"]`);
        console.log('🎯 Selector encontrado:', selector);
        if (selector) {
            selector.disabled = true;
            selector.style.opacity = '0.5';
            console.log('🔒 Selector deshabilitado');
        }
        
        // Siempre usar la API de conversiones
        console.log('🚀 Iniciando conversión via API...');
        console.log('📋 Llamando realizarConversionDirecta con:', {
            producto: producto.nombre,
            unidadOrigen: unidadOriginal,
            unidadDestino: nuevaUnidad,
            index: index
        });
        
        realizarConversionDirecta(producto, unidadOriginal, nuevaUnidad, index);
        console.log('✅ Llamada a realizarConversionDirecta completada');
        
    } catch (error) {
        console.error('❌ ERROR en cambiarUnidadNueva:', error);
        console.error('Stack trace:', error.stack);
        Swal.fire('Error', 'Error interno en cambiarUnidad: ' + error.message, 'error');
    }
};

// Función para realizar conversión directa
function realizarConversionDirecta(producto, unidadOrigen, unidadDestino, index) {
    try {
        console.log('🌐 Enviando petición AJAX a API...');
        console.log('📡 URL:', '/api/conversiones/convertir-unidad');
        console.log('📦 Datos:', {
            producto_id: producto.id,
            unidad_origen: unidadOrigen,
            unidad_destino: unidadDestino,
            cantidad: producto.cantidad,
            precio: producto.precio_venta
        });
    
    $.ajax({
        url: '/api/conversiones/convertir-unidad',
        method: 'POST',
        data: {
            producto_id: producto.id,
            unidad_origen: unidadOrigen,
            unidad_destino: unidadDestino,
            cantidad: producto.cantidad,
            precio: producto.precio_venta
        },
        success: function(response) {
            console.log('✅ Respuesta exitosa de la API:', response);
            
            if (response.success) {
                console.log('🎯 Conversión exitosa, actualizando producto...');
                
                // Guardar cantidad y precio originales para mostrar en notificación
                const cantidadOriginal = producto.cantidad;
                const unidadOriginal = unidadOrigen;
                const precioOriginal = producto.precio_venta;
                
                console.log('📊 Datos antes:', {cantidad: cantidadOriginal, precio: precioOriginal, unidad: unidadOriginal});
                console.log('📊 Datos nuevos:', {cantidad: response.data.cantidad_convertida, precio: response.data.precio_convertido, unidad: response.data.unidad_destino});
                
                // Actualizar datos del producto
                producto.cantidad = parseFloat(response.data.cantidad_convertida.toFixed(3));
                producto.precio_venta = parseFloat(response.data.precio_convertido.toFixed(2));
                producto.unidad_medida = response.data.unidad_destino;
                
                console.log('📝 Producto actualizado:', {
                    id: producto.id,
                    nombre: producto.nombre,
                    cantidad: producto.cantidad,
                    precio: producto.precio_venta,
                    unidad: producto.unidad_medida
                });
                
                // Actualizar la tabla
                console.log('🔄 Actualizando tabla de productos...');
                actualizarTablaProductos();
                console.log('🧮 Calculando totales...');
                calcularTotales();
                
                // FORZAR actualización manual del DOM
                console.log('🔧 Forzando actualización manual del DOM...');
                const fila = document.querySelector(`tr[data-producto-id="${producto.id}"]`);
                if (fila) {
                    // Actualizar cantidad
                    const celdaCantidad = fila.querySelector('input[type="number"]');
                    if (celdaCantidad) {
                        celdaCantidad.value = producto.cantidad.toFixed(3);
                        console.log('📝 Cantidad actualizada en DOM:', producto.cantidad);
                    }
                    
                    // Actualizar precio
                    const celdaPrecio = fila.querySelector('td:nth-child(6)'); // Columna precio
                    if (celdaPrecio) {
                        celdaPrecio.textContent = producto.precio_venta.toLocaleString();
                        console.log('💰 Precio actualizado en DOM:', producto.precio_venta);
                    }
                    
                    // Actualizar subtotal
                    const subtotal = producto.cantidad * producto.precio_venta;
                    const celdaSubtotal = fila.querySelector('td:nth-child(8)'); // Columna subtotal
                    if (celdaSubtotal) {
                        celdaSubtotal.textContent = subtotal.toLocaleString();
                        console.log('💵 Subtotal actualizado en DOM:', subtotal);
                    }
                }
                
                console.log('✅ Tabla y totales actualizados');
                
                // Mostrar notificación con detalles de la conversión
                Swal.fire({
                    icon: 'success',
                    title: 'Conversión Automática Realizada',
                    html: `
                        <div class="text-start">
                            <strong>Conversión:</strong><br>
                            ${cantidadOriginal} ${unidadOriginal.toUpperCase()} → <strong>${response.data.cantidad_convertida} ${response.data.unidad_destino.toUpperCase()}</strong><br><br>
                            <strong>Precio ajustado:</strong><br>
                            $${precioOriginal.toLocaleString()} por ${unidadOriginal} → <strong>$${response.data.precio_convertido.toLocaleString()} por ${response.data.unidad_destino}</strong><br><br>
                            <small class="text-muted">Factor: ${response.data.factor_conversion}</small><br>
                            ${response.data.descripcion ? '<small class="text-muted">' + response.data.descripcion + '</small>' : ''}
                        </div>
                    `,
                    timer: 3500,
                    showConfirmButton: false
                });
            } else {
                console.error('Error en conversión:', response.message);
                Swal.fire('Error', 'No se pudo realizar la conversión', 'error');
                
                // Revertir selector
                const selector = document.querySelector(`select[data-index="${index}"]`);
                if (selector) {
                    selector.value = unidadOrigen;
                }
            }
        },
        error: function(xhr) {
            console.error('❌ Error en conversión API:', xhr);
            console.error('📊 Status:', xhr.status);
            console.error('📝 Response:', xhr.responseText);
            console.error('📋 Ready State:', xhr.readyState);
            
            Swal.fire('Error', 'Error al conectar con el servidor: ' + xhr.status, 'error');
            
            // Revertir selector
            const selector = document.querySelector(`select[data-index="${index}"]`);
            if (selector) {
                selector.value = unidadOrigen;
            }
        },
        complete: function() {
            // Quitar indicador de carga
            const selector = document.querySelector(`select[data-index="${index}"]`);
            if (selector) {
                selector.disabled = false;
                selector.style.opacity = '1';
            }
        }
    });
    
    } catch (error) {
        console.error('❌ ERROR en realizarConversionDirecta:', error);
        console.error('Stack trace:', error.stack);
        
        // Quitar indicador de carga
        const selector = document.querySelector(`select[data-index="${index}"]`);
        if (selector) {
            selector.disabled = false;
            selector.style.opacity = '1';
            selector.value = unidadOrigen; // Revertir
        }
        
        Swal.fire('Error', 'Error interno en conversión: ' + error.message, 'error');
    }
}

});
</script>
@endpush