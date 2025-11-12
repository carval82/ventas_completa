/**
 * Script para manejar la funcionalidad responsive del sidebar
 */
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si estamos en una página de backup
    const esBackupPage = window.location.href.includes('/backup');
    
    // Si estamos en una página de backup, no ejecutar este script
    // para evitar conflictos con la funcionalidad de backup
    if (esBackupPage) {
        console.log('Página de backup detectada, no se aplicarán modificaciones del sidebar');
        return;
    }
    
    console.log('Script de sidebar móvil iniciado');
    
    // Crear el overlay para el sidebar si no existe
    if (!document.querySelector('.sidebar-overlay')) {
        const overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }

    // Obtener referencias a elementos
    const sidebar = document.getElementById('sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    let toggleBtn = document.getElementById('sidebarToggle');
    
    // Crear botón de toggle para el sidebar si no existe
    if (!toggleBtn) {
        toggleBtn = document.createElement('button');
        toggleBtn.id = 'sidebarToggle';
        toggleBtn.className = 'btn btn-link d-md-none';
        toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
        toggleBtn.style.position = 'fixed';
        toggleBtn.style.top = '10px';
        toggleBtn.style.left = '10px';
        toggleBtn.style.zIndex = '997';
        toggleBtn.style.backgroundColor = 'var(--primary-color)';
        toggleBtn.style.color = 'white';
        toggleBtn.style.borderRadius = '50%';
        toggleBtn.style.width = '40px';
        toggleBtn.style.height = '40px';
        toggleBtn.style.display = 'flex';
        toggleBtn.style.alignItems = 'center';
        toggleBtn.style.justifyContent = 'center';
        document.body.appendChild(toggleBtn);
    }

    // Función para mostrar/ocultar el sidebar
    function toggleSidebar() {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    }

    // Evento para el botón de toggle
    toggleBtn.addEventListener('click', toggleSidebar);

    // Evento para el overlay (cerrar al hacer clic fuera)
    overlay.addEventListener('click', toggleSidebar);

    // Cerrar el sidebar al hacer clic en un enlace (en dispositivos móviles)
    const sidebarLinks = sidebar.querySelectorAll('a.nav-link');
    if (window.innerWidth <= 768) {
        sidebarLinks.forEach(link => {
            // Solo para enlaces que no tienen submenús
            if (!link.classList.contains('dropdown-toggle')) {
                link.addEventListener('click', function() {
                    setTimeout(function() {
                        sidebar.classList.remove('active');
                        overlay.classList.remove('active');
                    }, 150); // Pequeño retraso para permitir la navegación
                });
            }
        });
    }

    // Ajustar el contenido principal
    const content = document.getElementById('content');
    if (content) {
        // Asegurarse de que el contenido tenga margen suficiente en móviles
        if (window.innerWidth <= 768) {
            content.style.marginLeft = '0';
            content.style.width = '100%';
        }
    }
    
    // Hacer que las tablas sean responsive automáticamente
    // Excluir las tablas que ya están en un contenedor responsive
    const tables = document.querySelectorAll('table:not(.table-responsive table)');
    tables.forEach(table => {
        // Verificar si la tabla ya está dentro de un div responsive
        if (!table.closest('.table-responsive')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });
    
    // Mejorar la experiencia en dispositivos móviles para modales
    if (window.innerWidth <= 768) {
        // Hacer que los modales sean más amigables en móviles
        const modales = document.querySelectorAll('.modal-dialog:not(.modal-xl)');
        modales.forEach(modal => {
            if (!modal.classList.contains('modal-fullscreen-sm-down')) {
                modal.classList.add('modal-fullscreen-sm-down');
            }
        });
        
        // Asegurarse de que los botones en modales sean lo suficientemente grandes para tocar
        const botonesModal = document.querySelectorAll('.modal .btn-sm');
        botonesModal.forEach(btn => {
            btn.classList.remove('btn-sm');
            btn.classList.add('btn');
            btn.style.marginBottom = '5px';
        });
    }
    
    // Manejar el problema de selección de productos en la vista de ventas
    if (document.getElementById('ventaForm')) {
        // Asegurarse de que la variable empresa esté disponible globalmente
        if (typeof window.empresa === 'undefined' && typeof empresa !== 'undefined') {
            window.empresa = empresa;
        }
        
        // Corregir la visibilidad de las columnas de IVA según el régimen
        const verificarRegimenEmpresa = function() {
            const tablaProductos = document.getElementById('tablaProductos');
            if (tablaProductos && window.empresa) {
                const esResponsableIVA = window.empresa.responsable_iva === 1;
                const columnasIVA = tablaProductos.querySelectorAll('.columna-iva');
                
                columnasIVA.forEach(col => {
                    col.style.display = esResponsableIVA ? '' : 'none';
                });
            }
        };
        
        // Verificar al cargar la página
        verificarRegimenEmpresa();
        
        // Verificar después de agregar productos
        if (typeof window.agregarProducto === 'function') {
            const originalAgregarProducto = window.agregarProducto;
            window.agregarProducto = function(producto) {
                const result = originalAgregarProducto(producto);
                verificarRegimenEmpresa();
                return result;
            };
        }
    }
    
    console.log('Script de sidebar móvil completado');
});
