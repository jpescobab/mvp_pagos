<?php

use App\Http\Controllers\Reportabilidad\CorteReportabilidadController;
use App\Http\Controllers\Reportabilidad\PeriodoReportabilidadController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('reportabilidad')->name('reportabilidad.')->group(function () {
    Route::get('periodos', [PeriodoReportabilidadController::class, 'index'])->name('periodos.index');
    Route::post('periodos', [PeriodoReportabilidadController::class, 'store'])->name('periodos.store');
    Route::post('periodos/{periodo}/cortes', [CorteReportabilidadController::class, 'store'])->name('periodos.cortes.store');

    Route::get('cortes/{corte}', [CorteReportabilidadController::class, 'show'])->name('cortes.show');
    Route::post('cortes/{corte}/publicar', [CorteReportabilidadController::class, 'publicar'])->name('cortes.publicar');
});
