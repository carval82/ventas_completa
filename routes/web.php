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
use App\Http\Controllers\FacturaElectronicaController;
use App\Http\Controllers\CajaDiariaController;
use App\Http\Controllers\AlegraFacturasController;
use App\Http\Controllers\AlegraReportesController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\RemisionController;
use App\Http\Controllers\EstadoResultadosController;
use App\Http\Controllers\FlujoEfectivoController;
use App\Http\Controllers\DashboardContabilidadController;
use App\Http\Controllers\DianFacturasController;
use App\Http\Controllers\BuzonEmailController;
use App\Http\Controllers\EmailConfigurationController;
use App\Http\Controllers\Dian\AcuseController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Services\AlegraService;
// Rutas de Autenticación
Auth::routes();

// Ruta principal - FUERA del middleware auth
Route::get('/', function () {
    return redirect()->route('home');
});

Route::get('/home', [HomeController::class, 'index'])->name('home')->middleware('auth');

// Resto de rutas protegidas
Route::middleware(['auth'])->group(function () {
    
    // Módulo de administración de cerdos eliminado
    // El código ha sido migrado a pig_farm_magnament

    // Configuración
    Route::prefix('configuracion')->group(function () {
        // Empresa
        Route::get('/empresa', [EmpresaController::class, 'index'])->name('empresa.index');
        Route::get('/empresa/create', [EmpresaController::class, 'create'])->name('empresa.create');
        Route::post('/empresa', [EmpresaController::class, 'store'])->name('empresa.store');
        Route::get('/empresa/edit', [EmpresaController::class, 'edit'])->name('empresa.edit');
        Route::put('/empresa/update', [EmpresaController::class, 'update'])->name('empresa.update');
        Route::post('/empresa/probar-conexion', [EmpresaController::class, 'probarConexion'])->name('empresa.probar_conexion');
        Route::get('empresa/sincronizar-alegra', [EmpresaController::class, 'sincronizarAlegra'])->name('empresa.sincronizar-alegra');
        
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
    Route::get('/ventas', [VentaController::class, 'index'])->name('ventas.index');
    Route::get('/ventas/create', [VentaController::class, 'create'])->name('ventas.create');
    Route::post('/ventas', [VentaController::class, 'store'])->name('ventas.store');
    Route::get('/ventas/{venta}', [VentaController::class, 'show'])->name('ventas.show');
    Route::get('/ventas/{venta}/print', [VentaController::class, 'print'])->name('ventas.print');
    Route::get('/ventas/{venta}/print-58mm', [VentaController::class, 'print58mm'])->name('ventas.print-58mm');
    Route::get('/ventas/{venta}/print-80mm', [VentaController::class, 'print80mm'])->name('ventas.print-80mm');
    Route::get('/ventas/{venta}/print-media-carta', [VentaController::class, 'printMediaCarta'])->name('ventas.print-media-carta');
    Route::post('/ventas/{venta}/generar-factura-electronica', [VentaController::class, 'generarFacturaElectronica'])
        ->name('ventas.generar-factura-electronica');
    Route::post('ventas/{venta}/dian', [VentaController::class, 'emitirFacturaElectronicaDIAN'])
        ->name('ventas.dian');
    Route::get('ventas/{venta}/dian/estado', [VentaController::class, 'verificarEstadoFacturaElectronicaDIAN'])
        ->name('ventas.dian.estado');
    Route::post('ventas/{id}/generar-fe', [VentaController::class, 'generarFacturaElectronicaPost'])
        ->name('ventas.generar-fe');
    Route::post('/ventas/sincronizar-qrs', [VentaController::class, 'sincronizarQRs'])
        ->name('ventas.sincronizar-qrs');

    // Cotizaciones
    Route::resource('cotizaciones', CotizacionController::class);
    Route::post('cotizaciones/{cotizacion}/cambiar-estado', [CotizacionController::class, 'cambiarEstado'])
        ->name('cotizaciones.cambiar-estado');
    Route::post('cotizaciones/{cotizacion}/convertir-venta', [CotizacionController::class, 'convertirAVenta'])
        ->name('cotizaciones.convertir-venta');
    Route::get('cotizaciones/{cotizacion}/pdf', [CotizacionController::class, 'generarPdf'])
        ->name('cotizaciones.pdf');

    // Remisiones
    Route::resource('remisiones', RemisionController::class);
    Route::post('remisiones/{remision}/cambiar-estado', [RemisionController::class, 'cambiarEstado'])
        ->name('remisiones.cambiar-estado');
    Route::post('remisiones/{remision}/registrar-entrega', [RemisionController::class, 'registrarEntrega'])
        ->name('remisiones.registrar-entrega');
    Route::get('remisiones/{remision}/pdf', [RemisionController::class, 'generarPdf'])
        ->name('remisiones.pdf');
    Route::get('ventas/{venta}/crear-remision', [RemisionController::class, 'crearDesdeVenta'])
        ->name('remisiones.crear-desde-venta');
    Route::get('cotizaciones/{cotizacion}/crear-remision', [RemisionController::class, 'crearDesdeCotizacion'])
        ->name('remisiones.crear-desde-cotizacion');

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
    Route::get('productos/{producto}/stock', [ProductoController::class, 'showStock'])->name('productos.stock');
    Route::post('productos/{producto}/stock', [ProductoController::class, 'updateStock'])->name('productos.updateStock');
    Route::get('productos-actualizar-alegra', [ProductoController::class, 'actualizarProductosAlegra'])->name('productos.actualizarAlegra');
    Route::post('/productos/{producto}/asignar-proveedor', [ProductoController::class, 'asignarProveedor'])
        ->name('productos.asignar-proveedor');
    Route::delete('productos/{producto}/remove-proveedor', [ProductoController::class, 'removeProveedor'])
        ->name('productos.remove-proveedor');
    Route::get('productos-unidades-medida', [ProductoController::class, 'unidadesMedida'])->name('productos.unidades_medida');
    Route::post('productos-actualizar-unidades', [ProductoController::class, 'actualizarUnidades'])->name('productos.actualizar_unidades');

    // Rutas para productos
    Route::get('/productos/unidades-medida', [ProductoController::class, 'unidadesMedida'])->name('productos.unidades-medida');
    Route::post('/productos/actualizar-unidad-medida', [ProductoController::class, 'actualizarUnidadMedida'])->name('productos.actualizar-unidad-medida');


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

    // Perfil (comentado hasta crear el controlador)
    // Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rutas Facturación Electrónica Modular
    Route::prefix('facturacion')->name('facturacion.')->group(function () {
        Route::get('/', [App\Http\Controllers\FacturacionElectronicaController::class, 'index'])->name('index');
        Route::get('/configurar/{proveedor}', [App\Http\Controllers\FacturacionElectronicaController::class, 'configurar'])->name('configurar');
        Route::post('/configurar/{proveedor}', [App\Http\Controllers\FacturacionElectronicaController::class, 'guardarConfiguracion'])->name('guardar-configuracion');
        Route::post('/cambiar-proveedor', [App\Http\Controllers\FacturacionElectronicaController::class, 'cambiarProveedor'])->name('cambiar-proveedor');
        Route::post('/enviar-factura/{venta}', [App\Http\Controllers\FacturacionElectronicaController::class, 'enviarFactura'])->name('enviar-factura');
        Route::post('/probar-conexion', [App\Http\Controllers\FacturacionElectronicaController::class, 'probarConexion'])->name('probar-conexion');
        Route::get('/probar-conexion/{proveedor}', [App\Http\Controllers\FacturacionElectronicaController::class, 'probarConexionDirecto'])->name('probar-conexion-directo');
        Route::get('/sincronizar-productos/{proveedor}', [App\Http\Controllers\FacturacionElectronicaController::class, 'sincronizarProductosVista'])->name('sincronizar-productos-vista');
        Route::get('/sincronizar-clientes/{proveedor}', [App\Http\Controllers\FacturacionElectronicaController::class, 'sincronizarClientesVista'])->name('sincronizar-clientes-vista');
        Route::post('/sincronizar-productos', [App\Http\Controllers\FacturacionElectronicaController::class, 'sincronizarProductos'])->name('sincronizar-productos');
        Route::post('/sincronizar-clientes', [App\Http\Controllers\FacturacionElectronicaController::class, 'sincronizarClientes'])->name('sincronizar-clientes');
    });

    // Rutas DIAN (mantener compatibilidad) - comentado hasta crear el controlador
    // Route::prefix('dian')->name('dian.')->group(function () {
    //     Route::post('/enviar-factura/{venta}', [DianController::class, 'enviarFactura'])->name('enviar.factura');
    //     Route::get('/consultar-estado/{venta}', [DianController::class, 'consultarEstado'])->name('consultar.estado');
    //     Route::post('/reenviar-factura/{venta}', [DianController::class, 'reenviarFactura'])->name('reenviar.factura');
    // });

    // Backup
    Route::prefix('backup')->group(function () {
        Route::get('/', [BackupController::class, 'index'])->name('backup.index');
        Route::get('/create', [BackupController::class, 'create'])->name('backup.create');
        Route::post('/store', [BackupController::class, 'store'])->name('backup.store');
        Route::get('/download/{filename}', [BackupController::class, 'download'])->name('backup.download');
        Route::post('/restore/{filename}', [BackupController::class, 'restore'])->name('backup.restore');
        Route::post('/restore-data/{filename}', [BackupController::class, 'restoreDataOnly'])->name('backup.restore-data');
        Route::post('/delete/{filename}', [BackupController::class, 'delete'])->name('backup.delete');
        Route::get('/analizar/{filename}', [BackupController::class, 'analizar'])->name('backup.analizar');
        Route::post('/configure-email', [BackupController::class, 'configureEmail'])->name('backup.configure-email');
        Route::post('/configure-auto', [BackupController::class, 'configureAutoBackup'])->name('backup.configure-auto');
        Route::get('/validate/{filename}', [BackupController::class, 'validateBackup'])->name('backup.validate');
        Route::get('/validate-all', [BackupController::class, 'validateAllBackups'])->name('backup.validate-all');
        Route::get('/stats', [BackupController::class, 'getStats'])->name('backup.stats');
    });

    // Rutas de Contabilidad
    Route::middleware(['auth'])->prefix('contabilidad')->group(function () {
        // Dashboard principal
        Route::get('dashboard', [DashboardContabilidadController::class, 'index'])->name('contabilidad.dashboard');
        Route::post('dashboard/reporte-rapido', [DashboardContabilidadController::class, 'reporteRapido'])->name('contabilidad.reporte-rapido');
        
        // Plan de Cuentas
        Route::resource('plan-cuentas', PlanCuentaController::class);
        
        // Comprobantes
        Route::resource('comprobantes', ComprobanteController::class);
        Route::post('comprobantes/{comprobante}/aprobar', [ComprobanteController::class, 'aprobar'])->name('comprobantes.aprobar');
        Route::post('comprobantes/{comprobante}/anular', [ComprobanteController::class, 'anular'])->name('comprobantes.anular');
        Route::get('comprobantes/{comprobante}/imprimir', [ComprobanteController::class, 'imprimir'])->name('comprobantes.imprimir');

        // Reportes
        Route::get('reportes', [ReporteContableController::class, 'index'])->name('contabilidad.reportes.index');
        Route::get('reportes/balance-general', [ReporteContableController::class, 'balance_general'])->name('reportes.balance-general');
        Route::get('reportes/estado-resultados', [ReporteContableController::class, 'estado_resultados'])->name('reportes.estado-resultados');
        Route::get('reportes/libro-diario', [ReporteContableController::class, 'libro_diario'])->name('reportes.libro-diario');
        Route::get('reportes/libro-mayor', [ReporteContableController::class, 'libro_mayor'])->name('reportes.libro-mayor');
        Route::get('reportes/fiscal-iva', [ReporteContableController::class, 'reporte_fiscal_iva'])->name('reportes.fiscal-iva');
        Route::get('reportes/fiscal-retenciones', [ReporteContableController::class, 'reporte_fiscal_retenciones'])->name('reportes.fiscal-retenciones');
        
        // Nuevos informes NIF Colombia
        Route::get('balance-general', [BalanceGeneralController::class, 'index'])->name('balance-general.index');
        Route::post('balance-general/generar', [BalanceGeneralController::class, 'generar'])->name('balance-general.generar');
        Route::post('balance-general/exportar-pdf', [BalanceGeneralController::class, 'exportarPdf'])->name('balance-general.pdf');
        Route::post('balance-general/detalle-cuenta', [BalanceGeneralController::class, 'detalleCuenta'])->name('balance-general.detalle-cuenta');
        Route::post('balance-general/comparativo', [BalanceGeneralController::class, 'comparativo'])->name('balance-general.comparativo');
        
        Route::get('estado-resultados', [EstadoResultadosController::class, 'index'])->name('estado-resultados.index');
        Route::post('estado-resultados/generar', [EstadoResultadosController::class, 'generar'])->name('estado-resultados.generar');
        Route::post('estado-resultados/exportar-pdf', [EstadoResultadosController::class, 'exportarPdf'])->name('estado-resultados.pdf');
        
        Route::get('flujo-efectivo', [FlujoEfectivoController::class, 'index'])->name('flujo-efectivo.index');
        Route::post('flujo-efectivo/generar', [FlujoEfectivoController::class, 'generar'])->name('flujo-efectivo.generar');
        Route::post('flujo-efectivo/exportar-pdf', [FlujoEfectivoController::class, 'exportarPdf'])->name('flujo-efectivo.pdf');
        Route::post('estado-resultados/analisis-margenes', [EstadoResultadosController::class, 'analisisMaxgenes'])->name('estado-resultados.margenes');
        Route::post('estado-resultados/comparativo-mensual', [EstadoResultadosController::class, 'comparativoMensual'])->name('estado-resultados.comparativo');
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
        
    // Módulo de Facturas de Alegra
    Route::prefix('alegra')->name('alegra.')->group(function () {
        Route::get('/facturas', [AlegraFacturasController::class, 'index'])->name('facturas.index');
        Route::get('/facturas/{id}', [AlegraFacturasController::class, 'show'])->name('facturas.show');
        Route::post('/facturas/vincular', [AlegraFacturasController::class, 'vincular'])->name('facturas.vincular');
        Route::get('/facturas/{id}/estado', [AlegraFacturasController::class, 'estado'])->name('facturas.estado');
        
        // Reportes de Facturas Electrónicas
        Route::get('/reportes/dashboard', [AlegraReportesController::class, 'dashboard'])->name('reportes.dashboard');
        Route::get('/reportes/periodo', [AlegraReportesController::class, 'reportePeriodo'])->name('reportes.periodo');
    });

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

    // Rutas de cajas diarias
    Route::resource('cajas', CajaDiariaController::class);
    Route::get('/cajas/{caja}/reporte', [CajaDiariaController::class, 'reporte'])->name('cajas.reporte');
    Route::get('/cajas-estado-actual', [CajaDiariaController::class, 'estadoActual'])->name('cajas.estado-actual');
    Route::get('/cajas-movimientos/create', [CajaDiariaController::class, 'createMovimiento'])->name('cajas.movimientos.create');
    Route::post('/cajas-movimientos', [CajaDiariaController::class, 'storeMovimiento'])->name('cajas.movimientos.store');

    // Rutas de créditos
    Route::get('/creditos', [CreditoController::class, 'index'])
        ->middleware('auth')
        ->name('creditos.index');
    Route::get('/creditos/{credito}', [CreditoController::class, 'show'])->name('creditos.show');
    Route::post('/creditos/{credito}/pago', [CreditoController::class, 'registrarPago'])->name('creditos.pago');

    // Rutas para unidades de medida de productos
    Route::get('/productos/unidades-medida', [ProductoController::class, 'unidadesMedida'])->name('productos.unidades-medida');
    Route::post('/productos/actualizar-unidad-medida', [ProductoController::class, 'actualizarUnidadMedida'])->name('productos.actualizar-unidad-medida');

    // Rutas para facturas electrónicas
    Route::prefix('facturas-electronicas')->group(function () {
        Route::get('/', [FacturaElectronicaController::class, 'index'])->name('facturas.electronicas.index');
        Route::get('/{id}', [FacturaElectronicaController::class, 'show'])->name('facturas.electronicas.show');
        Route::get('/{id}/pdf', [FacturaElectronicaController::class, 'descargarPDF'])->name('facturas.electronicas.descargar-pdf');
        Route::get('/{id}/tirilla', [FacturaElectronicaController::class, 'imprimirTirilla'])->name('facturas.electronicas.imprimir-tirilla');
        Route::get('/{id}/enviar-dian', [FacturaElectronicaController::class, 'enviarADian'])->name('facturas.electronicas.enviar-dian');
        Route::get('/{id}/verificar-estado', [FacturaElectronicaController::class, 'verificarEstado'])->name('facturas.electronicas.verificar-estado');
        Route::get('/{id}/abrir-y-emitir', [FacturaElectronicaController::class, 'abrirYEmitir'])->name('facturas.electronicas.abrir-y-emitir');
        Route::get('/{id}/abrir-factura', [FacturaElectronicaController::class, 'abrirFactura'])->name('facturas.electronicas.abrir-factura');
        Route::get('/{id}/imprimir', [FacturaElectronicaController::class, 'imprimirFactura'])->name('facturas.electronicas.imprimir');
        Route::get('/{id}/detalles-impresion', [FacturaElectronicaController::class, 'obtenerDetallesParaImpresion'])->name('facturas.electronicas.detalles-impresion');
        
        // Rutas para emisión directa
        Route::get('/{id}/emitir-directa', [FacturaElectronicaController::class, 'mostrarEmisionDirecta'])->name('facturas.electronicas.emitir-directa');
        Route::post('/{id}/procesar-emision-directa', [FacturaElectronicaController::class, 'procesarEmisionDirecta'])->name('facturas.electronicas.procesar-emision-directa');
    });

    // Ruta para la página "Acerca de"
    Route::get('/about', [AboutController::class, 'index'])->name('about');
});

// Productos API
Route::get('/api/productos/search', [ProductoController::class, 'searchApi'])
    ->name('api.productos.search');
Route::get('/api/productos/buscar-por-codigo', [ProductoController::class, 'buscarPorCodigo'])
    ->name('api.productos.buscar-por-codigo');
Route::get('/api/productos/buscar-por-codigo-relacionado', [ProductoController::class, 'buscarProductosPorCodigoRelacionado'])
    ->name('api.productos.buscar-por-codigo-relacionado');

Route::get('/test-alegra-invoice', function(AlegraService $alegra) {
    return $alegra->obtenerUltimaFactura();
});

Route::get('/empresa/obtener-resolucion', [EmpresaController::class, 'obtenerResolucionAlegra'])
    ->name('empresa.obtener-resolucion');

Route::get('/empresa/verificar-fe', [EmpresaController::class, 'verificarFacturacionElectronica'])
    ->name('empresa.verificar-fe');

// Ruta de prueba para equivalencias
Route::get('/test-equivalencias', function () {
    return view('test-equivalencias');
})->name('test.equivalencias');

// Ruta de prueba simple
Route::get('/test-conversion-simple', function () {
    return response()->file(public_path('../test_conversion_simple.html'));
})->name('test.conversion.simple');

// Rutas del Módulo DIAN
Route::middleware(['auth'])->prefix('dian')->name('dian.')->group(function () {
    Route::get('/', [DianFacturasController::class, 'index'])->name('dashboard');
    Route::get('/configuracion', [DianFacturasController::class, 'configuracion'])->name('configuracion');
    Route::post('/configuracion', [DianFacturasController::class, 'guardarConfiguracion'])->name('configuracion.guardar');
    Route::post('/autocompletar-gmail', [DianFacturasController::class, 'autocompletarDesdeGmail'])->name('autocompletar-gmail');
    Route::post('/probar-conexion', [DianFacturasController::class, 'probarConexion'])->name('probar-conexion');
    // Vistas dedicadas
    Route::get('/procesar-emails', [DianFacturasController::class, 'mostrarProcesarEmails'])->name('procesar-emails.vista');
    Route::get('/enviar-acuses', [DianFacturasController::class, 'mostrarEnviarAcuses'])->name('enviar-acuses.vista');
    
    // Acciones de procesamiento
    Route::post('/procesar-emails', [DianFacturasController::class, 'procesarEmails'])->name('procesar-emails');
    Route::post('/enviar-acuses', [DianFacturasController::class, 'enviarAcuses'])->name('enviar-acuses');
    Route::post('/toggle-activacion', [DianFacturasController::class, 'toggleActivacion'])->name('toggle-activacion');
    Route::get('/facturas', [DianFacturasController::class, 'facturas'])->name('facturas');
    Route::get('/facturas/{factura}', [DianFacturasController::class, 'verFactura'])->name('factura.detalle');
    Route::get('/facturas/{factura}/detalle', [DianFacturasController::class, 'detalleFacturaAjax'])->name('factura.detalle.ajax');
    Route::get('/facturas/{factura}/xml', [DianFacturasController::class, 'descargarXML'])->name('factura.xml');
    Route::post('/facturas/{factura}/acuse', [DianFacturasController::class, 'enviarAcuseIndividual'])->name('factura.acuse');
    Route::post('/subir-xml', [DianFacturasController::class, 'subirFacturaXML'])->name('subir-xml');
    Route::get('/oauth/authorize', [DianFacturasController::class, 'iniciarAutorizacionOAuth'])->name('oauth.authorize');
    Route::get('/oauth/callback', [DianFacturasController::class, 'callbackOAuth'])->name('oauth.callback');
    
    // Rutas del Buzón de Correos
    Route::get('/buzon', [BuzonEmailController::class, 'index'])->name('buzon');
    Route::get('/buzon/{email}', [BuzonEmailController::class, 'verEmail'])->name('buzon.email');
    Route::post('/buzon/sincronizar', [BuzonEmailController::class, 'sincronizar'])->name('buzon.sincronizar');
    Route::post('/buzon/procesar', [BuzonEmailController::class, 'procesar'])->name('buzon.procesar');
    
    // Rutas de Acuses de Recibo
    Route::get('/acuses', [AcuseController::class, 'index'])->name('acuses.index');
    Route::get('/acuses/{email}', [AcuseController::class, 'show'])->name('acuses.show');
    Route::post('/acuses/{email}/enviar', [AcuseController::class, 'enviar'])->name('acuses.enviar');
    Route::post('/acuses/{email}/reenviar', [AcuseController::class, 'reenviar'])->name('acuses.reenviar');
});

// Rutas de Configuración de Email (fuera del grupo DIAN para uso general)
Route::middleware(['auth'])->group(function () {
    Route::resource('email-configurations', EmailConfigurationController::class);
    Route::post('email-configurations/{emailConfiguration}/probar', [EmailConfigurationController::class, 'probar'])->name('email-configurations.probar');
    Route::patch('email-configurations/{emailConfiguration}/toggle', [EmailConfigurationController::class, 'toggleActivo'])->name('email-configurations.toggle');
});