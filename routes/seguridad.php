<?php

use App\Http\Controllers\Seguridad\AuditoriaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->get('auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
