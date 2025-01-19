@extends('layouts.app')

@section('title', 'Editar Proveedor')

@section('content')
<div class="container-fluid">
   <div class="card">
       <div class="card-header">
           <div class="d-flex justify-content-between align-items-center">
               <h5 class="mb-0">Editar Proveedor</h5>
               <a href="{{ route('proveedores.index') }}" class="btn btn-primary">
                   <i class="fas fa-arrow-left"></i> Volver
               </a>
           </div>
       </div>

       <div class="card-body">
           <form action="{{ route('proveedores.update', $proveedor) }}" method="POST">
               @csrf
               @method('PUT')

               <div class="row">
                   <div class="col-md-4">
                       <div class="mb-3">
                           <label class="form-label required">NIT</label>
                           <input type="text" 
                                  class="form-control @error('nit') is-invalid @enderror" 
                                  name="nit" 
                                  value="{{ old('nit', $proveedor->nit) }}" 
                                  required>
                           @error('nit')
                               <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                       </div>
                   </div>

                   <div class="col-md-8">
                       <div class="mb-3">
                           <label class="form-label required">Razón Social</label>
                           <input type="text" 
                                  class="form-control @error('razon_social') is-invalid @enderror" 
                                  name="razon_social" 
                                  value="{{ old('razon_social', $proveedor->razon_social) }}" 
                                  required>
                           @error('razon_social')
                               <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                       </div>
                   </div>

                   <div class="col-md-4">
                       <div class="mb-3">
                           <label class="form-label">Régimen</label>
                           <select class="form-select @error('regimen') is-invalid @enderror" 
                                   name="regimen">
                               <option value="">Seleccione...</option>
                               <option value="COMUN" {{ old('regimen', $proveedor->regimen) == 'COMUN' ? 'selected' : '' }}>Común</option>
                               <option value="SIMPLIFICADO" {{ old('regimen', $proveedor->regimen) == 'SIMPLIFICADO' ? 'selected' : '' }}>Simplificado</option>
                           </select>
                           @error('regimen')
                               <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                       </div>
                   </div>

                   <div class="col-md-4">
                       <div class="mb-3">
                           <label class="form-label">Tipo Identificación</label>
                           <select class="form-select @error('tipo_identificacion') is-invalid @enderror" 
                                   name="tipo_identificacion">
                               <option value="">Seleccione...</option>
                               <option value="NIT" {{ old('tipo_identificacion', $proveedor->tipo_identificacion) == 'NIT' ? 'selected' : '' }}>NIT</option>
                               <option value="CC" {{ old('tipo_identificacion', $proveedor->tipo_identificacion) == 'CC' ? 'selected' : '' }}>Cédula</option>
                               <option value="CE" {{ old('tipo_identificacion', $proveedor->tipo_identificacion) == 'CE' ? 'selected' : '' }}>Cédula Extranjería</option>
                           </select>
                           @error('tipo_identificacion')
                               <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                       </div>
                   </div>

                   <div class="col-md-4">
                       <div class="mb-3">
                           <label class="form-label">Ciudad</label>
                           <input type="text" 
                                  class="form-control @error('ciudad') is-invalid @enderror" 
                                  name="ciudad" 
                                  value="{{ old('ciudad', $proveedor->ciudad) }}">
                           @error('ciudad')
                               <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                       </div>
                   </div>

                   <div class="col-md-12">
                       <div class="mb-3">
                           <label class="form-label required">Dirección</label>
                           <input type="text" 
                                  class="form-control @error('direccion') is-invalid @enderror" 
                                  name="direccion" 
                                  value="{{ old('direccion', $proveedor->direccion) }}" 
                                  required>
                           @error('direccion')
                               <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                       </div>
                   </div>

                   <div class="col-md-4">
                       <div class="mb-3">
                           <label class="form-label required">Teléfono</label>
                           <input type="text" 
                                  class="form-control @error('telefono') is-invalid @enderror" 
                                  name="telefono" 
                                  value="{{ old('telefono', $proveedor->telefono) }}" 
                                  required>
                           @error('telefono')
                               <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                       </div>
                   </div>

                   <div class="col-md-4">
                       <div class="mb-3">
                           <label class="form-label">Celular</label>
                           <input type="text" 
                                  class="form-control @error('celular') is-invalid @enderror" 
                                  name="celular" 
                                  value="{{ old('celular', $proveedor->celular) }}">
                           @error('celular')
                               <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                       </div>
                   </div>

                   <div class="col-md-4">
                       <div class="mb-3">
                           <label class="form-label">Fax</label>
                           <input type="text" 
                                  class="form-control @error('fax') is-invalid @enderror" 
                                  name="fax" 
                                  value="{{ old('fax', $proveedor->fax) }}">
                           @error('fax')
                               <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                       </div>
                   </div>

                   <div class="col-md-6">
                       <div class="mb-3">
                           <label class="form-label">Correo Electrónico</label>
                           <input type="email" 
                                  class="form-control @error('correo_electronico') is-invalid @enderror" 
                                  name="correo_electronico" 
                                  value="{{ old('correo_electronico', $proveedor->correo_electronico) }}">
                           @error('correo_electronico')
                               <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                       </div>
                   </div>

                   <div class="col-md-6">
                       <div class="mb-3">
                           <label class="form-label">Contacto</label>
                           <input type="text" 
                                  class="form-control @error('contacto') is-invalid @enderror" 
                                  name="contacto" 
                                  value="{{ old('contacto', $proveedor->contacto) }}">
                           @error('contacto')
                               <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                       </div>
                   </div>

                   <div class="col-md-4">
                       <div class="mb-3">
                           <label class="form-label required">Estado</label>
                           <select class="form-select @error('estado') is-invalid @enderror" 
                                   name="estado" 
                                   required>
                               <option value="1" {{ old('estado', $proveedor->estado) ? 'selected' : '' }}>Activo</option>
                               <option value="0" {{ !old('estado', $proveedor->estado) ? 'selected' : '' }}>Inactivo</option>
                           </select>
                           @error('estado')
                               <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                       </div>
                   </div>
               </div>

               <div class="mt-4">
                   <button type="submit" class="btn btn-primary">
                       <i class="fas fa-save"></i> Actualizar
                   </button>
                   <a href="{{ route('proveedores.index') }}" class="btn btn-secondary">
                       <i class="fas fa-times"></i> Cancelar
                   </a>
               </div>
           </form>
       </div>
   </div>
</div>
@endsection