@extends('layouts.app')

@section('title', 'Administración de Empresas')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-building"></i> Gestión de Empresas
                    </h3>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearEmpresa">
                        <i class="fas fa-plus"></i> Nueva Empresa
                    </button>
                </div>
                
                <div class="card-body">
                    <!-- Estadísticas generales -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 id="total-empresas">0</h4>
                                            <p class="mb-0">Total Empresas</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-building fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 id="empresas-activas">0</h4>
                                            <p class="mb-0">Activas</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-check-circle fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 id="empresas-premium">0</h4>
                                            <p class="mb-0">Premium</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-crown fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 id="ingresos-mes">$0</h4>
                                            <p class="mb-0">Ingresos Mes</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-dollar-sign fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tabla de empresas -->
                    <div class="table-responsive">
                        <table class="table table-striped" id="tabla-empresas">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Empresa</th>
                                    <th>NIT</th>
                                    <th>Email</th>
                                    <th>Plan</th>
                                    <th>Estado</th>
                                    <th>Creada</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Se llena dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear Empresa -->
<div class="modal fade" id="modalCrearEmpresa" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-building"></i> Nueva Empresa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="form-crear-empresa">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nombre de la Empresa *</label>
                                <input type="text" class="form-control" name="nombre" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">NIT *</label>
                                <input type="text" class="form-control" name="nit" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="text" class="form-control" name="telefono">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Dirección</label>
                        <textarea class="form-control" name="direccion" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Plan *</label>
                                <select class="form-select" name="plan" required>
                                    <option value="">Seleccionar plan...</option>
                                    <option value="basico">Básico (3 usuarios, 500 productos)</option>
                                    <option value="premium">Premium (10 usuarios, 2000 productos)</option>
                                    <option value="enterprise">Enterprise (50 usuarios, 10000 productos)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Fecha de Expiración</label>
                                <input type="date" class="form-control" name="fecha_expiracion">
                                <small class="text-muted">Dejar vacío para sin límite</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Crear Empresa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    cargarEmpresas();
    
    // Crear empresa
    $('#form-crear-empresa').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        
        $.ajax({
            url: '/admin/tenants/crear',
            method: 'POST',
            data: data,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Empresa Creada!',
                        html: `
                            <p><strong>Empresa:</strong> ${response.data.tenant.nombre}</p>
                            <p><strong>URL de Acceso:</strong><br>
                            <a href="${response.data.url_acceso}" target="_blank">${response.data.url_acceso}</a></p>
                            <p><strong>Credenciales Iniciales:</strong><br>
                            Email: ${response.data.credenciales_iniciales.email}<br>
                            Password: ${response.data.credenciales_iniciales.password}</p>
                        `,
                        confirmButtonText: 'Entendido'
                    });
                    
                    $('#modalCrearEmpresa').modal('hide');
                    $('#form-crear-empresa')[0].reset();
                    cargarEmpresas();
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                let errorMsg = 'Error creando empresa:\n';
                
                Object.keys(errors).forEach(key => {
                    errorMsg += `• ${errors[key][0]}\n`;
                });
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMsg
                });
            }
        });
    });
});

function cargarEmpresas() {
    $.get('/admin/tenants', function(response) {
        if (response.success) {
            actualizarEstadisticas(response.data.data);
            actualizarTabla(response.data.data);
        }
    });
}

function actualizarEstadisticas(empresas) {
    const total = empresas.length;
    const activas = empresas.filter(e => e.activo).length;
    const premium = empresas.filter(e => e.plan === 'premium' || e.plan === 'enterprise').length;
    
    $('#total-empresas').text(total);
    $('#empresas-activas').text(activas);
    $('#empresas-premium').text(premium);
}

function actualizarTabla(empresas) {
    const tbody = $('#tabla-empresas tbody');
    tbody.empty();
    
    empresas.forEach(empresa => {
        const fila = `
            <tr>
                <td>${empresa.id}</td>
                <td>
                    <strong>${empresa.nombre}</strong><br>
                    <small class="text-muted">${empresa.slug}</small>
                </td>
                <td>${empresa.nit}</td>
                <td>${empresa.email}</td>
                <td>
                    <span class="badge bg-${getPlanColor(empresa.plan)}">
                        ${empresa.plan.toUpperCase()}
                    </span>
                </td>
                <td>
                    <span class="badge bg-${empresa.activo ? 'success' : 'danger'}">
                        ${empresa.activo ? 'Activa' : 'Inactiva'}
                    </span>
                </td>
                <td>${new Date(empresa.created_at).toLocaleDateString()}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-info" onclick="verEstadisticas('${empresa.slug}')">
                            <i class="fas fa-chart-bar"></i>
                        </button>
                        <button class="btn btn-${empresa.activo ? 'warning' : 'success'}" 
                                onclick="toggleEstado('${empresa.slug}')">
                            <i class="fas fa-${empresa.activo ? 'pause' : 'play'}"></i>
                        </button>
                        <a href="/empresa/${empresa.slug}/dashboard" class="btn btn-primary" target="_blank">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(fila);
    });
}

function getPlanColor(plan) {
    const colors = {
        'basico': 'secondary',
        'premium': 'warning',
        'enterprise': 'success'
    };
    return colors[plan] || 'secondary';
}

function toggleEstado(slug) {
    $.post(`/admin/tenants/${slug}/toggle`, {
        _token: $('meta[name="csrf-token"]').attr('content')
    }, function(response) {
        if (response.success) {
            cargarEmpresas();
            Swal.fire({
                icon: 'success',
                title: 'Estado Actualizado',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
}

function verEstadisticas(slug) {
    $.get(`/admin/tenants/${slug}/estadisticas`, function(response) {
        if (response.success) {
            const data = response.data;
            Swal.fire({
                title: `Estadísticas: ${data.tenant.nombre}`,
                html: `
                    <div class="text-start">
                        <p><strong>Usuarios:</strong> ${data.estadisticas.usuarios || 0} / ${data.limites.usuarios}</p>
                        <p><strong>Productos:</strong> ${data.estadisticas.productos || 0} / ${data.limites.productos}</p>
                        <p><strong>Ventas este mes:</strong> ${data.estadisticas.ventas_mes || 0} / ${data.limites.ventas_mes}</p>
                        <p><strong>Total ventas:</strong> $${(data.estadisticas.ventas_total || 0).toLocaleString()}</p>
                    </div>
                `,
                confirmButtonText: 'Cerrar'
            });
        }
    });
}
</script>
@endpush
