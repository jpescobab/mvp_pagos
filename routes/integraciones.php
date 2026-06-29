<?php

use App\Http\Controllers\Integraciones\ConectorAutomatizacionNavegadorController;
use App\Http\Controllers\Integraciones\PerfilAutenticacionNavegadorController;
use App\Http\Controllers\Integraciones\SistemaExternoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('integraciones')->name('integraciones.')->group(function () {
    Route::get('sistemas-externos', [SistemaExternoController::class, 'index'])->name('sistemas-externos.index');

    Route::get('conectores', [ConectorAutomatizacionNavegadorController::class, 'index'])->name('conectores.index');
    Route::post('conectores', [ConectorAutomatizacionNavegadorController::class, 'store'])->name('conectores.store');
    Route::post('conectores/{conector}/autorizar', [ConectorAutomatizacionNavegadorController::class, 'autorizar'])->name('conectores.autorizar');
    Route::post('conectores/{conector}/perfiles', [PerfilAutenticacionNavegadorController::class, 'store'])->name('conectores.perfiles.store');
});
