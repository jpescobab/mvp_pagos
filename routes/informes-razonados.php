<?php

use App\Http\Controllers\InformesRazonados\DefinicionInformeRazonadoController;
use App\Http\Controllers\InformesRazonados\EjecucionInformeRazonadoController;
use App\Http\Controllers\InformesRazonados\ExcepcionInformeRazonadoController;
use App\Http\Controllers\InformesRazonados\NarrativaInformeRazonadoController;
use App\Http\Controllers\InformesRazonados\SeccionInformeRazonadoController;
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

    Route::post('ejecuciones/{ejecucion}/narrativas', [NarrativaInformeRazonadoController::class, 'store'])->name('ejecuciones.narrativas.store');
    Route::patch('narrativas/{narrativa}', [NarrativaInformeRazonadoController::class, 'update'])->name('narrativas.update');
    Route::delete('narrativas/{narrativa}', [NarrativaInformeRazonadoController::class, 'destroy'])->name('narrativas.destroy');
    Route::post('narrativas/{narrativa}/revisar', [NarrativaInformeRazonadoController::class, 'revisar'])->name('narrativas.revisar');

    Route::post('ejecuciones/{ejecucion}/secciones', [SeccionInformeRazonadoController::class, 'store'])->name('ejecuciones.secciones.store');
    Route::patch('secciones/{seccion}', [SeccionInformeRazonadoController::class, 'update'])->name('secciones.update');
    Route::delete('secciones/{seccion}', [SeccionInformeRazonadoController::class, 'destroy'])->name('secciones.destroy');

    Route::post('ejecuciones/{ejecucion}/excepciones', [ExcepcionInformeRazonadoController::class, 'store'])->name('ejecuciones.excepciones.store');
    Route::patch('excepciones/{excepcion}', [ExcepcionInformeRazonadoController::class, 'update'])->name('excepciones.update');
    Route::delete('excepciones/{excepcion}', [ExcepcionInformeRazonadoController::class, 'destroy'])->name('excepciones.destroy');
});
