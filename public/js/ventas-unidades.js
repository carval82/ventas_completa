// Sistema de Unidades de Medida para Ventas
// Implementación modular que no interfiere con la funcionalidad básica

(function() {
    'use strict';
    
    // Configuración del sistema de unidades
    const UNIDADES_CONFIG = {
        // Unidades de peso
        'unidad': { tipo: 'peso', factor: 1, decimales: 0 },
        'kg': { tipo: 'peso', factor: 1, decimales: 3 },
        'g': { tipo: 'peso', factor: 0.001, decimales: 1 },
        'lb': { tipo: 'peso', factor: 0.453592, decimales: 3 },
        
        // Unidades de volumen
        'l': { tipo: 'volumen', factor: 1, decimales: 3 },
        'ml': { tipo: 'volumen', factor: 0.001, decimales: 0 },
        'cc': { tipo: 'volumen', factor: 0.001, decimales: 0 },
        
        // Unidades especiales
        'bulto': { tipo: 'especial', factor: 1, decimales: 0 },
        'docena': { tipo: 'especial', factor: 12, decimales: 0 },
        'caja': { tipo: 'especial', factor: 1, decimales: 0 }
    };
    
    // Variables del sistema
    let sistemUnidadesActivo = false;
    let conversionesCache = new Map();
    
    // Función para inicializar el sistema de unidades
    function inicializarSistemaUnidades() {
        console.log('Inicializando sistema de unidades de medida...');
        
        // Verificar si ya existe la funcionalidad básica
        if (typeof window.agregarProducto !== 'function') {
            console.warn('Funcionalidad básica no disponible. Esperando...');
            setTimeout(inicializarSistemaUnidades, 500);
            return;
        }
        
        // Guardar la función original
        window.agregarProductoOriginal = window.agregarProducto;
        
        // Extender la función agregarProducto
        window.agregarProducto = function(producto) {
            // Llamar a la función original primero
            window.agregarProductoOriginal(producto);
            
            // Agregar funcionalidad de unidades si está activa
            if (sistemUnidadesActivo) {
                agregarSelectorUnidades(producto);
            }
        };
        
        sistemUnidadesActivo = true;
        console.log('Sistema de unidades inicializado correctamente');
    }
    
    // Función para agregar selector de unidades a un producto
    function agregarSelectorUnidades(producto) {
        const filaProducto = document.getElementById(`producto-${producto.id}`);
        if (!filaProducto) return;
        
        // Buscar la celda de cantidad
        const celdaCantidad = filaProducto.querySelector('.cantidad-input').parentElement;
        
        // Crear selector de unidades si no existe
        let selectorUnidades = celdaCantidad.querySelector('.selector-unidades');
        if (!selectorUnidades) {
            selectorUnidades = crearSelectorUnidades(producto);
            celdaCantidad.appendChild(selectorUnidades);
        }
    }
    
    // Función para crear el selector de unidades
    function crearSelectorUnidades(producto) {
        const select = document.createElement('select');
        select.className = 'form-select form-select-sm selector-unidades mt-1';
        select.style.width = '80px';
        select.style.fontSize = '11px';
        
        // Obtener unidades compatibles con el producto
        const unidadesCompatibles = obtenerUnidadesCompatibles(producto);
        
        // Agregar opciones
        unidadesCompatibles.forEach(unidad => {
            const option = document.createElement('option');
            option.value = unidad;
            option.textContent = unidad.toUpperCase();
            
            // Seleccionar la unidad del producto por defecto
            if (unidad === (producto.unidad_medida || 'unidad')) {
                option.selected = true;
            }
            
            select.appendChild(option);
        });
        
        // Agregar evento de cambio
        select.addEventListener('change', function() {
            manejarCambioUnidad(producto.id, this.value);
        });
        
        return select;
    }
    
    // Función para obtener unidades compatibles
    function obtenerUnidadesCompatibles(producto) {
        const unidadBase = producto.unidad_medida || 'unidad';
        const tipoBase = UNIDADES_CONFIG[unidadBase]?.tipo || 'peso';
        
        // Retornar unidades del mismo tipo
        return Object.keys(UNIDADES_CONFIG).filter(unidad => 
            UNIDADES_CONFIG[unidad].tipo === tipoBase
        );
    }
    
    // Función para manejar cambio de unidad
    function manejarCambioUnidad(productoId, nuevaUnidad) {
        const producto = window.productos.find(p => p.id === productoId);
        if (!producto) return;
        
        const unidadOriginal = producto.unidad_medida || 'unidad';
        
        // Si es la misma unidad, no hacer nada
        if (unidadOriginal === nuevaUnidad) return;
        
        // Mostrar indicador de carga
        mostrarIndicadorCarga(productoId, true);
        
        // Realizar conversión
        realizarConversion(producto, unidadOriginal, nuevaUnidad)
            .then(resultado => {
                if (resultado.success) {
                    actualizarProductoConConversion(producto, resultado.data);
                } else {
                    console.error('Error en conversión:', resultado.error);
                    Swal.fire('Error', 'No se pudo realizar la conversión', 'error');
                }
            })
            .finally(() => {
                mostrarIndicadorCarga(productoId, false);
            });
    }
    
    // Función para realizar conversión usando API
    function realizarConversion(producto, unidadOrigen, unidadDestino) {
        return new Promise((resolve) => {
            // Usar API real para conversiones
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
                    if (response.success) {
                        resolve({
                            success: true,
                            data: {
                                cantidad_convertida: response.data.cantidad_convertida,
                                precio_convertido: response.data.precio_convertido,
                                unidad_nueva: response.data.unidad_destino,
                                factor_conversion: response.data.factor_conversion
                            }
                        });
                    } else {
                        resolve({ success: false, error: response.message });
                    }
                },
                error: function(xhr) {
                    console.error('Error en conversión API:', xhr);
                    // Fallback a conversión local si falla la API
                    realizarConversionLocal(producto, unidadOrigen, unidadDestino)
                        .then(resolve)
                        .catch(() => {
                            resolve({ success: false, error: 'Error en conversión' });
                        });
                }
            });
        });
    }
    
    // Función de conversión local como fallback
    function realizarConversionLocal(producto, unidadOrigen, unidadDestino) {
        return new Promise((resolve) => {
            const configOrigen = UNIDADES_CONFIG[unidadOrigen];
            const configDestino = UNIDADES_CONFIG[unidadDestino];
            
            if (!configOrigen || !configDestino) {
                resolve({ success: false, error: 'Unidad no válida' });
                return;
            }
            
            // Calcular factor de conversión
            const factor = configOrigen.factor / configDestino.factor;
            
            resolve({
                success: true,
                data: {
                    cantidad_convertida: producto.cantidad * factor,
                    precio_convertido: producto.precio_venta / factor,
                    unidad_nueva: unidadDestino,
                    factor_conversion: factor
                }
            });
        });
    }
    
    // Función para actualizar producto con conversión
    function actualizarProductoConConversion(producto, datosConversion) {
        // Actualizar datos del producto
        producto.cantidad = parseFloat(datosConversion.cantidad_convertida.toFixed(3));
        producto.precio_venta = parseFloat(datosConversion.precio_convertido.toFixed(2));
        producto.unidad_medida = datosConversion.unidad_nueva;
        
        // Actualizar la tabla
        if (typeof window.actualizarTablaProductos === 'function') {
            window.actualizarTablaProductos();
        }
        
        // Mostrar notificación
        Swal.fire({
            icon: 'success',
            title: 'Conversión realizada',
            text: `Producto convertido a ${datosConversion.unidad_nueva}`,
            timer: 1500,
            showConfirmButton: false
        });
    }
    
    // Función para mostrar indicador de carga
    function mostrarIndicadorCarga(productoId, mostrar) {
        const fila = document.getElementById(`producto-${productoId}`);
        if (!fila) return;
        
        const selector = fila.querySelector('.selector-unidades');
        if (!selector) return;
        
        if (mostrar) {
            selector.disabled = true;
            selector.style.opacity = '0.5';
        } else {
            selector.disabled = false;
            selector.style.opacity = '1';
        }
    }
    
    // Función para activar/desactivar el sistema
    function toggleSistemaUnidades(activar) {
        sistemUnidadesActivo = activar;
        
        if (activar) {
            console.log('Sistema de unidades activado');
        } else {
            console.log('Sistema de unidades desactivado');
            // Remover selectores existentes
            document.querySelectorAll('.selector-unidades').forEach(selector => {
                selector.remove();
            });
        }
    }
    
    // Función para verificar compatibilidad
    function verificarCompatibilidad() {
        const requisitos = [
            'jQuery' in window,
            'Swal' in window,
            'agregarProducto' in window,
            'productos' in window
        ];
        
        const compatible = requisitos.every(req => req);
        
        if (!compatible) {
            console.warn('Sistema de unidades: Algunos requisitos no están disponibles');
        }
        
        return compatible;
    }
    
    // Exponer API pública
    window.SistemaUnidades = {
        inicializar: inicializarSistemaUnidades,
        activar: () => toggleSistemaUnidades(true),
        desactivar: () => toggleSistemaUnidades(false),
        verificarCompatibilidad: verificarCompatibilidad,
        estado: () => sistemUnidadesActivo
    };
    
    // Auto-inicializar cuando el documento esté listo
    $(document).ready(function() {
        // Esperar un poco para que se cargue todo
        setTimeout(() => {
            if (verificarCompatibilidad()) {
                inicializarSistemaUnidades();
            }
        }, 1000);
    });
    
})();
