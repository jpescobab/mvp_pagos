<?php

use App\Http\Controllers\Documentos\DocumentoProcesoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('procesos/{proceso}/documentos')->name('procesos.documentos.')->group(function () {
    Route::post('/', [DocumentoProcesoController::class, 'store'])->name('store');
    Route::get('{documento}/descargar', [DocumentoProcesoController::class, 'descargar'])->name('descargar');
    Route::delete('{vinculo}', [DocumentoProcesoController::class, 'destroy'])->name('destroy');
});
