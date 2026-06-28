<?php

use App\Http\Controllers\Documentos\DocumentoEgresoCguController;
use App\Http\Controllers\Documentos\DocumentoProcesoController;
use App\Http\Controllers\Documentos\ValidacionDocumentoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('procesos/{proceso}/documentos')->name('procesos.documentos.')->group(function () {
    Route::post('/', [DocumentoProcesoController::class, 'store'])->name('store');
    Route::get('{documento}/descargar', [DocumentoProcesoController::class, 'descargar'])->name('descargar');
    Route::delete('{vinculo}', [DocumentoProcesoController::class, 'destroy'])->name('destroy');
    Route::post('{documento}/validaciones', [ValidacionDocumentoController::class, 'store'])->name('validaciones.store');
    Route::post('{documento}/versiones', [DocumentoProcesoController::class, 'nuevaVersion'])->name('versiones.store');
});

Route::middleware(['auth'])->prefix('egresos-cgu/{egresoCgu}/documentos')->name('egresos-cgu.documentos.')->group(function () {
    Route::post('/', [DocumentoEgresoCguController::class, 'store'])->name('store');
    Route::get('{documento}/descargar', [DocumentoEgresoCguController::class, 'descargar'])->name('descargar');
    Route::delete('{vinculo}', [DocumentoEgresoCguController::class, 'destroy'])->name('destroy');
});
