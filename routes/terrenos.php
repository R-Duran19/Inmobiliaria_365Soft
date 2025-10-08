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
        Route::get('/export/{idproyecto}', [TerrenoController::class, 'export'])->name('export');
        Route::post('/', [TerrenoController::class, 'store'])->name('store');
        Route::put('/{terreno}', [TerrenoController::class, 'update'])->name('update');
        Route::delete('/{terreno}', [TerrenoController::class, 'destroy'])->name('destroy');
        Route::patch('/{terreno}/condicion', [TerrenoController::class, 'setCondicion'])->name('condicion');
        

    });

Route::middleware(['auth', 'verified', 'role:admin'])
    ->get('api/terrenos', [TerrenoController::class, 'getTerrenos']);

Route::middleware(['auth', 'verified', 'role:admin'])
    ->get('api/terrenos/{terreno}', [TerrenoController::class, 'show'])
    ->name('terrenos.show');

// Route::get('api/terrenos', [TerrenoController::class, 'getTerrenos']);