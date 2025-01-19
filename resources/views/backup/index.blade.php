@extends('layouts.app')

@section('title', 'Sistema de Respaldo')

@section('styles')
<style>
    .backup-card {
        transition: transform .2s;
    }
    .backup-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .stat-card {
        border-left: 4px solid;
    }
    .actions-btn {
        width: 32px;
        height: 32px;
        padding: 0;
        line-height: 32px;
        text-align: center;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Stats Row -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card border-primary">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h6 class="text-primary mb-0">Total Backups</h6>
                            <h3 class="mt-2 mb-0">{{ $backups->count() }}</h3>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-database fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card border-success">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h6 class="text-success mb-0">Último Backup</h6>
                            <h5 class="mt-2 mb-0">
                                {{ $backups->first() ? $backups->first()['date']->diffForHumans() : 'No hay backups' }}
                            </h5>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card border-info">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h6 class="text-info mb-0">Espacio Total</h6>
                            <h3 class="mt-2 mb-0">{{ number_format($backups->sum('size') / 1048576, 2) }} MB</h3>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hdd fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card border-warning">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h6 class="text-warning mb-0">Estado</h6>
                            <h5 class="mt-2 mb-0">{{ $backups->count() > 0 ? 'Activo' : 'Sin Backups' }}</h5>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <!-- Backup List -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list"></i> Lista de Backups
                        </h5>
                        <a href="{{ route('backup.create') }}" class="btn btn-light">
                            <i class="fas fa-plus"></i> Nuevo Backup
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($backups->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Tamaño</th>
                                        <th>Creado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($backups as $backup)
                                        <tr>
                                            <td>
                                                <i class="fas fa-file-archive text-primary"></i>
                                                {{ $backup['filename'] }}
                                            </td>
                                            <td>{{ number_format($backup['size'] / 1048576, 2) }} MB</td>
                                            <td>{{ $backup['date']->format('d/m/Y H:i:s') }}</td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="{{ route('backup.download', $backup['filename']) }}" 
                                                       class="btn btn-sm btn-info" 
                                                       title="Descargar">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    <button type="button"
                                                            class="btn btn-sm btn-danger" 
                                                            onclick="confirmarEliminar('{{ $backup['filename'] }}')"
                                                            title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-database fa-3x text-muted mb-3"></i>
                            <h5>No hay backups disponibles</h5>
                            <p class="text-muted">Cree su primer backup haciendo clic en "Nuevo Backup"</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Actions Panel -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt"></i> Acciones Rápidas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <button type="button" 
                                class="list-group-item list-group-item-action"
                                data-bs-toggle="modal" 
                                data-bs-target="#restoreModal">
                            <i class="fas fa-upload text-primary"></i> Restaurar Backup
                        </button>
                        <a href="{{ route('backup.create') }}" 
                           class="list-group-item list-group-item-action">
                            <i class="fas fa-plus text-success"></i> Crear Nuevo Backup
                        </a>
                        <button type="button" 
                                class="list-group-item list-group-item-action"
                                onclick="window.location.reload()">
                            <i class="fas fa-sync text-info"></i> Actualizar Lista
                        </button>
                    </div>
                </div>
            </div>

            <!-- Info Panel -->
            <div class="card">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle"></i> Información
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-lightbulb"></i> Tips:</h6>
                        <ul class="mb-0">
                            <li>Realice backups regularmente</li>
                            <li>Guarde copias en ubicaciones seguras</li>
                            <li>Verifique la integridad de los backups</li>
                        </ul>
                    </div>
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle"></i> Importante:</h6>
                        <p class="mb-0">La restauración sobrescribirá todos los datos actuales. Proceda con precaución.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Restore Modal -->
<div class="modal fade" id="restoreModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('backup.restore') }}" method="POST" id="restoreForm">
                @csrf
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="fas fa-upload me-2"></i> Restaurar Base de Datos
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Step 1: Warning -->
                    <div class="alert alert-danger">
                        <h6 class="alert-heading">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ¡ADVERTENCIA IMPORTANTE!
                        </h6>
                        <hr>
                        <ul class="mb-0">
                            <li>Esta operación <strong>SOBRESCRIBIRÁ TODOS LOS DATOS ACTUALES</strong></li>
                            <li>La operación <strong>NO SE PUEDE DESHACER</strong></li>
                            <li>Se recomienda realizar un backup antes de restaurar</li>
                            <li>Todos los usuarios deberán volver a iniciar sesión</li>
                        </ul>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Seleccione el backup a restaurar</label>
                        <select name="backup_file" class="form-select" required>
                            <option value="">Seleccione un backup...</option>
                            @foreach($backups as $backup)
                                <option value="{{ $backup['filename'] }}">
                                    {{ $backup['filename'] }} ({{ number_format($backup['size'] / 1048576, 2) }} MB) - 
                                    {{ $backup['date']->format('d/m/Y H:i:s') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirmUnderstand" required>
                        <label class="form-check-label" for="confirmUnderstand">
                            Entiendo que esta acción es irreversible y sobrescribirá todos los datos actuales
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning" id="submitRestore">
                        <i class="fas fa-upload me-2"></i>Restaurar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Form -->
<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Referencias a los elementos del modal
    const modal = new bootstrap.Modal(document.getElementById('restoreModal'));
    let currentStep = 1;
    const totalSteps = 3;
    
    // Función para actualizar la barra de progreso
    function updateProgress() {
        const progress = ((currentStep - 1) / (totalSteps - 1)) * 100;
        $('.progress-bar').css('width', progress + '%');
    }

    // Función para mostrar el paso actual
    function showStep(step) {
        $('.step').hide();
        $(`#step${step}`).show();
        
        $('#prevStep').toggle(step > 1);
        $('#nextStep').toggle(step < totalSteps);
        $('#submitRestore').toggle(step === totalSteps);
        
        updateProgress();
    }

    // Control de los pasos
    $('#nextStep').click(function() {
        if (currentStep === 1) {
            if (!$('#confirmBackup').is(':checked') || !$('#confirmUnderstand').is(':checked')) {
                Toast.fire({
                    icon: 'error',
                    title: 'Debe confirmar ambas casillas para continuar'
                });
                return;
            }
        }
        
        if (currentStep === 2) {
            if (!$('input[name="backup_file"]')[0].files.length) {
                Toast.fire({
                    icon: 'error',
                    title: 'Debe seleccionar un archivo'
                });
                return;
            }
        }
        
        currentStep++;
        showStep(currentStep);
    });

    $('#prevStep').click(function() {
        currentStep--;
        showStep(currentStep);
    });

    // Actualizar nombre del archivo seleccionado
    $('input[name="backup_file"]').change(function() {
        const fileName = this.files[0]?.name || 'Ninguno';
        $('#selectedFileName').text(fileName);
    });

    // Manejo del formulario de restauración
    $('#restoreForm').submit(function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: '¿Está seguro?',
            text: "Esta acción no se puede deshacer",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, restaurar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Restaurando...',
                    text: 'Por favor espere mientras se restaura la base de datos',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                this.submit();
            }
        });
    });

    // Reset del modal cuando se cierra
    $('#restoreModal').on('hidden.bs.modal', function () {
        currentStep = 1;
        showStep(1);
        $('#restoreForm').trigger('reset');
        $('#selectedFileName').text('Ninguno');
    });

    // Función para confirmar eliminación de backup
    window.confirmarEliminar = function(filename) {
        Swal.fire({
            title: '¿Eliminar backup?',
            text: `¿Está seguro de eliminar ${filename}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('delete-form');
                form.action = `{{ route('backup.delete', '') }}/${filename}`;
                form.submit();
            }
        });
    }
});
</script>
@endpush