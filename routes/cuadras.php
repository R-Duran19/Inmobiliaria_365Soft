<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CuadraController;

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('cuadras')
    ->name('cuadras.')
    ->group(function () {
        Route::get('barrio/{idbarrio}', [CuadraController::class, 'getCuadrasPorBarrio'])->name('barrio');
        Route::get('all', [CuadraController::class, 'getAllCuadras'])->name('all');
    });
