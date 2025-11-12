<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\Api\ConversionUnidadesController;

/*
|--------------------------------------------------------------------------
| Rutas Multi-Tenant
|--------------------------------------------------------------------------
|
| Estas rutas se ejecutan dentro del contexto de un tenant específico
| Todas tienen acceso a la base de datos del tenant correspondiente
|
*/

// Dashboard principal del tenant
Route::get('/dashboard', [DashboardController::class, 'index'])->name('tenant.dashboard');

// Gestión de productos
Route::prefix('productos')->name('productos.')->group(function () {
    Route::get('/', [ProductoController::class, 'index'])->name('index');
    Route::get('/create', [ProductoController::class, 'create'])->name('create');
    Route::post('/', [ProductoController::class, 'store'])->name('store');
    Route::get('/{producto}', [ProductoController::class, 'show'])->name('show');
    Route::get('/{producto}/edit', [ProductoController::class, 'edit'])->name('edit');
    Route::put('/{producto}', [ProductoController::class, 'update'])->name('update');
    Route::delete('/{producto}', [ProductoController::class, 'destroy'])->name('destroy');
    
    // Búsqueda de productos para ventas
    Route::get('/buscar/ajax', [ProductoController::class, 'buscarAjax'])->name('buscar.ajax');
});

// Gestión de ventas
Route::prefix('ventas')->name('ventas.')->group(function () {
    Route::get('/', [VentaController::class, 'index'])->name('index');
    Route::get('/create', [VentaController::class, 'create'])->name('create');
    Route::post('/', [VentaController::class, 'store'])->name('store');
    Route::get('/{venta}', [VentaController::class, 'show'])->name('show');
    Route::get('/{venta}/edit', [VentaController::class, 'edit'])->name('edit');
    Route::put('/{venta}', [VentaController::class, 'update'])->name('update');
    Route::delete('/{venta}', [VentaController::class, 'destroy'])->name('destroy');
    
    // Reportes de ventas
    Route::get('/reportes/diario', [VentaController::class, 'reporteDiario'])->name('reportes.diario');
    Route::get('/reportes/mensual', [VentaController::class, 'reporteMensual'])->name('reportes.mensual');
});

// Gestión de clientes
Route::prefix('clientes')->name('clientes.')->group(function () {
    Route::get('/', [ClienteController::class, 'index'])->name('index');
    Route::get('/create', [ClienteController::class, 'create'])->name('create');
    Route::post('/', [ClienteController::class, 'store'])->name('store');
    Route::get('/{cliente}', [ClienteController::class, 'show'])->name('show');
    Route::get('/{cliente}/edit', [ClienteController::class, 'edit'])->name('edit');
    Route::put('/{cliente}', [ClienteController::class, 'update'])->name('update');
    Route::delete('/{cliente}', [ClienteController::class, 'destroy'])->name('destroy');
    
    // Búsqueda de clientes para ventas
    Route::get('/buscar/ajax', [ClienteController::class, 'buscarAjax'])->name('buscar.ajax');
});

// Configuración de la empresa
Route::prefix('empresa')->name('empresa.')->group(function () {
    Route::get('/configuracion', [EmpresaController::class, 'configuracion'])->name('configuracion');
    Route::put('/configuracion', [EmpresaController::class, 'actualizarConfiguracion'])->name('actualizar');
    Route::get('/usuarios', [EmpresaController::class, 'usuarios'])->name('usuarios');
    Route::post('/usuarios', [EmpresaController::class, 'crearUsuario'])->name('usuarios.crear');
});

// API para conversiones de unidades (específica del tenant)
Route::prefix('api/conversiones')->name('api.conversiones.')->group(function () {
    Route::get('/unidades-disponibles', [ConversionUnidadesController::class, 'obtenerUnidadesDisponibles'])
        ->name('unidades-disponibles');
    Route::post('/convertir-unidad', [ConversionUnidadesController::class, 'convertirUnidad'])
        ->name('convertir-unidad');
    Route::post('/validar-stock', [ConversionUnidadesController::class, 'validarStock'])
        ->name('validar-stock');
});

// Rutas adicionales específicas del tenant
Route::prefix('inventario')->name('inventario.')->group(function () {
    Route::get('/', 'InventarioController@index')->name('index');
    Route::get('/ubicaciones', 'UbicacionController@index')->name('ubicaciones');
    Route::post('/ajustar-stock', 'InventarioController@ajustarStock')->name('ajustar-stock');
});

// Reportes y estadísticas
Route::prefix('reportes')->name('reportes.')->group(function () {
    Route::get('/ventas', 'ReporteController@ventas')->name('ventas');
    Route::get('/productos', 'ReporteController@productos')->name('productos');
    Route::get('/clientes', 'ReporteController@clientes')->name('clientes');
    Route::get('/inventario', 'ReporteController@inventario')->name('inventario');
});

// Configuraciones avanzadas
Route::prefix('configuracion')->name('configuracion.')->group(function () {
    Route::get('/equivalencias', 'ConfiguracionController@equivalencias')->name('equivalencias');
    Route::get('/impuestos', 'ConfiguracionController@impuestos')->name('impuestos');
    Route::get('/facturacion', 'ConfiguracionController@facturacion')->name('facturacion');
});
