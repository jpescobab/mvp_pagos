<?php

use App\Http\Controllers\PagoProveedores\BuscarProcesoAdquisicionController;
use App\Http\Controllers\PagoProveedores\CasoPagoProveedorController;
use App\Http\Controllers\PagoProveedores\EgresoCguController;
use App\Http\Controllers\PagoProveedores\TransicionCasoPagoProveedorController;
use App\Http\Controllers\PagoProveedores\VinculoAdquisicionCasoPagoProveedorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('pago-proveedores')->name('pago-proveedores.')->group(function () {
    Route::get('casos', [CasoPagoProveedorController::class, 'index'])->name('casos.index');
    Route::get('casos/{caso}', [CasoPagoProveedorController::class, 'show'])->name('casos.show');
    Route::post('casos/{caso}/transiciones', [TransicionCasoPagoProveedorController::class, 'store'])->name('casos.transiciones.store');

    Route::get('casos/{caso}/buscar-adquisiciones', BuscarProcesoAdquisicionController::class)->name('casos.buscar-adquisiciones');
    Route::post('casos/{caso}/vincular-adquisicion', [VinculoAdquisicionCasoPagoProveedorController::class, 'store'])->name('casos.vincular-adquisicion.store');
    Route::delete('casos/{caso}/vincular-adquisicion', [VinculoAdquisicionCasoPagoProveedorController::class, 'destroy'])->name('casos.vincular-adquisicion.destroy');

    Route::get('egresos-cgu', [EgresoCguController::class, 'index'])->name('egresos-cgu.index');
    Route::get('egresos-cgu/crear', [EgresoCguController::class, 'create'])->name('egresos-cgu.create');
    Route::post('egresos-cgu', [EgresoCguController::class, 'store'])->name('egresos-cgu.store');
});
