@extends('layouts.app')

@section('title', 'Configuración de Empresa')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Datos de la Empresa</h5>
                @if(!$empresa)
                    <a href="{{ route('empresa.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Registrar Empresa
                    </a>
                @endif
            </div>
        </div>

        <div class="card-body">
            @if($empresa)
                <div class="row">
                    <!-- Logo actual -->
                    @if($empresa->logo)
                    <div class="col-md-12 text-center mb-4">
                    <img src="{{ asset('images/logo.png') }}" 
                             alt="Logo de la empresa" 
                             class="img-fluid"
                             style="max-height: 150px;">
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
                        </dl>
                    </div>

                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">Dirección:</dt>
                            <dd class="col-sm-8">{{ $empresa->direccion }}</dd>

                            <dt class="col-sm-4">Teléfono:</dt>
                            <dd class="col-sm-8">{{ $empresa->telefono }}</dd>

                            <dt class="col-sm-4">Email:</dt>
                            <dd class="col-sm-8">{{ $empresa->email ?: 'No registrado' }}</dd>
                        </dl>
                    </div>

                    <div class="col-md-12">
                        <dl class="row">
                            <dt class="col-sm-2">Sitio Web:</dt>
                            <dd class="col-sm-10">{{ $empresa->sitio_web ?: 'No registrado' }}</dd>

                            <dt class="col-sm-2">Régimen Tributario:</dt>
                            <dd class="col-sm-10">
                                {{ $empresa->regimen_tributario == 'comun' ? 'Régimen Común' : 'Régimen Simplificado' }}
                            </dd>
                        </dl>
                    </div>

                    <div class="col-12 mt-4">
                        <a href="{{ route('empresa.edit', $empresa) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Editar Información
                        </a>
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-building fa-3x text-muted mb-3"></i>
                    <p class="h5 text-muted">No hay información de empresa registrada</p>
                    <a href="{{ route('empresa.create') }}" class="btn btn-primary mt-3">
                        <i class="fas fa-plus"></i> Registrar Empresa
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection