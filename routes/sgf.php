<?php

use App\Http\Controllers\Sgf\EliminarImportacionSgfController;
use App\Http\Controllers\Sgf\ImportacionSgfController;
use App\Http\Controllers\Sgf\ImportarCasosGrupoPagoOperacionesSgfController;
use App\Http\Controllers\Sgf\ImportarCasosPendientesSgfController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('sgf')->name('sgf.')->group(function () {
    Route::get('importaciones', [ImportacionSgfController::class, 'index'])->name('importaciones.index');
    Route::get('importaciones/{trabajoIntegracion}', [ImportacionSgfController::class, 'show'])->name('importaciones.show');
    Route::delete('importaciones/{trabajoIntegracion}', [EliminarImportacionSgfController::class, 'destroy'])->name('importaciones.destroy');

    Route::post('casos/importar-pendientes', [ImportarCasosPendientesSgfController::class, 'store'])->name('casos.importar-pendientes');
    Route::post('casos/importar-grupo-pago-operaciones', [ImportarCasosGrupoPagoOperacionesSgfController::class, 'store'])->name('casos.importar-grupo-pago-operaciones');
});
