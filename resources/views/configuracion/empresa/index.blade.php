@extends('layouts.app')

@php
use Illuminate\Support\Str;
@endphp

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
                    @if($empresa->logo)
                        <div class="col-md-12 text-center mb-4">
                            <img src="{{ Storage::url($empresa->logo) }}" 
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

                            <dt class="col-sm-4">Régimen Tributario:</dt>
                            <dd class="col-sm-8">
                                @switch($empresa->regimen_tributario)
                                    @case('responsable_iva')
                                        Responsable de IVA
                                        @break
                                    @case('no_responsable_iva')
                                        No Responsable de IVA
                                        @break
                                    @case('regimen_simple')
                                        Régimen Simple de Tributación
                                        @break
                                @endswitch
                            </dd>
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

                            <dt class="col-sm-4">Sitio Web:</dt>
                            <dd class="col-sm-8">{{ $empresa->sitio_web ?: 'No registrado' }}</dd>
                        </dl>
                    </div>

                    <!-- Nueva sección para Facturación Electrónica -->
                    <div class="col-12 mt-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Información de Facturación Electrónica</h6>
                            </div>
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-3">Resolución:</dt>
                                    <dd class="col-sm-9">
                                        @if(is_string($empresa->resolucion_facturacion) && Str::startsWith($empresa->resolucion_facturacion, '{'))
                                            @php
                                                $resolucion = json_decode($empresa->resolucion_facturacion, true);
                                                echo $resolucion['texto'] ?? 'No registrada';
                                            @endphp
                                        @else
                                            {{ $empresa->resolucion_facturacion ?: 'No registrada' }}
                                        @endif
                                    </dd>

                                    <dt class="col-sm-3">Prefijo Factura:</dt>
                                    <dd class="col-sm-9">{{ $empresa->prefijo_factura ?: 'No registrado' }}</dd>

                                    <dt class="col-sm-3">ID Resolución Alegra:</dt>
                                    <dd class="col-sm-9">{{ $empresa->id_resolucion_alegra ?: 'No registrado' }}</dd>

                                    <dt class="col-sm-3">Fecha Resolución:</dt>
                                    <dd class="col-sm-9">
                                        {{ $empresa->fecha_resolucion ? $empresa->fecha_resolucion->format('d/m/Y') : 'No registrada' }}
                                    </dd>

                                    <dt class="col-sm-3">Fecha Vencimiento:</dt>
                                    <dd class="col-sm-9">
                                        {{ $empresa->fecha_vencimiento_resolucion ? $empresa->fecha_vencimiento_resolucion->format('d/m/Y') : 'No registrada' }}
                                    </dd>

                                    <dt class="col-sm-3">Estado:</dt>
                                    <dd class="col-sm-9">
                                        @if($empresa->factura_electronica_habilitada)
                                            <span class="badge bg-success">Habilitada</span>
                                        @else
                                            <span class="badge bg-warning">No Habilitada</span>
                                        @endif
                                    </dd>
                                    <dt class="col-sm-3">QR/CUFE Local:</dt>
                                    <dd class="col-sm-9">
                                        @if($empresa->generar_qr_local)
                                            <span class="badge bg-success">Activado</span>
                                        @else
                                            <span class="badge bg-secondary">Desactivado</span>
                                        @endif
                                    </dd>
                                </dl>
                            </div>
                        </div>
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