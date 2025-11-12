@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-plug"></i> 
                        Resultado de Conexión - {{ ucfirst($proveedor) }}
                    </h5>
                    <a href="{{ route('facturacion.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
                
                <div class="card-body">
                    @if($success)
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle"></i> ¡Conexión Exitosa!</h5>
                            <p class="mb-0">{{ $resultado['message'] ?? 'Conexión establecida correctamente' }}</p>
                        </div>
                        
                        @if(isset($resultado['data']) && is_array($resultado['data']))
                            <div class="mt-4">
                                <h6><i class="fas fa-info-circle"></i> Información de la Empresa:</h6>
                                <div class="bg-light p-3 rounded">
                                    <div class="row">
                                        @foreach($resultado['data'] as $key => $value)
                                            @if($key !== 'status' && !is_array($value))
                                                <div class="col-md-6 mb-2">
                                                    <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                                    <span class="text-muted">{{ $value }}</span>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        @if(isset($resultado['data']) && is_array($resultado['data']))
                            <div class="mt-4">
                                <h6><i class="fas fa-code"></i> Datos Completos (JSON):</h6>
                                <div class="bg-dark text-light p-3 rounded">
                                    <pre><code>{{ json_encode($resultado['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-triangle"></i> Error de Conexión</h5>
                            <p class="mb-0">{{ $resultado['message'] ?? 'Error desconocido al conectar con el proveedor' }}</p>
                        </div>
                        
                        <div class="mt-4">
                            <h6><i class="fas fa-lightbulb"></i> Posibles Soluciones:</h6>
                            <ul>
                                <li>Verificar que las credenciales estén configuradas correctamente</li>
                                <li>Comprobar la conexión a internet</li>
                                <li>Revisar que el proveedor esté disponible</li>
                                <li>Contactar al administrador del sistema</li>
                            </ul>
                        </div>
                    @endif
                </div>
                
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('facturacion.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al Panel
                        </a>
                        
                        @if(!$success)
                            <a href="{{ route('facturacion.configurar', $proveedor) }}" class="btn btn-primary">
                                <i class="fas fa-cog"></i> Configurar {{ ucfirst($proveedor) }}
                            </a>
                        @else
                            <button type="button" class="btn btn-success" onclick="window.location.reload()">
                                <i class="fas fa-redo"></i> Probar Nuevamente
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
