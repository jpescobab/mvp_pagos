<?php

use App\Http\Controllers\InformesRazonados\DefinicionInformeRazonadoController;
use App\Http\Controllers\InformesRazonados\EjecucionInformeRazonadoController;
use App\Http\Controllers\InformesRazonados\TransicionEjecucionInformeRazonadoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('informes-razonados')->name('informes-razonados.')->group(function () {
    Route::get('definiciones', [DefinicionInformeRazonadoController::class, 'index'])->name('definiciones.index');
    Route::get('definiciones/crear', [DefinicionInformeRazonadoController::class, 'create'])->name('definiciones.create');
    Route::post('definiciones', [DefinicionInformeRazonadoController::class, 'store'])->name('definiciones.store');
    Route::get('definiciones/{definicion}', [DefinicionInformeRazonadoController::class, 'show'])->name('definiciones.show');
    Route::get('definiciones/{definicion}/editar', [DefinicionInformeRazonadoController::class, 'edit'])->name('definiciones.edit');
    Route::patch('definiciones/{definicion}', [DefinicionInformeRazonadoController::class, 'update'])->name('definiciones.update');
    Route::delete('definiciones/{definicion}', [DefinicionInformeRazonadoController::class, 'destroy'])->name('definiciones.destroy');

    Route::get('ejecuciones', [EjecucionInformeRazonadoController::class, 'index'])->name('ejecuciones.index');
    Route::post('ejecuciones', [EjecucionInformeRazonadoController::class, 'store'])->name('ejecuciones.store');
    Route::get('ejecuciones/{ejecucion}', [EjecucionInformeRazonadoController::class, 'show'])->name('ejecuciones.show');
    Route::post('ejecuciones/{ejecucion}/transiciones', [TransicionEjecucionInformeRazonadoController::class, 'store'])->name('ejecuciones.transiciones.store');
});
