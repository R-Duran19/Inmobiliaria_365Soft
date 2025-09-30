<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProyectoController;

Route::middleware(['auth', 'verified', 'role:admin'])
    ->get('proyectos', [ProyectoController::class, 'index'])
    ->name('proyectos');

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('proyectos')
    ->name('proyectos.')
    ->group(function () {
        Route::post('/', [ProyectoController::class, 'store'])->name('store');
        Route::put('/{proyecto}', [ProyectoController::class, 'update'])->name('update');
        Route::delete('/{proyecto}', [ProyectoController::class, 'destroy'])->name('destroy');
        Route::get('/export', [ProyectoController::class, 'export'])->name('export');
});