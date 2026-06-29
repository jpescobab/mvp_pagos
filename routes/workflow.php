<?php

use App\Http\Controllers\Workflow\DefinicionWorkflowController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('workflow')->name('workflow.')->group(function () {
    Route::get('definiciones', [DefinicionWorkflowController::class, 'index'])->name('definiciones.index');
    Route::get('definiciones/{definicionWorkflow}', [DefinicionWorkflowController::class, 'show'])->name('definiciones.show');
});
