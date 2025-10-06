<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TerrenoController;

Route::middleware(['auth', 'verified', 'role:admin'])
    ->get('terrenos', [TerrenoController::class, 'index'])
    ->name('terrenos');

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('terrenos')
    ->name('terrenos.')
    ->group(function () {
        Route::post('/', [TerrenoController::class, 'store'])->name('store');
        Route::put('/{terreno}', [TerrenoController::class, 'update'])->name('update');
        Route::delete('/{terreno}', [TerrenoController::class, 'destroy'])->name('destroy');
        Route::patch('/{terreno}/condicion', [TerrenoController::class, 'setCondicion'])->name('condicion');
        Route::get('/export', [TerrenoController::class, 'export'])->name('export');
    });

Route::middleware(['auth', 'verified', 'role:admin'])
    ->get('api/terrenos', [TerrenoController::class, 'getTerrenos']);
