<?php

use App\Http\Controllers\Indicadores\IndicadorEconomicoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->get('indicadores-economicos', [IndicadorEconomicoController::class, 'index'])->name('indicadores-economicos.index');
