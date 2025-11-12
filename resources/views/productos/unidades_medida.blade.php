@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3>Gestión de Unidades de Medida</h3>
                        <a href="{{ route('productos.index') }}" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <p class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Las unidades de medida son necesarias para la integración con Alegra. 
                        Selecciona la unidad de medida apropiada para cada producto.
                    </p>

                    <form action="{{ route('productos.actualizar_unidades') }}" method="POST">
                        @csrf
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Nombre</th>
                                        <th>Unidad de Medida</th>
                                        <th>ID Alegra</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($productos as $producto)
                                    <tr>
                                        <td>{{ $producto->codigo }}</td>
                                        <td>{{ $producto->nombre }}</td>
                                        <td>
                                            <select name="unidades[{{ $producto->id }}]" class="form-control">
                                                <optgroup label="Cantidad">
                                                    <option value="unit" {{ $producto->unidad_medida == 'unit' ? 'selected' : '' }}>Unidad</option>
                                                    <option value="dozen" {{ $producto->unidad_medida == 'dozen' ? 'selected' : '' }}>Docena</option>
                                                    <option value="box" {{ $producto->unidad_medida == 'box' ? 'selected' : '' }}>Caja</option>
                                                    <option value="pack" {{ $producto->unidad_medida == 'pack' ? 'selected' : '' }}>Paquete</option>
                                                    <option value="bulto" {{ $producto->unidad_medida == 'bulto' ? 'selected' : '' }}>Bulto</option>
                                                </optgroup>
                                                <optgroup label="Peso">
                                                    <option value="kg" {{ $producto->unidad_medida == 'kg' ? 'selected' : '' }}>Kilogramo</option>
                                                    <option value="g" {{ $producto->unidad_medida == 'g' ? 'selected' : '' }}>Gramo</option>
                                                    <option value="lb" {{ $producto->unidad_medida == 'lb' ? 'selected' : '' }}>Libra</option>
                                                    <option value="oz" {{ $producto->unidad_medida == 'oz' ? 'selected' : '' }}>Onza</option>
                                                </optgroup>
                                                <optgroup label="Volumen">
                                                    <option value="l" {{ $producto->unidad_medida == 'l' ? 'selected' : '' }}>Litro</option>
                                                    <option value="ml" {{ $producto->unidad_medida == 'ml' ? 'selected' : '' }}>Mililitro</option>
                                                    <option value="cc" {{ $producto->unidad_medida == 'cc' ? 'selected' : '' }}>Centímetro Cúbico (cc)</option>
                                                    <option value="gal" {{ $producto->unidad_medida == 'gal' ? 'selected' : '' }}>Galón</option>
                                                </optgroup>
                                                <optgroup label="Longitud">
                                                    <option value="m" {{ $producto->unidad_medida == 'm' ? 'selected' : '' }}>Metro</option>
                                                    <option value="cm" {{ $producto->unidad_medida == 'cm' ? 'selected' : '' }}>Centímetro</option>
                                                    <option value="mm" {{ $producto->unidad_medida == 'mm' ? 'selected' : '' }}>Milímetro</option>
                                                </optgroup>
                                                <optgroup label="Servicios">
                                                    <option value="service" {{ $producto->unidad_medida == 'service' ? 'selected' : '' }}>Servicio</option>
                                                </optgroup>
                                            </select>
                                        </td>
                                        <td>
                                            {{ $producto->id_alegra ?: 'No sincronizado' }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
