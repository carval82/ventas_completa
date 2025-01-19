// productos.js
document.addEventListener('DOMContentLoaded', function() {
    console.log('Productos.js cargado');

    // Manejador del formulario de producto
    function initProductForm() {
        const btnGuardar = document.getElementById('guardarProducto');
        const formProducto = document.getElementById('productoForm');

        if (!btnGuardar || !formProducto) {
            console.error('Elementos del formulario no encontrados');
            return;
        }

        btnGuardar.addEventListener('click', handleProductSave);
        initFormValidation();
    }

    // Función para manejar el guardado
    function handleProductSave(e) {
        e.preventDefault();
        console.log('Iniciando guardado de producto');

        const formData = getFormData();
        if (!validateFormData(formData)) return;

        saveProduct(formData);
    }

    // Obtener datos del formulario
    function getFormData() {
        return {
            _token: document.querySelector('meta[name="csrf-token"]')?.content,
            codigo: document.getElementById('codigo')?.value?.trim(),
            nombre: document.getElementById('nombre')?.value?.trim(),
            descripcion: document.getElementById('descripcion')?.value?.trim(),
            precio_compra: parseFloat(document.getElementById('precio_compra')?.value || 0),
            precio_venta: parseFloat(document.getElementById('precio_venta')?.value || 0),
            stock_minimo: parseInt(document.getElementById('stock_minimo')?.value || 0),
            stock: 0
        };
    }

    // Validar datos
    function validateFormData(data) {
        if (!data.codigo || !data.nombre || !data.descripcion) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Todos los campos son obligatorios'
            });
            return false;
        }

        if (data.precio_venta <= data.precio_compra) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'El precio de venta debe ser mayor al precio de compra'
            });
            return false;
        }

        return true;
    }

    // Guardar producto
    function saveProduct(formData) {
        console.log('Enviando datos:', formData);

        fetch('/api/productos', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': formData._token
            },
            body: JSON.stringify(formData)
        })
        .then(response => {
            if (!response.ok) throw new Error('Error en la respuesta del servidor');
            return response.json();
        })
        .then(data => {
            console.log('Producto creado:', data);
            handleSuccess(data);
        })
        .catch(error => handleError(error));
    }

    // Manejar éxito
    function handleSuccess(data) {
        // Limpiar formulario
        document.getElementById('productoForm').reset();
        
        // Cerrar modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('nuevoProductoModal'));
        if (modal) modal.hide();
        
        // Notificar éxito
        Swal.fire({
            icon: 'success',
            title: 'Éxito',
            text: 'Producto creado correctamente',
            timer: 1500
        });

        // Emitir evento de producto creado
        document.dispatchEvent(new CustomEvent('productoCreado', { detail: data }));
    }

    // Manejar error
    function handleError(error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Error al crear el producto'
        });
    }

    // Inicializar validación de formulario
    function initFormValidation() {
        const form = document.getElementById('productoForm');
        if (!form) return;

        form.querySelectorAll('input, textarea').forEach(element => {
            element.addEventListener('input', function() {
                if (this.checkValidity()) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                }
            });
        });
    }

    // Inicializar
    initProductForm();
});