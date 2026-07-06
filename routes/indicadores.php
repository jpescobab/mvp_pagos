<?php

use App\Http\Controllers\Indicadores\IndicadorEconomicoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('indicadores-economicos')->name('indicadores-economicos.')->group(function () {
    Route::get('/', [IndicadorEconomicoController::class, 'index'])->name('index');
    Route::post('importar-mensual', [IndicadorEconomicoController::class, 'importarMensual'])->name('importar-mensual');
});
