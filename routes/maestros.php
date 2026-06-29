<?php

use App\Http\Controllers\Maestros\ProveedorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('maestros')->name('maestros.')->group(function () {
    Route::get('proveedores', [ProveedorController::class, 'index'])->name('proveedores.index');
});
