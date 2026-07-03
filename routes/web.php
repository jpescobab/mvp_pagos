<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/pago-proveedores.php';
require __DIR__.'/adquisiciones.php';
require __DIR__.'/documentos.php';
require __DIR__.'/indicadores.php';
require __DIR__.'/seguridad.php';
require __DIR__.'/workflow.php';
require __DIR__.'/sgf.php';
require __DIR__.'/maestros.php';
require __DIR__.'/integraciones.php';
require __DIR__.'/reportabilidad.php';
require __DIR__.'/informes-razonados.php';
