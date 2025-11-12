/**
 * Script de corrección para la página de ventas
 * Este script soluciona problemas con la selección de productos y el escáner
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Script de corrección cargado correctamente');
    
    // Corregir la selección de productos en el catálogo
    $(document).off('click', '.btn-seleccionar').on('click', '.btn-seleccionar', function() {
        console.log('Botón de selección clickeado');
        
        // Obtener el ID del producto
        const productoId = $(this).data('id');
        console.log('ID del producto:', productoId);
        
        // Buscar el producto en la lista global
        const producto = window.listaProductos.find(p => p.id == productoId);
        
        if (producto) {
            console.log('Producto encontrado:', producto);
            agregarProducto(producto);
            
            // Cerrar el modal después de seleccionar
            $('#productosModal').modal('hide');
        } else {
            console.error('No se encontró el producto con ID:', productoId);
            alert('Error: No se pudo encontrar el producto seleccionado');
        }
    });
    
    // Corregir el escáner de productos
    $('#busqueda-producto').off('keypress').on('keypress', function(e) {
        if (e.which === 13) { // Enter
            e.preventDefault();
            e.stopPropagation();
            
            const busqueda = $(this).val().trim().toLowerCase();
            console.log('Búsqueda de producto:', busqueda);
            
            if (!busqueda) return;
            
            // Primero buscar por código exacto (para código de barras)
            let producto = window.listaProductos.find(p => 
                p.codigo && p.codigo.toLowerCase() === busqueda
            );
            
            // Si no encuentra por código, buscar por nombre
            if (!producto) {
                producto = window.listaProductos.find(p => 
                    p.nombre && p.nombre.toLowerCase().includes(busqueda)
                );
            }
            
            if (producto) {
                console.log('Producto encontrado por búsqueda:', producto);
                agregarProducto(producto);
                $(this).val('').focus(); // Limpiar y mantener foco
            } else {
                console.log('Producto no encontrado:', busqueda);
                alert('No se encontró ningún producto con ese código o nombre');
                $(this).select(); // Seleccionar texto para facilitar nueva búsqueda
            }
        }
    });
    
    // Asegurar que el botón de búsqueda también funcione
    $('#btn-buscar').off('click').on('click', function() {
        const busqueda = $('#busqueda-producto').val().trim().toLowerCase();
        console.log('Búsqueda por botón:', busqueda);
        
        if (!busqueda) return;
        
        // Primero buscar por código exacto
        let producto = window.listaProductos.find(p => 
            p.codigo && p.codigo.toLowerCase() === busqueda
        );
        
        // Si no encuentra por código, buscar por nombre
        if (!producto) {
            producto = window.listaProductos.find(p => 
                p.nombre && p.nombre.toLowerCase().includes(busqueda)
            );
        }
        
        if (producto) {
            console.log('Producto encontrado por búsqueda de botón:', producto);
            agregarProducto(producto);
            $('#busqueda-producto').val('').focus();
        } else {
            console.log('Producto no encontrado (búsqueda por botón):', busqueda);
            alert('No se encontró ningún producto con ese código o nombre');
            $('#busqueda-producto').select();
        }
    });
    
    // Asegurar que el input de búsqueda mantenga el foco
    setTimeout(function() {
        $('#busqueda-producto').focus();
    }, 500);
    
    console.log('Correcciones aplicadas correctamente');
});
