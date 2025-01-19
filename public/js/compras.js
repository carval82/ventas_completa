document.addEventListener('DOMContentLoaded', function() {
    console.log('Script de compras iniciado');

    // Variables globales
    window.productos = [];
    window.listaProductos = [];

    // Inicializar Select2
    $('.select2').select2({
        placeholder: 'Seleccionar proveedor...',
        allowClear: true
    });

    // Búsqueda de productos con Enter
    $('#busqueda-producto').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            const busqueda = $(this).val().trim().toLowerCase();
            
            if (!busqueda) return;

            const producto = window.listaProductos.find(p => 
                p.codigo.toLowerCase() === busqueda || 
                p.nombre.toLowerCase().includes(busqueda)
            );

            if (producto) {
                seleccionarProducto(producto);
                $(this).val('').focus();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Producto no encontrado',
                    text: 'No se encontró ningún producto con ese código o nombre'
                });
            }
        }
    });

    // Selección de producto desde el catálogo
    $('.btn-seleccionar').on('click', function() {
        const producto = $(this).data('producto');
        seleccionarProducto(producto);
        $('#productosModal').modal('hide');
    });

    // Búsqueda en el modal de catálogo
    $('#modal-busqueda').on('keyup', function() {
        const busqueda = $(this).val().toLowerCase();
        $('#tabla-productos-modal tbody tr').each(function() {
            const texto = $(this).text().toLowerCase();
            $(this).toggle(texto.indexOf(busqueda) > -1);
        });
    });

    // Validación del formulario de compra
    $('#compraForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!window.productos.length) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe agregar al menos un producto'
            });
            return;
        }

        if (!$('select[name="proveedor_id"]').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe seleccionar un proveedor'
            });
            return;
        }

        if (!$('input[name="numero_factura"]').val().trim()) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe ingresar el número de factura'
            });
            return;
        }

        this.submit();
    });

    // Función para seleccionar un producto
    window.seleccionarProducto = function(producto) {
        console.log('Seleccionando producto:', producto);
        
        if (!producto) {
            console.error('Producto inválido');
            return;
        }

        const productoExistente = window.productos.find(p => p.id === producto.id);
        
        if (productoExistente) {
            productoExistente.cantidad += 1;
            productoExistente.subtotal = productoExistente.cantidad * productoExistente.precio;
        } else {
            window.productos.push({
                id: producto.id,
                codigo: producto.codigo,
                nombre: producto.nombre,
                cantidad: 1,
                precio: parseFloat(producto.precio_compra),
                iva_valor: parseFloat(producto.precio_compra) * 0.19,
                subtotal: parseFloat(producto.precio_compra)
            });
        }

        actualizarTabla();
        calcularTotales();
    };

    // Función para actualizar la tabla de productos
    function actualizarTabla() {
        const tbody = $('#productos-table tbody');
        tbody.empty();

        if (!window.productos.length) {
            tbody.html('<tr><td colspan="7" class="text-center">No hay productos agregados</td></tr>');
            return;
        }

        window.productos.forEach((producto, index) => {
            const subtotal = producto.cantidad * producto.precio;
            const iva = subtotal * 0.19;

            tbody.append(`
                <tr>
                    <td>${producto.codigo}</td>
                    <td>${producto.nombre}</td>
                    <td class="text-center">
                        <input type="number" 
                               class="form-control form-control-sm cantidad-input" 
                               value="${producto.cantidad}" 
                               min="1" 
                               onchange="actualizarCantidad(${index}, this.value)">
                    </td>
                    <td class="text-end">${producto.precio.toLocaleString('es-CO')}</td>
                    <td class="text-end">${iva.toLocaleString('es-CO')}</td>
                    <td class="text-end">${subtotal.toLocaleString('es-CO')}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm" onclick="eliminarProducto(${index})">
                            <i class="fas fa-trash"></i>
                        </button>
                        <input type="hidden" name="productos[${index}][id]" value="${producto.id}">
                        <input type="hidden" name="productos[${index}][cantidad]" value="${producto.cantidad}">
                        <input type="hidden" name="productos[${index}][precio]" value="${producto.precio}">
                    </td>
                </tr>
            `);
        });
    }

    // Función para calcular totales
    function calcularTotales() {
        let subtotal = 0;
        let iva_total = 0;

        window.productos.forEach(producto => {
            const subtotal_producto = producto.cantidad * producto.precio;
            subtotal += subtotal_producto;
            iva_total += subtotal_producto * 0.19;
        });

        const total = subtotal + iva_total;

        // Actualizar displays
        $('#subtotal-display').text(subtotal.toLocaleString('es-CO'));
        $('#iva-display').text(iva_total.toLocaleString('es-CO'));
        $('#total-display').text(total.toLocaleString('es-CO'));

        // Actualizar inputs hidden
        $('input[name="subtotal"]').val(subtotal.toFixed(2));
        $('input[name="iva"]').val(iva_total.toFixed(2));
        $('input[name="total"]').val(total.toFixed(2));
    }

    // Funciones globales para actualizar cantidad y eliminar productos
    window.actualizarCantidad = function(index, cantidad) {
        cantidad = parseInt(cantidad);
        if (cantidad < 1) cantidad = 1;
        
        window.productos[index].cantidad = cantidad;
        window.productos[index].subtotal = cantidad * window.productos[index].precio;
        
        actualizarTabla();
        calcularTotales();
    };

    window.eliminarProducto = function(index) {
        window.productos.splice(index, 1);
        actualizarTabla();
        calcularTotales();
    };

    // Escuchar evento de producto creado desde productos.js
    document.addEventListener('productoCreado', function(e) {
        const producto = e.detail;
        console.log('Nuevo producto creado:', producto);
        window.listaProductos.push(producto);
        seleccionarProducto(producto);
    });

    // Inicializar lista de productos
    window.listaProductos = @json($productos ?? []);
});