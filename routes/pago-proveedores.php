<?php

use App\Http\Controllers\PagoProveedores\BuscarProcesoAdquisicionController;
use App\Http\Controllers\PagoProveedores\CasoPagoProveedorController;
use App\Http\Controllers\PagoProveedores\EgresoCguController;
use App\Http\Controllers\PagoProveedores\RegistroContableCguController;
use App\Http\Controllers\PagoProveedores\RegistroPagoBancarioController;
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

    Route::post('casos/{caso}/registros-contables-cgu', [RegistroContableCguController::class, 'store'])->name('casos.registros-contables-cgu.store');
    Route::post('casos/{caso}/registros-pago-bancario', [RegistroPagoBancarioController::class, 'store'])->name('casos.registros-pago-bancario.store');

    Route::get('egresos-cgu', [EgresoCguController::class, 'index'])->name('egresos-cgu.index');
    Route::get('egresos-cgu/crear', [EgresoCguController::class, 'create'])->name('egresos-cgu.create');
    Route::post('egresos-cgu', [EgresoCguController::class, 'store'])->name('egresos-cgu.store');
    Route::get('egresos-cgu/{egresoCgu}', [EgresoCguController::class, 'show'])->name('egresos-cgu.show');
});
