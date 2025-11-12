@extends('layouts.app')

@section('title', 'Sistema de Respaldo')

@section('styles')
<style>
    .backup-btn {
        font-size: 1.1rem;
        padding: 10px 20px;
        margin: 10px 0;
        display: inline-block;
        width: auto;
        min-width: 200px;
    }
    
    @media (max-width: 768px) {
        .backup-btn {
            width: 100%;
            margin-top: 15px;
        }
    }
    
    /* Corregir z-index de modales */
    .modal {
        z-index: 1055 !important;
    }
    
    .modal-backdrop {
        z-index: 1050 !important;
    }
    
    /* Asegurar que los modales sean clickeables */
    .modal-dialog {
        pointer-events: auto !important;
        margin: 1.75rem auto !important;
    }
    
    .modal-content {
        pointer-events: auto !important;
        position: relative !important;
        z-index: 1056 !important;
        background-color: white !important;
        border: 1px solid rgba(0,0,0,.2) !important;
        border-radius: 0.375rem !important;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15) !important;
    }
    
    /* Específico para modal de restauración */
    #modalRestauracion {
        z-index: 1060 !important;
    }
    
    #modalRestauracion .modal-dialog {
        z-index: 1061 !important;
    }
    
    #modalRestauracion .modal-content {
        z-index: 1062 !important;
    }
    
    /* Asegurar que los botones sean clickeables */
    .btn {
        pointer-events: auto !important;
        position: relative !important;
        z-index: 1 !important;
    }
</style>
@endsection

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-3">Gestión de Backups</h5>
                </div>
                
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if (session('warning'))
                        <div class="alert alert-warning" role="alert">
                            {{ session('warning') }}
                        </div>
                    @endif
                    
                    <!-- Botón de crear backup -->
                    <div class="card mb-4 border-primary">
                        <div class="card-body bg-light">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="card-title">Crear nuevo backup de la base de datos</h5>
                                    <p class="card-text">Crea una copia de seguridad completa de la base de datos. Puedes optar por recibir el backup por correo electrónico.</p>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <form action="{{ route('backup.store') }}" method="POST">
                                        @csrf
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="send_email" id="sendEmail">
                                            <label class="form-check-label" for="sendEmail">
                                                Enviar por correo
                                            </label>
                                        </div>
                                        <button type="submit" class="btn btn-primary backup-btn">
                                            <i class="fas fa-database"></i> Crear Backup
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Configuración de correo electrónico -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Configuración de Correo Electrónico</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('backup.configure-email') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="input-group">
                                            <span class="input-group-text">Correo electrónico</span>
                                            <input type="email" class="form-control" name="email" value="{{ $backupEmail ?? '' }}" required placeholder="ejemplo@dominio.com">
                                        </div>
                                        <small class="text-muted">Los backups se enviarán a esta dirección de correo electrónico.</small>
                                    </div>
                                    <div class="col-md-4 text-md-end">
                                        <button type="submit" class="btn btn-info text-white">
                                            <i class="fas fa-envelope"></i> Guardar Configuración
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive mt-4">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Nombre del archivo</th>
                                    <th>Tamaño</th>
                                    <th>Fecha</th>
                                    <th>Antigüedad</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($backups as $backup)
                                    <tr>
                                        <td>{{ $backup['filename'] }}</td>
                                        <td>{{ $backup['size'] }}</td>
                                        <td>{{ $backup['date'] }}</td>
                                        <td>{{ $backup['age'] }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('backup.download', $backup['filename']) }}" class="btn btn-sm btn-info me-1">
                                                    <i class="fas fa-download"></i> Descargar
                                                </a>
                                                <button type="button" class="btn btn-sm btn-primary me-1" onclick="analizarBackup('{{ $backup['filename'] }}')">
                                                    <i class="fas fa-search"></i> Analizar
                                                </button>
                                                <button type="button" class="btn btn-sm btn-warning me-1" onclick="abrirModalRestauracion('{{ $backup['filename'] }}')">
                                                    <i class="fas fa-arrow-counterclockwise"></i> Restaurar
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $loop->index }}">
                                                    <i class="fas fa-trash"></i> Eliminar
                                                </button>
                                            </div>
                                            
                                            
                                            <!-- Modal de Eliminación -->
                                            <div class="modal fade" id="deleteModal{{ $loop->index }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $loop->index }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel{{ $loop->index }}">Confirmar Eliminación</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>¿Está seguro de que desea eliminar el backup "{{ $backup['filename'] }}"?</p>
                                                            <p>Esta acción no se puede deshacer.</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <form action="{{ route('backup.delete', $backup['filename']) }}" method="POST">
                                                                @csrf
                                                                <button type="submit" class="btn btn-danger">Sí, Eliminar</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No hay backups disponibles</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Información sobre backups -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="mb-0">Información sobre Backups</h6>
                        </div>
                        <div class="card-body">
                            <p>El sistema está configurado para realizar backups manuales. Para crear un backup:</p>
                            <ol>
                                <li>Haga clic en el botón "Crear Backup" en la parte superior de esta página.</li>
                                <li>Si desea recibir el backup por correo electrónico, marque la casilla "Enviar por correo".</li>
                                <li>El sistema creará una copia de seguridad de la base de datos completa.</li>
                            </ol>
                            <p>Se mantendrán los últimos 10 backups en el sistema. Los más antiguos se eliminarán automáticamente.</p>
                            
                            <div class="alert alert-info mt-3">
                                <p><strong>Sobre la restauración de datos:</strong></p>
                                <p>Si solo necesita restaurar los datos sin afectar la estructura de la base de datos, utilice la opción "Restaurar Solo Datos". Esta opción es más segura y mantiene las mejoras en la estructura de tablas.</p>
                                <p>El sistema procesa los datos en lotes de 50 registros para reducir la carga de memoria y evita problemas con tablas grandes como la de productos.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<!-- Modal de Análisis de Backup -->
