<?php

use App\Http\Controllers\ConsumoBasico\ConsumoBasicoController;
use App\Http\Controllers\ConsumoBasico\ConsumoBasicoDocumentoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('consumo-basico')->name('consumo-basico.')->group(function () {
    Route::get('casos/{caso}/crear', [ConsumoBasicoController::class, 'create'])->name('create');
    Route::post('casos/{caso}', [ConsumoBasicoController::class, 'store'])->name('store');
    Route::get('{consumoBasico}', [ConsumoBasicoController::class, 'show'])->name('show');
    Route::get('{consumoBasico}/editar', [ConsumoBasicoController::class, 'edit'])->name('edit');
    Route::match(['put', 'patch'], '{consumoBasico}', [ConsumoBasicoController::class, 'update'])->name('update');

    Route::post('{consumoBasico}/documento', [ConsumoBasicoDocumentoController::class, 'store'])->name('documento.store');
    Route::delete('{consumoBasico}/documento/{vinculo}', [ConsumoBasicoDocumentoController::class, 'destroy'])->name('documento.destroy');
    Route::get('{consumoBasico}/documento/{documento}/descargar', [ConsumoBasicoDocumentoController::class, 'descargar'])->name('documento.descargar');
});
