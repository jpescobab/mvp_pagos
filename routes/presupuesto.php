<?php

use App\Http\Controllers\Presupuesto\ImportacionPresupuestoController;
use App\Http\Controllers\Presupuesto\PresupuestoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('presupuesto')->name('presupuesto.')->group(function () {
    Route::get('importaciones', [ImportacionPresupuestoController::class, 'index'])->name('importaciones.index');
    Route::post('importaciones', [ImportacionPresupuestoController::class, 'store'])->name('importaciones.store');
    Route::get('lineas', [PresupuestoController::class, 'index'])->name('lineas.index');
});
