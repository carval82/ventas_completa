/**
 * Script para mejorar la experiencia de backup en dispositivos móviles
 * sin interferir con su funcionalidad
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Script de mejoras para backup en móviles cargado');
    
    // Mejorar la experiencia en dispositivos móviles para modales de backup
    if (window.innerWidth <= 768) {
        // Hacer que los modales sean más amigables en móviles
        const modales = document.querySelectorAll('.modal-dialog');
        modales.forEach(modal => {
            if (!modal.classList.contains('modal-dialog-scrollable')) {
                modal.classList.add('modal-dialog-scrollable');
            }
        });
        
        // Asegurarse de que los botones en modales sean lo suficientemente grandes para tocar
        const botonesModal = document.querySelectorAll('.modal .btn-sm');
        botonesModal.forEach(btn => {
            btn.style.minHeight = '38px';
            btn.style.marginBottom = '5px';
        });
        
        // Hacer que las tablas sean responsive
        const tablas = document.querySelectorAll('table:not(.table-responsive table)');
        tablas.forEach(tabla => {
            if (!tabla.closest('.table-responsive')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'table-responsive';
                tabla.parentNode.insertBefore(wrapper, tabla);
                wrapper.appendChild(tabla);
            }
        });
    }
    
    // Asegurarse de que los formularios de backup funcionen correctamente
    const formulariosBackup = document.querySelectorAll('form[action*="backup"]');
    formulariosBackup.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Verificar si el formulario tiene un action válido
            if (!form.action || form.action === '') {
                console.error('Formulario sin action:', form);
                e.preventDefault();
                alert('Error: El formulario no tiene una acción definida');
                return;
            }
            
            console.log('Formulario de backup enviándose a:', form.action);
            
            // Asegurarse de que el token CSRF esté presente
            const csrfToken = form.querySelector('input[name="_token"]');
            if (!csrfToken || csrfToken.value === '') {
                console.error('Formulario sin token CSRF:', form);
                // No detener el envío, Laravel mostrará un error más claro
            }
        });
    });
    
    // Mejorar la experiencia de los botones de backup
    const botonesBackup = document.querySelectorAll('.backup-btn');
    botonesBackup.forEach(btn => {
        if (window.innerWidth <= 768) {
            btn.style.width = '100%';
            btn.style.margin = '10px 0';
            btn.style.padding = '12px 20px';
        }
    });
    
    console.log('Mejoras para backup en móviles aplicadas');
});