<div class="modal fade" id="analisisModal" tabindex="-1" aria-labelledby="analisisModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="analisisModalLabel">
                    <i class="fas fa-search"></i> Análisis de Backup: <span id="backup-nombre"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Spinner de carga -->
                <div id="analisis-loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-3">Analizando el contenido del backup...</p>
                </div>
                
                <!-- Contenido del análisis -->
                <div id="analisis-contenido" style="display: none;">
                    <!-- Resumen general -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-primary h-100">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Información General</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tbody>
                                            <tr>
                                                <th width="40%">Archivo:</th>
                                                <td id="info-filename"></td>
                                            </tr>
                                            <tr>
                                                <th>Tamaño:</th>
                                                <td id="info-size"></td>
                                            </tr>
                                            <tr>
                                                <th>Fecha de creación:</th>
                                                <td id="info-date"></td>
                                            </tr>
                                            <tr>
                                                <th>Versión MySQL:</th>
                                                <td id="info-mysql-version"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-success h-100">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-database"></i> Estadísticas</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6 mb-3">
                                            <h6 class="text-muted">Total de Tablas</h6>
                                            <h2 id="stats-total-tablas" class="mb-0"></h2>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <h6 class="text-muted">Total de Registros</h6>
                                            <h2 id="stats-total-registros" class="mb-0"></h2>
                                        </div>
                                    </div>
                                    <div class="alert alert-info mt-2">
                                        <i class="fas fa-lightbulb"></i> Este backup contiene datos que pueden ser restaurados en el sistema.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Categorías de tablas -->
                    <div id="categorias-container">
                        <!-- Las categorías se cargarán dinámicamente aquí -->
                    </div>
                </div>
                
                <!-- Error -->
                <div id="analisis-error" class="alert alert-danger" style="display: none;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span id="error-message"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
                <button type="button" class="btn btn-success" id="btn-restaurar-desde-analisis">
                    <i class="fas fa-upload"></i> Restaurar este Backup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Restauración Único -->
