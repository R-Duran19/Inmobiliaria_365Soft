<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VentasController;
use Inertia\Inertia;

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('ventas')
    ->name('ventas')
    ->group(function () {
        Route::post('/', [VentasController::class, 'postVenta']);
    });

