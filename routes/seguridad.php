<?php

use App\Http\Controllers\Seguridad\AuditoriaController;
use App\Http\Controllers\Seguridad\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->get('auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');

Route::middleware(['auth'])->prefix('usuarios')->name('usuarios.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::patch('{usuario}/activar', [UserController::class, 'activar'])->name('activar');
    Route::patch('{usuario}/desactivar', [UserController::class, 'desactivar'])->name('desactivar');
    Route::post('{usuario}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
});
