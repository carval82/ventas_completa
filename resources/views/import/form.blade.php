@extends('layouts.app')

@section('title', 'Importar Inventario')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Importar Inventario</h5>
                <a href="{{ route('productos.index') }}" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row">
                <div class="col-md-6">
                    <!-- Asegúrate de que la ruta sea correcta -->
                    <form action="{{ route('import.inventario') }}" 
                          method="POST" 
                          enctype="multipart/form-data" 
                          id="importForm">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="file" class="form-label">Archivo Excel (.xls, .xlsx)</label>
                            <input type="file" 
                                   class="form-control @error('file') is-invalid @enderror" 
                                   name="file" 
                                   id="file" 
                                   accept=".xls,.xlsx" 
                                   required>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-info">
                            <h6 class="alert-heading">Formato del archivo Excel:</h6>
                            <ul class="mb-0">
                                <li>Columna A: Código del producto (requerido)</li>
                                <li>Columna B: Nombre del producto (requerido)</li>
                                <li>Columna C: Descripción (opcional)</li>
                                <li>Columna D: Precio de compra</li>
                                <li>Columna E: Precio de venta</li>
                                <li>Columna F: Stock mínimo</li>
                                <li>Columna G: Stock actual</li>
                            </ul>
                        </div>

                        <button type="submit" class="btn btn-primary" id="btnImport">
                            <i class="fas fa-upload"></i> Importar Inventario
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('importForm').addEventListener('submit', function() {
    const btnImport = document.getElementById('btnImport');
    btnImport.disabled = true;
    btnImport.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
});
</script>
@endpush