<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\API\AlegraController;

// Ruta para productos
Route::middleware('api')->group(function () {
    Route::post('/productos', [ProductoController::class, 'apiStore']);
    Route::get('/productos/search', [ProductoController::class, 'search'])->name('api.productos.search');
});

// Rutas de Alegra
Route::middleware('alegra.api')->prefix('alegra')->group(function () {
    Route::get('/test', [AlegraController::class, 'test']);
});
