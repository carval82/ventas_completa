@extends('layouts.app')

@section('title', 'Editar Cliente')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Editar Cliente</h5>
                <a href="{{ route('clientes.index') }}" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <div class="card-body">
            <form action="{{ route('clientes.update', $cliente) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Nombres</label>
                            <input type="text" class="form-control @error('nombres') is-invalid @enderror" 
                                   name="nombres" value="{{ old('nombres', $cliente->nombres) }}" required>
                            @error('nombres')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Apellidos</label>
                            <input type="text" class="form-control @error('apellidos') is-invalid @enderror" 
                                   name="apellidos" value="{{ old('apellidos', $cliente->apellidos) }}" required>
                            @error('apellidos')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Cédula</label>
                            <input type="text" class="form-control @error('cedula') is-invalid @enderror" 
                                   name="cedula" value="{{ old('cedula', $cliente->cedula) }}" required>
                            @error('cedula')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control @error('telefono') is-invalid @enderror" 
                                   name="telefono" value="{{ old('telefono', $cliente->telefono) }}">
                            @error('telefono')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   name="email" value="{{ old('email', $cliente->email) }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label">Dirección</label>
                            <textarea class="form-control @error('direccion') is-invalid @enderror" 
                                      name="direccion" rows="2">{{ old('direccion', $cliente->direccion) }}</textarea>
                            @error('direccion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select @error('estado') is-invalid @enderror" name="estado">
                                <option value="1" {{ old('estado', $cliente->estado) ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ !old('estado', $cliente->estado) ? 'selected' : '' }}>Inactivo</option>
                            </select>
                            @error('estado')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Ciudad</label>
                            <input type="text" class="form-control @error('ciudad') is-invalid @enderror" 
                                   name="ciudad" value="{{ old('ciudad', $cliente->ciudad ?? 'Bogotá D.C.') }}">
                            @error('ciudad')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Departamento</label>
                            <input type="text" class="form-control @error('departamento') is-invalid @enderror" 
                                   name="departamento" value="{{ old('departamento', $cliente->departamento ?? 'Bogotá') }}">
                            @error('departamento')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Tipo de Documento</label>
                            <select class="form-select @error('tipo_documento') is-invalid @enderror" name="tipo_documento">
                                <option value="CC" {{ old('tipo_documento', $cliente->tipo_documento) == 'CC' ? 'selected' : '' }}>Cédula de Ciudadanía</option>
                                <option value="NIT" {{ old('tipo_documento', $cliente->tipo_documento) == 'NIT' ? 'selected' : '' }}>NIT</option>
                                <option value="CE" {{ old('tipo_documento', $cliente->tipo_documento) == 'CE' ? 'selected' : '' }}>Cédula de Extranjería</option>
                                <option value="TI" {{ old('tipo_documento', $cliente->tipo_documento) == 'TI' ? 'selected' : '' }}>Tarjeta de Identidad</option>
                                <option value="PP" {{ old('tipo_documento', $cliente->tipo_documento) == 'PP' ? 'selected' : '' }}>Pasaporte</option>
                            </select>
                            @error('tipo_documento')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Tipo de Persona</label>
                            <select class="form-select @error('tipo_persona') is-invalid @enderror" name="tipo_persona">
                                <option value="PERSON_ENTITY" {{ old('tipo_persona', $cliente->tipo_persona) == 'PERSON_ENTITY' ? 'selected' : '' }}>Persona Natural</option>
                                <option value="LEGAL_ENTITY" {{ old('tipo_persona', $cliente->tipo_persona) == 'LEGAL_ENTITY' ? 'selected' : '' }}>Persona Jurídica</option>
                            </select>
                            @error('tipo_persona')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Régimen</label>
                            <select class="form-select @error('regimen') is-invalid @enderror" name="regimen">
                                <option value="SIMPLIFIED_REGIME" {{ old('regimen', $cliente->regimen) == 'SIMPLIFIED_REGIME' ? 'selected' : '' }}>Régimen Simplificado</option>
                                <option value="COMMON_REGIME" {{ old('regimen', $cliente->regimen) == 'COMMON_REGIME' ? 'selected' : '' }}>Régimen Común</option>
                                <option value="SPECIAL_REGIME" {{ old('regimen', $cliente->regimen) == 'SPECIAL_REGIME' ? 'selected' : '' }}>Régimen Especial</option>
                                <option value="NATIONAL_CONSUMPTION_TAX" {{ old('regimen', $cliente->regimen) == 'NATIONAL_CONSUMPTION_TAX' ? 'selected' : '' }}>Impuesto Nacional al Consumo</option>
                            </select>
                            @error('regimen')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection