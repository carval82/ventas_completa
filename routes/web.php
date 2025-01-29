<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\MovimientoInternoController;
use App\Http\Controllers\MovimientoMasivoController;
use App\Http\Controllers\UbicacionController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\RegularizacionController;
use App\Http\Controllers\ImportTercerosController;
use App\Http\Controllers\Contabilidad\PlanCuentaController;
use App\Http\Controllers\Contabilidad\ComprobanteController;
use App\Http\Controllers\Contabilidad\ReporteContableController;
use App\Http\Controllers\SugeridoCompraController;
use App\Http\Controllers\OrdenCompraController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\TurnoController;
use App\Http\Controllers\CreditoController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
// Rutas de Autenticación
Auth::routes();


// Ruta principal - FUERA del middleware auth
Route::get('/', function () {
    return redirect()->route('home');
});

Route::get('/home', [HomeController::class, 'index'])->name('home')->middleware('auth');

// Resto de rutas protegidas
Route::middleware(['auth'])->group(function () {

    // Configuración
    Route::prefix('configuracion')->group(function () {
        // Empresa
        Route::resource('empresa', EmpresaController::class);
        
        // Usuarios
        Route::resource('users', UserController::class)
            ->parameters(['users' => 'usuario'])
            ->names('users');
        Route::put('users/{user}/change-password', [UserController::class, 'changePassword'])
            ->name('users.change-password');
        Route::put('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
            ->name('users.toggle-status');
    });

    // Ventas
    Route::resource('ventas', VentaController::class);
    Route::get('ventas/{venta}/print', [VentaController::class, 'print'])
        ->name('ventas.print');

    // Compras
    Route::controller(CompraController::class)->group(function () {
        Route::get('/compras', 'index')->name('compras.index');
        Route::get('/compras/create', 'create')->name('compras.create');
        Route::post('/compras', 'store')->name('compras.store');
        Route::get('/compras/{compra}', 'show')->name('compras.show');
        Route::get('/compras/{compra}/print', 'print')->name('compras.print');
    });

    // Productos
    Route::resource('productos', ProductoController::class);
    Route::get('/productos/search', [ProductoController::class, 'search'])
        ->name('productos.search');
    Route::post('/api/productos', [ProductoController::class, 'store'])
        ->name('api.productos.store');
    Route::post('productos/{producto}/asignar-proveedor', [ProductoController::class, 'asignarProveedor'])
        ->name('productos.asignar-proveedor');
    Route::delete('productos/{producto}/remove-proveedor', [ProductoController::class, 'removeProveedor'])
        ->name('productos.remove-proveedor');

    // Clientes
    Route::resource('clientes', ClienteController::class);

    // Proveedores
    Route::resource('proveedores', ProveedorController::class);

    // Ubicaciones
    Route::resource('ubicaciones', UbicacionController::class);

    // Movimientos Internos
    Route::resource('movimientos', MovimientoInternoController::class);
    Route::get('movimientos-reporte-stock', [MovimientoInternoController::class, 'reporteStock'])
        ->name('movimientos.reporte-stock');
    Route::get('movimientos-stock-bajo', [MovimientoInternoController::class, 'stockBajo'])
        ->name('movimientos.stock-bajo');
    Route::get('get-stock-ubicacion', [MovimientoInternoController::class, 'getStockUbicacion'])
        ->name('movimientos.get-stock');

    // Movimientos Masivos
    Route::resource('movimientos-masivos', MovimientoMasivoController::class);
    Route::get('movimientos-masivos/{movimientos_masivo}/procesar', [MovimientoMasivoController::class, 'procesar'])
        ->name('movimientos-masivos.procesar');
    Route::put('movimientos-masivos/{movimientoMasivo}/anular', [MovimientoMasivoController::class, 'anular'])
        ->name('movimientos-masivos.anular');

    // Importación
    Route::get('/import', [ImportController::class, 'showImportForm'])->name('import.form');
    Route::post('/import', [ImportController::class, 'importInventario'])->name('import.inventario');
    Route::get('/import/terceros', [ImportTercerosController::class, 'showImportForm'])->name('import.terceros.form');
    Route::post('/import/terceros', [ImportTercerosController::class, 'importTerceros'])->name('import.terceros');

    // Regularización
    Route::prefix('regularizacion')->group(function () {
        Route::get('/', [RegularizacionController::class, 'index'])->name('regularizacion.index');
        Route::post('/ejecutar', [RegularizacionController::class, 'regularizar'])->name('regularizacion.ejecutar');
    });

    // Backup
    Route::prefix('backup')->group(function () {
        Route::get('/', [BackupController::class, 'index'])->name('backup.index');
        Route::post('/', [BackupController::class, 'store'])->name('backup.store');
        Route::get('/create', [BackupController::class, 'create'])->name('backup.create');
        Route::get('/download/{filename}', [BackupController::class, 'download'])->name('backup.download');
        Route::post('/restore', [BackupController::class, 'restore'])->name('backup.restore');
        Route::delete('/{filename}', [BackupController::class, 'delete'])->name('backup.delete');
    });

    // Rutas de Contabilidad
    Route::middleware(['auth'])->prefix('contabilidad')->group(function () {
        // Plan de Cuentas
        Route::resource('plan-cuentas', PlanCuentaController::class);
        
        // Comprobantes
        Route::resource('comprobantes', ComprobanteController::class);
        Route::post('comprobantes/{comprobante}/aprobar', [ComprobanteController::class, 'aprobar'])->name('comprobantes.aprobar');
        Route::post('comprobantes/{comprobante}/anular', [ComprobanteController::class, 'anular'])->name('comprobantes.anular');
        Route::get('comprobantes/{comprobante}/imprimir', [ComprobanteController::class, 'imprimir'])->name('comprobantes.imprimir');

        // Reportes
        Route::get('reportes', [ReporteContableController::class, 'index'])->name('reportes.index');
        Route::get('reportes/balance-general', [ReporteContableController::class, 'balance_general'])->name('reportes.balance-general');
        Route::get('reportes/estado-resultados', [ReporteContableController::class, 'estado_resultados'])->name('reportes.estado-resultados');
        Route::get('reportes/libro-diario', [ReporteContableController::class, 'libro_diario'])->name('reportes.libro-diario');
        Route::get('reportes/libro-mayor', [ReporteContableController::class, 'libro_mayor'])->name('reportes.libro-mayor');
    });

    Route::get('/productos/{id}/barcode', [ProductoController::class, 'imprimirCodigoBarras'])->name('productos.barcode');

    // Sugeridos y Órdenes de Compra
    Route::prefix('sugeridos')->group(function () {
        Route::get('/', [SugeridoCompraController::class, 'index'])->name('sugeridos.index');
        Route::get('/calcular', [SugeridoCompraController::class, 'calcularSugeridos'])->name('sugeridos.calcular');
        Route::post('/generar-orden', [SugeridoCompraController::class, 'generarOrden'])->name('sugeridos.generar-orden');
    });

    // Órdenes de Compra
    Route::prefix('ordenes')->group(function () {
        Route::get('/', [OrdenCompraController::class, 'index'])->name('ordenes.index');
        Route::get('/{orden}', [OrdenCompraController::class, 'show'])->name('ordenes.show');
        Route::patch('/{orden}/status', [OrdenCompraController::class, 'updateStatus'])->name('ordenes.update-status');
        Route::get('/{orden}/export', [OrdenCompraController::class, 'export'])->name('ordenes.export');
    });

    // Rutas existentes de sugeridos
    Route::get('/sugeridos', [SugeridoCompraController::class, 'index'])->name('sugeridos.index');
    Route::get('/sugeridos/calcular', [SugeridoCompraController::class, 'calcularSugeridos'])->name('sugeridos.calcular');

    // Nuevas rutas para sugeridos
    Route::post('/sugeridos/actualizar-cantidad', [SugeridoCompraController::class, 'actualizarCantidad'])
        ->name('sugeridos.actualizar-cantidad');

    Route::get('/sugeridos/generar-orden-individual/{sugerido}', [SugeridoCompraController::class, 'generarOrdenIndividual'])
        ->name('sugeridos.generar-orden-individual');

    Route::get('/sugeridos/generar-orden', [SugeridoCompraController::class, 'generarOrden'])
        ->name('sugeridos.generar-orden');

    // Rutas de reportes
    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/generar', [ReporteController::class, 'generarReporte'])->name('reportes.generar');
    Route::get('/reportes/cuadre-caja', [ReporteController::class, 'cuadreCaja'])->name('reportes.cuadre_caja');
    Route::get('/reportes/imprimir-facturas', [ReporteController::class, 'imprimirFacturas'])->name('reportes.imprimir_facturas');
    Route::get('/reportes/imprimir-productos', [ReporteController::class, 'imprimirProductos'])->name('reportes.imprimir_productos');
    
    // Rutas de turnos
    Route::resource('turnos', TurnoController::class);

    // Rutas de créditos
    Route::get('/creditos', [CreditoController::class, 'index'])
        ->middleware('auth')
        ->name('creditos.index');
    Route::get('/creditos/{credito}', [CreditoController::class, 'show'])->name('creditos.show');
    Route::post('/creditos/{credito}/pago', [CreditoController::class, 'registrarPago'])->name('creditos.pago');
});

// Productos API
Route::get('/api/productos/search', [ProductoController::class, 'searchApi'])
    ->name('api.productos.search');