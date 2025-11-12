// Script de diagnóstico para identificar problemas en el sistema de ventas
console.log('=== DIAGNÓSTICO DEL SISTEMA DE VENTAS ===');

// Función para probar la selección de productos del modal
function probarSeleccionModal() {
    console.log('1. Probando selección de productos del modal...');
    
    // Verificar si existen los botones de selección
    const botonesSeleccion = document.querySelectorAll('.btn-seleccionar');
    console.log(`   - Botones de selección encontrados: ${botonesSeleccion.length}`);
    
    if (botonesSeleccion.length > 0) {
        const primerBoton = botonesSeleccion[0];
        console.log('   - Primer botón encontrado:', primerBoton);
        console.log('   - Data-producto del primer botón:', primerBoton.dataset.producto);
        console.log('   - Data-id del primer botón:', primerBoton.dataset.id);
        
        // Verificar si el evento click está asignado
        const eventos = $._data(primerBoton, 'events');
        console.log('   - Eventos asignados al botón:', eventos);
    }
    
    // Verificar si existe la función agregarProducto
    console.log('   - Función agregarProducto existe:', typeof window.agregarProducto !== 'undefined');
    console.log('   - Función agregarProducto (global):', typeof agregarProducto !== 'undefined');
}

// Función para probar el input de búsqueda/escaneo
function probarInputBusqueda() {
    console.log('2. Probando input de búsqueda/escaneo...');
    
    const inputBusqueda = document.getElementById('busqueda-producto');
    console.log('   - Input de búsqueda encontrado:', !!inputBusqueda);
    
    if (inputBusqueda) {
        console.log('   - Valor actual:', inputBusqueda.value);
        console.log('   - Eventos asignados:', $._data(inputBusqueda, 'events'));
        
        // Verificar funciones relacionadas
        console.log('   - Función buscarProductoLocal existe:', typeof buscarProductoLocal !== 'undefined');
        console.log('   - Función buscarProductoAPI existe:', typeof buscarProductoAPI !== 'undefined');
    }
}

// Función para probar variables globales
function probarVariablesGlobales() {
    console.log('3. Probando variables globales...');
    
    console.log('   - window.productos:', window.productos);
    console.log('   - window.listaProductos:', window.listaProductos ? window.listaProductos.length + ' productos' : 'No definida');
    console.log('   - window.aplicaIVA:', window.aplicaIVA);
    
    // Verificar si jQuery está cargado
    console.log('   - jQuery cargado:', typeof $ !== 'undefined');
    console.log('   - Versión de jQuery:', typeof $ !== 'undefined' ? $.fn.jquery : 'No disponible');
}

// Función para simular selección de producto
function simularSeleccionProducto() {
    console.log('4. Simulando selección de producto...');
    
    if (window.listaProductos && window.listaProductos.length > 0) {
        const primerProducto = window.listaProductos[0];
        console.log('   - Producto a simular:', primerProducto);
        
        try {
            if (typeof agregarProducto === 'function') {
                agregarProducto(primerProducto);
                console.log('   - ✓ Producto agregado exitosamente');
            } else {
                console.log('   - ✗ Función agregarProducto no disponible');
            }
        } catch (error) {
            console.log('   - ✗ Error al agregar producto:', error);
        }
    } else {
        console.log('   - ✗ No hay productos disponibles para simular');
    }
}

// Función para verificar el estado del modal
function verificarModal() {
    console.log('5. Verificando modal de productos...');
    
    const modal = document.getElementById('productosModal');
    console.log('   - Modal encontrado:', !!modal);
    
    if (modal) {
        const tabla = modal.querySelector('#tabla-productos-modal');
        console.log('   - Tabla del modal encontrada:', !!tabla);
        
        if (tabla) {
            const filas = tabla.querySelectorAll('tbody tr');
            console.log('   - Filas de productos en modal:', filas.length);
        }
    }
}

// Ejecutar todas las pruebas
function ejecutarDiagnostico() {
    console.log('Iniciando diagnóstico completo...');
    
    probarVariablesGlobales();
    probarSeleccionModal();
    probarInputBusqueda();
    verificarModal();
    simularSeleccionProducto();
    
    console.log('=== FIN DEL DIAGNÓSTICO ===');
}

// Ejecutar diagnóstico cuando el documento esté listo
$(document).ready(function() {
    setTimeout(ejecutarDiagnostico, 1000); // Esperar 1 segundo para que todo se cargue
});

// Exponer funciones para uso manual
window.diagnosticoVentas = {
    ejecutar: ejecutarDiagnostico,
    probarSeleccion: probarSeleccionModal,
    probarInput: probarInputBusqueda,
    probarVariables: probarVariablesGlobales,
    simularProducto: simularSeleccionProducto,
    verificarModal: verificarModal
};
