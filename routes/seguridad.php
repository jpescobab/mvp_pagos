<?php

use App\Http\Controllers\Seguridad\AuditoriaController;
use App\Http\Controllers\Seguridad\RoleController;
use App\Http\Controllers\Seguridad\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->get('auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');

Route::middleware(['auth'])->prefix('usuarios')->name('usuarios.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('create', [UserController::class, 'create'])->name('create');
    Route::post('/', [UserController::class, 'store'])->name('store');
    // Debe ir DESPUÉS de 'create': si se declara antes, /usuarios/create se
    // resuelve contra {usuario} y el binding implícito devuelve 404.
    Route::get('{usuario}', [UserController::class, 'show'])->name('show');
    Route::get('{usuario}/editar', [UserController::class, 'edit'])->name('edit');
    Route::patch('{usuario}', [UserController::class, 'update'])->name('update');
    Route::patch('{usuario}/activar', [UserController::class, 'activar'])->name('activar');
    Route::patch('{usuario}/desactivar', [UserController::class, 'desactivar'])->name('desactivar');
    Route::post('{usuario}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
    Route::patch('{usuario}/roles', [UserController::class, 'actualizarRoles'])->name('roles.update');
});

Route::middleware(['auth'])->prefix('roles')->name('roles.')->group(function () {
    Route::get('/', [RoleController::class, 'index'])->name('index');
    Route::get('create', [RoleController::class, 'create'])->name('create');
    Route::post('/', [RoleController::class, 'store'])->name('store');
    Route::get('{role}/editar', [RoleController::class, 'edit'])->name('edit');
    Route::patch('{role}', [RoleController::class, 'update'])->name('update');
    Route::delete('{role}', [RoleController::class, 'destroy'])->name('destroy');
});
