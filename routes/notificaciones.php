<?php

use App\Http\Controllers\Notificaciones\NotificacionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('notificaciones')->name('notificaciones.')->group(function () {
    Route::get('/', [NotificacionController::class, 'index'])->name('index');
    Route::post('marcar-leidas', [NotificacionController::class, 'marcarLeidas'])->name('marcar-leidas');
});
