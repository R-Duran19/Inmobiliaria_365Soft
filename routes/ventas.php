<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VentasController;
use Inertia\Inertia;

Route::middleware(['auth', 'verified', 'role:admin'])
    ->get('ventas', [VentasController::class, 'index'])
    ->name('ventas');

