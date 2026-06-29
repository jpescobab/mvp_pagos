<?php

use App\Http\Controllers\Integraciones\SistemaExternoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('integraciones')->name('integraciones.')->group(function () {
    Route::get('sistemas-externos', [SistemaExternoController::class, 'index'])->name('sistemas-externos.index');
});
