@extends('layouts.app')

@section('title', 'Importar Terceros')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-file-import"></i> Importar Terceros
                        </h5>
                        <div>
                            <a href="{{ asset('templates/plantilla_clientes.xlsx') }}" class="btn btn-light btn-sm">
                                <i class="fas fa-download"></i> Plantilla Clientes
                            </a>
                            <a href="{{ asset('templates/plantilla_proveedores.xlsx') }}" class="btn btn-light btn-sm ms-2">
                                <i class="fas fa-download"></i> Plantilla Proveedores
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>

                        @if(session('errores'))
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>Advertencias:</h6>
                                <ul class="mb-0">
                                    @foreach(session('errores') as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-times-circle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('import.terceros') }}" method="POST" enctype="multipart/form-data" id="importForm">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label required">Tipo de Terceros</label>
                            <select name="tipo" class="form-select" required>
                                <option value="">Seleccione tipo...</option>
                                <option value="clientes">Clientes</option>
                                <option value="proveedores">Proveedores</option>
                            </select>
                            @error('tipo')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">Archivo Excel</label>
                            <input type="file" 
                                   name="file" 
                                   class="form-control" 
                                   accept=".xlsx,.xls" 
                                   required>
                            @error('file')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <small class="form-text">
                                Formatos soportados: Excel (.xlsx, .xls)
                            </small>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Estructura del Archivo
                                </h6>
                            </div>
                            <div class="card-body">
                                <!-- Estructura para Clientes -->
                                <div id="estructuraClientes" style="display: none;">
                                    <h6 class="text-primary">Estructura para Clientes:</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Columna</th>
                                                    <th>Campo</th>
                                                    <th>Requerido</th>
                                                    <th>Descripción</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>A</td>
                                                    <td>Cédula/Identificación</td>
                                                    <td>Sí</td>
                                                    <td>Número de identificación único</td>
                                                </tr>
                                                <tr>
                                                    <td>B</td>
                                                    <td>Nombres</td>
                                                    <td>Sí</td>
                                                    <td>Nombres del cliente</td>
                                                </tr>
                                                <tr>
                                                    <td>C</td>
                                                    <td>Apellidos</td>
                                                    <td>No</td>
                                                    <td>Apellidos del cliente</td>
                                                </tr>
                                                <tr>
                                                    <td>D</td>
                                                    <td>Teléfono</td>
                                                    <td>No</td>
                                                    <td>Número de contacto</td>
                                                </tr>
                                                <tr>
                                                    <td>E</td>
                                                    <td>Email</td>
                                                    <td>No</td>
                                                    <td>Correo electrónico</td>
                                                </tr>
                                                <tr>
                                                    <td>F</td>
                                                    <td>Dirección</td>
                                                    <td>No</td>
                                                    <td>Dirección física</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Estructura para Proveedores -->
                                <div id="estructuraProveedores" style="display: none;">
                                    <h6 class="text-primary">Estructura para Proveedores:</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Columna</th>
                                                    <th>Campo</th>
                                                    <th>Requerido</th>
                                                    <th>Descripción</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>A</td>
                                                    <td>NIT</td>
                                                    <td>Sí</td>
                                                    <td>Número de identificación tributaria</td>
                                                </tr>
                                                <tr>
                                                    <td>B</td>
                                                    <td>Nombre</td>
                                                    <td>Sí</td>
                                                    <td>Nombre o razón social</td>
                                                </tr>
                                                <tr>
                                                    <td>C</td>
                                                    <td>Teléfono</td>
                                                    <td>No</td>
                                                    <td>Número de contacto</td>
                                                </tr>
                                                <tr>
                                                    <td>D</td>
                                                    <td>Email</td>
                                                    <td>No</td>
                                                    <td>Correo electrónico</td>
                                                </tr>
                                                <tr>
                                                    <td>E</td>
                                                    <td>Dirección</td>
                                                    <td>No</td>
                                                    <td>Dirección física</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <a href="{{ route('home') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary" id="btnSubmit">
                                <i class="fas fa-upload me-2"></i>Importar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Mostrar/ocultar estructura según tipo seleccionado
    $('select[name="tipo"]').change(function() {
        $('#estructuraClientes, #estructuraProveedores').hide();
        
        if (this.value === 'clientes') {
            $('#estructuraClientes').show();
        } else if (this.value === 'proveedores') {
            $('#estructuraProveedores').show();
        }
    });

    // Manejo del formulario
    $('#importForm').submit(function() {
        const btn = $('#btnSubmit');
        btn.prop('disabled', true);
        btn.html('<i class="fas fa-spinner fa-spin me-2"></i>Procesando...');

        // Mostrar alerta de procesamiento
        Swal.fire({
            title: 'Procesando importación',
            html: 'Por favor espere mientras se procesan los datos...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    });
});
</script>
@endpush