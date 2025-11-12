<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\API\AlegraController;
use App\Http\Controllers\Api\ConversionController;
use App\Http\Controllers\Api\ConversionUnidadesController;

// Ruta para productos
Route::middleware('api')->group(function () {
    Route::post('/productos', [ProductoController::class, 'apiStore']);
    Route::get('/productos/search', [ProductoController::class, 'search'])->name('api.productos.search');
    
    // Rutas para conversiones de unidades (sistema anterior)
    Route::get('/productos/{id}/unidades', [ConversionController::class, 'obtenerUnidadesDisponibles']);
    Route::post('/productos/convertir', [ConversionController::class, 'convertir']);
    Route::get('/productos/{id}/info-conversion', [ConversionController::class, 'informacionProducto']);
    
    // Rutas para conversiones de unidades (sistema nuevo)
    Route::get('/conversiones/unidades-disponibles', [ConversionUnidadesController::class, 'obtenerUnidadesDisponibles']);
    Route::post('/conversiones/convertir-unidad', [ConversionUnidadesController::class, 'convertirUnidad']);
    Route::post('/conversiones/validar-stock', [ConversionUnidadesController::class, 'validarStockConversion']);
});

// Rutas de Alegra
Route::middleware('alegra.api')->prefix('alegra')->group(function () {
    Route::get('/test', [AlegraController::class, 'test']);
});
