<!-- resources/views/configuracion/empresa/show.blade.php -->
@extends('layouts.app')

@section('title', 'Detalle de Empresa')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Detalle de la Empresa</h5>
                <div>
                    <a href="{{ route('empresa.edit', $empresa) }}" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                    <a href="{{ route('empresa.index') }}" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="row">
                @if($empresa->logo)
                <div class="col-md-12 text-center mb-4">
                    <img src="{{ Storage::url($empresa->logo) }}" 
                         alt="Logo" 
                         class="img-fluid"
                         style="max-width: 200px; max-height: 200px;">
                </div>
                @endif

                <div class="col-md-6">
                    <dl class="row">
                        <dt class="col-sm-4">Nombre Comercial:</dt>
                        <dd class="col-sm-8">{{ $empresa->nombre_comercial }}</dd>

                        <dt class="col-sm-4">Razón Social:</dt>
                        <dd class="col-sm-8">{{ $empresa->razon_social }}</dd>

                        <dt class="col-sm-4">NIT:</dt>
                        <dd class="col-sm-8">{{ $empresa->nit }}</dd>

                        <dt class="col-sm-4">Teléfono:</dt>
                        <dd class="col-sm-8">{{ $empresa->telefono }}</dd>
                    </dl>
                </div>

                <div class="col-md-6">
                    <dl class="row">
                        <dt class="col-sm-4">Email:</dt>
                        <dd class="col-sm-8">{{ $empresa->email ?? 'No registrado' }}</dd>

                        <dt class="col-sm-4">Sitio Web:</dt>
                        <dd class="col-sm-8">{{ $empresa->sitio_web ?? 'No registrado' }}</dd>

                        <dt class="col-sm-4">Dirección:</dt>
                        <dd class="col-sm-8">{{ $empresa->direccion }}</dd>

                        <dt class="col-sm-4">Régimen Tributario:</dt>
                        <dd class="col-sm-8">
                            {{ $empresa->regimen_tributario == 'comun' ? 'Régimen Común' : 'Régimen Simplificado' }}
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection