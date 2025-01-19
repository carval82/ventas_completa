@extends('layouts.app')

@section('title', 'Crear Nuevo Backup')

@section('styles')
<style>
    .backup-option {
        border-left: 3px solid #007bff;
        transition: all 0.3s ease;
    }
    .backup-option:hover {
        background-color: #f8f9fa;
        transform: translateX(5px);
    }
    .option-card {
        transition: all 0.3s ease;
    }
    .option-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('backup.index') }}">
                            <i class="fas fa-database"></i> Backups
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Crear Nuevo</li>
                </ol>
            </nav>

            <form action="{{ route('backup.index') }}" method="POST" id="backupForm" enctype="multipart/form-data">
                @csrf
                
                <!-- Información Principal -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-plus"></i> Nuevo Backup
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Nombre del Backup</label>
                                    <input type="text" 
                                           name="backup_name" 
                                           class="form-control" 
                                           value="backup_{{ date('Y-m-d_H-i-s') }}"
                                           required>
                                    <small class="text-muted">
                                        Nombre para identificar este backup
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Descripción</label>
                                    <input type="text" 
                                           name="description" 
                                           class="form-control"
                                           placeholder="Ej: Backup mensual de datos"
                                           required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Opciones de Backup -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-cog"></i> Opciones de Backup
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Tipo de Backup -->
                            <div class="col-md-6 mb-4">
                                <div class="card option-card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-archive"></i> Tipo de Backup
                                        </h6>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" 
                                                   type="radio" 
                                                   name="backup_type" 
                                                   value="full" 
                                                   checked>
                                            <label class="form-check-label">
                                                Backup Completo
                                                <small class="d-block text-muted">
                                                    Incluye toda la base de datos
                                                </small>
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="radio" 
                                                   name="backup_type" 
                                                   value="partial">
                                            <label class="form-check-label">
                                                Backup Parcial
                                                <small class="d-block text-muted">
                                                    Seleccione tablas específicas
                                                </small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Opciones de Compresión -->
                            <div class="col-md-6 mb-4">
                                <div class="card option-card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-compress"></i> Compresión
                                        </h6>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   name="compress" 
                                                   value="1" 
                                                   checked>
                                            <label class="form-check-label">
                                                Comprimir backup
                                                <small class="d-block text-muted">
                                                    Reduce el tamaño del archivo
                                                </small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Selección de Tablas (para backup parcial) -->
                            <div class="col-12" id="tablesSection" style="display: none;">
                                <div class="card option-card">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-table"></i> Selección de Tablas
                                        </h6>
                                        <div class="row">
                                            @foreach($tables as $table)
                                                <div class="col-md-4">
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" 
                                                               type="checkbox" 
                                                               name="tables[]" 
                                                               value="{{ $table }}">
                                                        <label class="form-check-label">
                                                            {{ $table }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Opciones Adicionales -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-sliders-h"></i> Opciones Adicionales
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3 backup-option p-3">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="include_drop" 
                                   value="1" 
                                   checked>
                            <label class="form-check-label">
                                <strong>Incluir DROP TABLE</strong>
                                <small class="d-block text-muted">
                                    Incluye sentencias para eliminar tablas existentes antes de restaurar
                                </small>
                            </label>
                        </div>
                        
                        <div class="form-check mb-3 backup-option p-3">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="add_locks" 
                                   value="1" 
                                   checked>
                            <label class="form-check-label">
                                <strong>Agregar LOCKS</strong>
                                <small class="d-block text-muted">
                                    Mejora la consistencia durante la restauración
                                </small>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="card">
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            El proceso de backup puede tomar varios minutos dependiendo del tamaño de la base de datos.
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Crear Backup
                        </button>
                        
                        <a href="{{ route('backup.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Mostrar/ocultar sección de tablas según tipo de backup
    $('input[name="backup_type"]').change(function() {
        if ($(this).val() === 'partial') {
            $('#tablesSection').slideDown();
        } else {
            $('#tablesSection').slideUp();
        }
    });

    // Validación del formulario
    $('#backupForm').submit(function(e) {
        const backupType = $('input[name="backup_type"]:checked').val();
        
        if (backupType === 'partial' && !$('input[name="tables[]"]:checked').length) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe seleccionar al menos una tabla para el backup parcial'
            });
        } else {
            Swal.fire({
                title: 'Creando Backup',
                html: 'Por favor espere mientras se crea el backup...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }
    });
});
</script>
@endpush