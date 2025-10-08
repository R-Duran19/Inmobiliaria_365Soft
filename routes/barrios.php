<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BarrioController;


Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('barrios')
    ->name('barrios.')
    ->group(function () {
        Route::post('postBarrios', [BarrioController::class, 'postBarrios']);
        Route::get('getUltimoId', [BarrioController::class, 'getUltimoIdBarrio']);
    });