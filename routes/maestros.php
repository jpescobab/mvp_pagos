<?php

use App\Http\Controllers\Maestros\CcostoController;
use App\Http\Controllers\Maestros\CfinancieroController;
use App\Http\Controllers\Maestros\ClienteMedidorController;
use App\Http\Controllers\Maestros\ProveedorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('maestros')->name('maestros.')->group(function () {
    Route::get('proveedores', [ProveedorController::class, 'index'])->name('proveedores.index');
    Route::get('proveedores/crear', [ProveedorController::class, 'create'])->name('proveedores.create');
    Route::post('proveedores', [ProveedorController::class, 'store'])->name('proveedores.store');
    Route::get('proveedores/{proveedor}', [ProveedorController::class, 'show'])->name('proveedores.show');
    Route::get('proveedores/{proveedor}/editar', [ProveedorController::class, 'edit'])->name('proveedores.edit');
    Route::patch('proveedores/{proveedor}', [ProveedorController::class, 'update'])->name('proveedores.update');
    Route::delete('proveedores/{proveedor}', [ProveedorController::class, 'destroy'])->name('proveedores.destroy');
    Route::get('clientes-medidores', [ClienteMedidorController::class, 'index'])->name('clientes-medidores.index');
    Route::get('cfinancieros', [CfinancieroController::class, 'index'])->name('cfinancieros.index');
    Route::get('ccostos', [CcostoController::class, 'index'])->name('ccostos.index');
});
