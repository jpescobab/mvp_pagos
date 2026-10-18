<?php

use App\Http\Controllers\ServiciosContratados\ServicioContratadoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('servicios-contratados')->name('servicios-contratados.')->group(function () {
    Route::get('/', [ServicioContratadoController::class, 'index'])->name('index');
    Route::get('crear', [ServicioContratadoController::class, 'create'])->name('create');
    Route::post('/', [ServicioContratadoController::class, 'store'])->name('store');
    Route::get('{servicioContratado}', [ServicioContratadoController::class, 'show'])->name('show');
    Route::get('{servicioContratado}/editar', [ServicioContratadoController::class, 'edit'])->name('edit');
    Route::match(['put', 'patch'], '{servicioContratado}', [ServicioContratadoController::class, 'update'])->name('update');
    Route::post('{servicioContratado}/terminar', [ServicioContratadoController::class, 'terminar'])->name('terminar');
});
