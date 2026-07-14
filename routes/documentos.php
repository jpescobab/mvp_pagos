<?php

use App\Http\Controllers\Documentos\DocumentoProcesoController;
use App\Http\Controllers\Documentos\ValidacionDocumentoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('procesos/{proceso}/documentos')->name('procesos.documentos.')->group(function () {
    Route::post('/', [DocumentoProcesoController::class, 'store'])->name('store');
    Route::get('{documento}/descargar', [DocumentoProcesoController::class, 'descargar'])->name('descargar');
    Route::delete('{vinculo}', [DocumentoProcesoController::class, 'destroy'])->name('destroy');
    Route::post('{documento}/validaciones', [ValidacionDocumentoController::class, 'store'])->name('validaciones.store');
    Route::post('{documento}/versiones', [DocumentoProcesoController::class, 'nuevaVersion'])->name('versiones.store');
    Route::patch('{documento}/tipo-documento', [DocumentoProcesoController::class, 'reclasificar'])->name('tipo-documento.store');
});
