<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\HomeController;

/*
|--------------------------------------------------------------------------
| Rutas Multi-Tenant
|--------------------------------------------------------------------------
|
| Sistema de rutas para múltiples empresas con bases de datos independientes
|
*/

// Página principal pública
Route::get('/', [HomeController::class, 'index'])->name('home');

// Rutas públicas para registro de empresas
Route::prefix('registro')->name('registro.')->group(function () {
    Route::get('/', [TenantController::class, 'mostrarFormularioRegistro'])->name('formulario');
    Route::post('/crear-empresa', [TenantController::class, 'crear'])->name('crear');
    Route::get('/confirmacion/{slug}', [TenantController::class, 'confirmacion'])->name('confirmacion');
});

// Rutas de administración del sistema (super admin)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:super-admin'])->group(function () {
    Route::get('/dashboard', [TenantController::class, 'dashboardAdmin'])->name('dashboard');
    Route::get('/tenants', [TenantController::class, 'listar'])->name('tenants.index');
    Route::get('/tenants/{slug}', [TenantController::class, 'mostrar'])->name('tenants.show');
    Route::get('/tenants/{slug}/estadisticas', [TenantController::class, 'estadisticas'])->name('tenants.estadisticas');
    Route::post('/tenants/{slug}/toggle', [TenantController::class, 'toggleEstado'])->name('tenants.toggle');
    Route::delete('/tenants/{slug}', [TenantController::class, 'eliminar'])->name('tenants.destroy');
});

// Rutas específicas de cada empresa (tenant)
Route::prefix('empresa/{empresa}')
    ->name('tenant.')
    ->middleware(['tenant', 'auth'])
    ->group(function () {
        // Incluir todas las rutas del tenant
        require_once __DIR__ . '/tenant.php';
    });

// Rutas de autenticación (aplicables a todos los tenants)
Route::middleware(['tenant'])->group(function () {
    require __DIR__.'/auth.php';
});

// Rutas legacy (mantener temporalmente para compatibilidad)
Route::middleware(['auth'])->group(function () {
    // Redireccionar rutas antiguas al nuevo sistema
    Route::get('/dashboard', function () {
        // Detectar tenant del usuario y redireccionar
        $user = auth()->user();
        $tenant = $user->tenant ?? session('tenant_slug', 'demo');
        return redirect()->route('tenant.dashboard', ['empresa' => $tenant]);
    });
    
    Route::get('/productos', function () {
        $tenant = session('tenant_slug', 'demo');
        return redirect()->route('tenant.productos.index', ['empresa' => $tenant]);
    });
    
    Route::get('/ventas', function () {
        $tenant = session('tenant_slug', 'demo');
        return redirect()->route('tenant.ventas.index', ['empresa' => $tenant]);
    });
});

// Ruta para seleccionar tenant (desarrollo/testing)
Route::get('/seleccionar-empresa/{slug}', function ($slug) {
    session(['tenant_slug' => $slug]);
    return redirect()->route('tenant.dashboard', ['empresa' => $slug]);
})->name('seleccionar-empresa');
