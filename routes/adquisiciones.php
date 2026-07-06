<?php

use App\Http\Controllers\Adquisiciones\LicitacionMercadoPublicoController;
use App\Http\Controllers\Adquisiciones\OrdenCompraMercadoPublicoController;
use App\Http\Controllers\Adquisiciones\ProcesoAdquisicionController;
use App\Http\Controllers\Adquisiciones\TransicionProcesoAdquisicionController;
use App\Http\Controllers\Adquisiciones\VinculoProcesoAdquisicionLicitacionMercadoPublicoController;
use App\Http\Controllers\Adquisiciones\VinculoProcesoAdquisicionOrdenCompraMercadoPublicoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('adquisiciones')->name('adquisiciones.')->group(function () {
    Route::get('procesos', [ProcesoAdquisicionController::class, 'index'])->name('procesos.index');
    Route::get('procesos/crear', [ProcesoAdquisicionController::class, 'create'])->name('procesos.create');
    Route::post('procesos', [ProcesoAdquisicionController::class, 'store'])->name('procesos.store');
    Route::get('procesos/{proceso}', [ProcesoAdquisicionController::class, 'show'])->name('procesos.show');
    Route::post('procesos/{proceso}/transiciones', [TransicionProcesoAdquisicionController::class, 'store'])->name('procesos.transiciones.store');

    Route::prefix('ordenes-compra-mercado-publico')->name('ordenes_compra_mp.')->group(function () {
        Route::get('/', [OrdenCompraMercadoPublicoController::class, 'index'])->name('index');
        Route::post('buscar', [OrdenCompraMercadoPublicoController::class, 'buscar'])->name('buscar');
        Route::post('guardar', [OrdenCompraMercadoPublicoController::class, 'guardar'])->name('guardar');
        Route::get('pdf', [OrdenCompraMercadoPublicoController::class, 'pdf'])->name('pdf');
        Route::get('{orden}', [OrdenCompraMercadoPublicoController::class, 'show'])->name('show');
        Route::post('{orden}/verificar', [OrdenCompraMercadoPublicoController::class, 'verificar'])->name('verificar');
        Route::post('{orden}/actualizar', [OrdenCompraMercadoPublicoController::class, 'actualizar'])->name('actualizar');
        Route::post('{orden}/vinculo', [VinculoProcesoAdquisicionOrdenCompraMercadoPublicoController::class, 'store'])->name('vinculo.store');
        Route::delete('{orden}/vinculo', [VinculoProcesoAdquisicionOrdenCompraMercadoPublicoController::class, 'destroy'])->name('vinculo.destroy');
    });

    Route::prefix('licitaciones-mercado-publico')->name('licitaciones_mp.')->group(function () {
        Route::get('/', [LicitacionMercadoPublicoController::class, 'index'])->name('index');
        Route::post('buscar', [LicitacionMercadoPublicoController::class, 'buscar'])->name('buscar');
        Route::post('guardar', [LicitacionMercadoPublicoController::class, 'guardar'])->name('guardar');
        Route::get('{licitacion}', [LicitacionMercadoPublicoController::class, 'show'])->name('show');
        Route::post('{licitacion}/verificar', [LicitacionMercadoPublicoController::class, 'verificar'])->name('verificar');
        Route::post('{licitacion}/actualizar', [LicitacionMercadoPublicoController::class, 'actualizar'])->name('actualizar');
        Route::post('{licitacion}/vinculo', [VinculoProcesoAdquisicionLicitacionMercadoPublicoController::class, 'store'])->name('vinculo.store');
        Route::delete('{licitacion}/vinculo', [VinculoProcesoAdquisicionLicitacionMercadoPublicoController::class, 'destroy'])->name('vinculo.destroy');
    });
});
