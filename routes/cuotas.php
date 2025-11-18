<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CuotasController;

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('cuotas')
    ->name('cuotas.')
    ->group(function () {
        Route::post('/', [CuotasController::class, 'postCuotas'])->name('postCuotas');
    });
