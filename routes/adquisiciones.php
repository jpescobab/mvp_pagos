<?php

use App\Http\Controllers\Adquisiciones\ProcesoAdquisicionController;
use App\Http\Controllers\Adquisiciones\TransicionProcesoAdquisicionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('adquisiciones')->name('adquisiciones.')->group(function () {
    Route::get('procesos', [ProcesoAdquisicionController::class, 'index'])->name('procesos.index');
    Route::get('procesos/crear', [ProcesoAdquisicionController::class, 'create'])->name('procesos.create');
    Route::post('procesos', [ProcesoAdquisicionController::class, 'store'])->name('procesos.store');
    Route::get('procesos/{proceso}', [ProcesoAdquisicionController::class, 'show'])->name('procesos.show');
    Route::post('procesos/{proceso}/transiciones', [TransicionProcesoAdquisicionController::class, 'store'])->name('procesos.transiciones.store');
});
