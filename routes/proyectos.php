<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProyectoController;

Route::prefix('proyectos')->name('proyectos.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [ProyectoController::class, 'index'])->name('index');
    Route::post('/', [ProyectoController::class, 'store'])->name('store');
    Route::put('{proyecto}', [ProyectoController::class, 'update'])->name('update');
    Route::delete('{proyecto}', [ProyectoController::class, 'destroy'])->name('destroy');
    Route::get('export', [ProyectoController::class, 'export'])->name('export');
});