<?php

use App\Http\Controllers\Sgf\ImportacionSgfController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('sgf')->name('sgf.')->group(function () {
    Route::get('importaciones', [ImportacionSgfController::class, 'index'])->name('importaciones.index');
    Route::get('importaciones/{importacionSgf}', [ImportacionSgfController::class, 'show'])->name('importaciones.show');
});