<div class="modal fade" id="modalRestauracion" tabindex="-1" aria-labelledby="modalRestauracionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="modalRestauracionLabel">
                    <i class="fas fa-arrow-counterclockwise"></i> Opciones de Restauración
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <h6 class="fw-bold">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Restaurar backup: <span id="nombreBackupRestaurar"></span>
                    </h6>
                    <p class="mb-0">Seleccione el tipo de restauración que desea realizar:</p>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-danger h-100">
                            <div class="card-header bg-danger text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-exclamation-circle"></i> Restauración Completa
                                </h6>
                            </div>
                            <div class="card-body">
                                <p><strong>¡ADVERTENCIA!</strong> Esta acción restaurará completamente la base de datos.</p>
                                <p class="small">Todos los datos actuales y la estructura de tablas serán reemplazados. <strong>Esta acción no se puede deshacer.</strong></p>
                                <div class="text-center">
                                    <button type="button" class="btn btn-danger" id="btnRestauracionCompleta">
                                        <i class="fas fa-arrow-repeat"></i> Restauración Completa
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border-success h-100">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-check-circle"></i> Restauración Solo Datos
                                </h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Recomendado:</strong> Restaura solo los datos preservando la estructura actual.</p>
                                <p class="small">Ideal para importar datos de versiones anteriores sin perder mejoras en la estructura.</p>
                                <div class="text-center">
                                    <button type="button" class="btn btn-success" id="btnRestauracionDatos">
                                        <i class="fas fa-database"></i> Restaurar Solo Datos
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Inicializar los tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Corregir problemas de modales
    $(document).on('show.bs.modal', '.modal', function() {
        const zIndex = 1040 + (10 * $('.modal:visible').length);
        $(this).css('z-index', zIndex);
        setTimeout(() => {
            $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
        }, 0);
    });
    
    // Limpiar backdrop al cerrar modal
    $(document).on('hidden.bs.modal', '.modal', function() {
        $('.modal-backdrop').remove();
        if ($('.modal:visible').length > 0) {
            $('body').addClass('modal-open');
        }
    });
    
    // Variable global para el archivo de backup actual
    let backupActual = '';
    
    // Función para abrir modal de restauración
    window.abrirModalRestauracion = function(filename) {
        backupActual = filename;
        $('#nombreBackupRestaurar').text(filename);
        
        const modalRestauracion = new bootstrap.Modal(document.getElementById('modalRestauracion'));
        modalRestauracion.show();
    };
    
    // Manejar botón de restauración completa
    $('#btnRestauracionCompleta').on('click', function() {
        if (confirm('¿Está ABSOLUTAMENTE SEGURO de que desea realizar una restauración completa? Esta acción NO SE PUEDE DESHACER.')) {
            // Crear formulario dinámico
            const form = $('<form>', {
                'method': 'POST',
                'action': '{{ route("backup.restore", "") }}/' + backupActual
            });
            
            form.append($('<input>', {
                'type': 'hidden',
                'name': '_token',
                'value': '{{ csrf_token() }}'
            }));
            
            // Agregar al body y enviar
            $('body').append(form);
            form.submit();
        }
    });
    
    // Manejar botón de restauración solo datos
    $('#btnRestauracionDatos').on('click', function() {
        if (confirm('¿Confirma que desea restaurar solo los datos del backup "' + backupActual + '"?')) {
            // Crear formulario dinámico
            const form = $('<form>', {
                'method': 'POST',
                'action': '{{ route("backup.restore-data", "") }}/' + backupActual
            });
            
            form.append($('<input>', {
                'type': 'hidden',
                'name': '_token',
                'value': '{{ csrf_token() }}'
            }));
            
            // Agregar al body y enviar
            $('body').append(form);
            form.submit();
        }
    });
    
    // Función para analizar backup
    window.analizarBackup = function(filename) {
        // Mostrar modal
        const analisisModal = new bootstrap.Modal(document.getElementById('analisisModal'));
        analisisModal.show();
        
        // Actualizar nombre del backup
        $('#backup-nombre').text(filename);
        
        // Mostrar spinner de carga
        $('#analisis-loading').show();
        $('#analisis-contenido').hide();
        $('#analisis-error').hide();
        
        // Realizar petición AJAX
        $.ajax({
            url: `{{ route('backup.analizar', '') }}/${filename}`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    mostrarAnalisisBackup(response.data);
                } else {
                    mostrarErrorAnalisis(response.message);
                }
            },
            error: function(xhr) {
                let mensaje = 'Error al analizar el backup';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    mensaje = xhr.responseJSON.message;
                }
                mostrarErrorAnalisis(mensaje);
            }
        });
        
        // Configurar botón de restaurar
        $('#btn-restaurar-desde-analisis').off('click').on('click', function() {
            analisisModal.hide();
            
            // Abrir modal de restauración
            $(`button[data-bs-target="#restoreModal0"]`).click();
        });
    }
    
    // Función para mostrar el análisis
    function mostrarAnalisisBackup(data) {
        // Ocultar spinner y mostrar contenido
        $('#analisis-loading').hide();
        $('#analisis-contenido').show();
        
        // Actualizar información general
        $('#info-filename').text(data.archivo.nombre);
        $('#info-size').text(data.archivo.tamano);
        $('#info-date').text(data.archivo.fecha);
        $('#info-mysql-version').text(data.version_mysql || 'No disponible');
        
        // Actualizar estadísticas
        $('#stats-total-tablas').text(data.total_tablas.toLocaleString());
        $('#stats-total-registros').text(data.total_registros.toLocaleString());
        
        // Generar contenido de categorías
        const categoriasContainer = $('#categorias-container');
        categoriasContainer.empty();
        
        if (data.categorias && data.categorias.length > 0) {
            data.categorias.forEach(function(categoria) {
                const categoriaHtml = generarHtmlCategoria(categoria);
                categoriasContainer.append(categoriaHtml);
            });
        } else {
            categoriasContainer.html('<div class="alert alert-warning">No se encontraron tablas en este backup.</div>');
        }
    }
    
    // Función para mostrar error de análisis
    function mostrarErrorAnalisis(mensaje) {
        $('#analisis-loading').hide();
        $('#analisis-contenido').hide();
        $('#analisis-error').show();
        $('#error-message').text(mensaje);
    }
    
    // Función para generar el HTML de una categoría
    function generarHtmlCategoria(categoria) {
        let html = `
            <div class="card mb-4 border-${categoria.color}">
                <div class="card-header bg-${categoria.color} text-white">
                    <h5 class="mb-0">
                        <i class="fas ${categoria.icono}"></i> ${categoria.nombre}
                        <span class="badge bg-light text-dark float-end">${categoria.total_registros.toLocaleString()} registros</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Tabla</th>
                                    <th class="text-center">Registros</th>
                                </tr>
                            </thead>
                            <tbody>
        `;
        
        // Añadir filas de tablas
        categoria.tablas.forEach(function(tabla) {
            html += `
                <tr>
                    <td>${tabla.nombre}</td>
                    <td class="text-center">${tabla.registros.toLocaleString()}</td>
                </tr>
            `;
        });
        
        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
        
        return html;
    }
});
</script>
@endpush