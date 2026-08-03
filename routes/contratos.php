<?php

use App\Http\Controllers\Contratos\ContratoController;
use App\Http\Controllers\Contratos\ContratoCuotaController;
use App\Http\Controllers\Contratos\ContratoItemConvenioPrecioController;
use App\Http\Controllers\Contratos\TransicionContratoController;
use App\Http\Controllers\Contratos\VinculoContratoLicitacionMercadoPublicoController;
use App\Http\Controllers\Contratos\VinculoContratoProcesoAdquisicionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('contratos')->name('contratos.')->group(function () {
    Route::get('/', [ContratoController::class, 'index'])->name('index');
    Route::get('crear', [ContratoController::class, 'create'])->name('create');
    Route::post('/', [ContratoController::class, 'store'])->name('store');
    Route::get('{contrato}/editar', [ContratoController::class, 'edit'])->name('edit');
    Route::get('{contrato}', [ContratoController::class, 'show'])->name('show');
    Route::match(['put', 'patch'], '{contrato}', [ContratoController::class, 'update'])->name('update');
    Route::post('{contrato}/transiciones', [TransicionContratoController::class, 'store'])->name('transiciones.store');

    Route::post('{contrato}/items-convenio-precio', [ContratoItemConvenioPrecioController::class, 'store'])->name('items_convenio_precio.store');
    Route::delete('{contrato}/items-convenio-precio/{item}', [ContratoItemConvenioPrecioController::class, 'destroy'])->name('items_convenio_precio.destroy');

    Route::post('{contrato}/cuotas/generar', [ContratoCuotaController::class, 'generar'])->name('cuotas.generar');
    Route::match(['put', 'patch'], '{contrato}/cuotas/{cuota}', [ContratoCuotaController::class, 'update'])->name('cuotas.update');
    Route::post('{contrato}/cuotas/{cuota}/vincular-pago', [ContratoCuotaController::class, 'vincularPago'])->name('cuotas.vincular_pago');
    Route::delete('{contrato}/cuotas/{cuota}/vincular-pago', [ContratoCuotaController::class, 'desvincularPago'])->name('cuotas.desvincular_pago');

    Route::post('{contrato}/vinculo-proceso-adquisicion', [VinculoContratoProcesoAdquisicionController::class, 'store'])->name('vinculo_proceso_adquisicion.store');
    Route::delete('{contrato}/vinculo-proceso-adquisicion', [VinculoContratoProcesoAdquisicionController::class, 'destroy'])->name('vinculo_proceso_adquisicion.destroy');

    Route::post('{contrato}/vinculo-licitacion-mp', [VinculoContratoLicitacionMercadoPublicoController::class, 'store'])->name('vinculo_licitacion_mp.store');
    Route::delete('{contrato}/vinculo-licitacion-mp', [VinculoContratoLicitacionMercadoPublicoController::class, 'destroy'])->name('vinculo_licitacion_mp.destroy');
});
