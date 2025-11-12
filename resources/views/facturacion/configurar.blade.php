@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-cog"></i>
                        Configurar {{ ucfirst($proveedor) }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('facturacion.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('facturacion.guardar-configuracion', $proveedor) }}">
                        @csrf
                        
                        @if($proveedor === 'alegra')
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="usuario">Usuario Alegra</label>
                                        <input type="text" class="form-control @error('usuario') is-invalid @enderror" 
                                               id="usuario" name="usuario" 
                                               value="{{ old('usuario', $configuracionActual['usuario'] ?? '') }}" required>
                                        @error('usuario')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="token">Token API</label>
                                        <input type="password" class="form-control @error('token') is-invalid @enderror" 
                                               id="token" name="token" 
                                               value="{{ old('token', $configuracionActual['token'] ?? '') }}" required>
                                        @error('token')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="url_base">URL Base</label>
                                        <input type="url" class="form-control @error('url_base') is-invalid @enderror" 
                                               id="url_base" name="url_base" 
                                               value="{{ old('url_base', $configuracionActual['url_base'] ?? 'https://api.alegra.com/api/v1') }}" required>
                                        @error('url_base')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($proveedor === 'dian')
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nit_empresa">NIT Empresa</label>
                                        <input type="text" class="form-control @error('nit_empresa') is-invalid @enderror" 
                                               id="nit_empresa" name="nit_empresa" 
                                               value="{{ old('nit_empresa', $configuracionActual['nit_empresa'] ?? '') }}" required>
                                        @error('nit_empresa')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="username">Usuario DIAN</label>
                                        <input type="text" class="form-control @error('username') is-invalid @enderror" 
                                               id="username" name="username" 
                                               value="{{ old('username', $configuracionActual['username'] ?? '') }}" required>
                                        @error('username')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password">Contraseña DIAN</label>
                                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                               id="password" name="password" 
                                               value="{{ old('password', $configuracionActual['password'] ?? '') }}" required>
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="test_mode" name="test_mode" value="1"
                                                   {{ old('test_mode', $configuracionActual['test_mode'] ?? true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="test_mode">
                                                Modo de Pruebas
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($proveedor === 'siigo')
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="username">Usuario Siigo</label>
                                        <input type="text" class="form-control @error('username') is-invalid @enderror" 
                                               id="username" name="username" 
                                               value="{{ old('username', $configuracionActual['username'] ?? '') }}" required>
                                        @error('username')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="access_key">Access Key</label>
                                        <input type="password" class="form-control @error('access_key') is-invalid @enderror" 
                                               id="access_key" name="access_key" 
                                               value="{{ old('access_key', $configuracionActual['access_key'] ?? '') }}" required>
                                        @error('access_key')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="base_url">URL Base</label>
                                        <input type="url" class="form-control @error('base_url') is-invalid @enderror" 
                                               id="base_url" name="base_url" 
                                               value="{{ old('base_url', $configuracionActual['base_url'] ?? 'https://api.siigo.com/v1') }}" required>
                                        @error('base_url')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($proveedor === 'worldoffice')
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="api_key">API Key</label>
                                        <input type="password" class="form-control @error('api_key') is-invalid @enderror" 
                                               id="api_key" name="api_key" 
                                               value="{{ old('api_key', $configuracionActual['api_key'] ?? '') }}" required>
                                        @error('api_key')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="company_id">Company ID</label>
                                        <input type="text" class="form-control @error('company_id') is-invalid @enderror" 
                                               id="company_id" name="company_id" 
                                               value="{{ old('company_id', $configuracionActual['company_id'] ?? '') }}" required>
                                        @error('company_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="base_url">URL Base</label>
                                        <input type="url" class="form-control @error('base_url') is-invalid @enderror" 
                                               id="base_url" name="base_url" 
                                               value="{{ old('base_url', $configuracionActual['base_url'] ?? 'https://api.worldoffice.com/v1') }}" required>
                                        @error('base_url')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar Configuración
                                </button>
                                <a href="{{ route('facturacion.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
