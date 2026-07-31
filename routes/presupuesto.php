<?php

use App\Http\Controllers\Presupuesto\AnularCertificadoDisponibilidadController;
use App\Http\Controllers\Presupuesto\CertificadoDisponibilidadPresupuestariaController;
use App\Http\Controllers\Presupuesto\ImportacionPresupuestoController;
use App\Http\Controllers\Presupuesto\ParidadCdpController;
use App\Http\Controllers\Presupuesto\PresupuestoController;
use App\Http\Controllers\Presupuesto\TransicionCertificadoDisponibilidadController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('presupuesto')->name('presupuesto.')->group(function () {
    Route::get('importaciones', [ImportacionPresupuestoController::class, 'index'])->name('importaciones.index');
    Route::post('importaciones', [ImportacionPresupuestoController::class, 'store'])->name('importaciones.store');
    Route::get('lineas', [PresupuestoController::class, 'index'])->name('lineas.index');

    Route::get('cdps', [CertificadoDisponibilidadPresupuestariaController::class, 'index'])->name('cdps.index');
    Route::get('cdps/crear', [CertificadoDisponibilidadPresupuestariaController::class, 'create'])->name('cdps.create');
    Route::get('cdps/paridad', [ParidadCdpController::class, 'show'])->name('cdps.paridad');
    Route::post('cdps', [CertificadoDisponibilidadPresupuestariaController::class, 'store'])->name('cdps.store');
    Route::get('cdps/{cdp}', [CertificadoDisponibilidadPresupuestariaController::class, 'show'])->name('cdps.show');
    Route::get('cdps/{cdp}/editar', [CertificadoDisponibilidadPresupuestariaController::class, 'edit'])->name('cdps.edit');
    Route::put('cdps/{cdp}', [CertificadoDisponibilidadPresupuestariaController::class, 'update'])->name('cdps.update');
    Route::post('cdps/{cdp}/transiciones', [TransicionCertificadoDisponibilidadController::class, 'store'])->name('cdps.transiciones.store');
    Route::post('cdps/{cdp}/anular', [AnularCertificadoDisponibilidadController::class, 'store'])->name('cdps.anular');
});
