<?php
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MonedaController;


// Route::prefix('monedas')
//     ->name('monedas.')
//     ->group(function () {
//         Route::get('/', [MonedaController::class, 'getMonedas'])->name('getMonedas');
        
//     });

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('monedas')
    ->name('monedas.')
    ->group(function () {
        Route::get('/', [MonedaController::class, 'getMonedas'])->name('getMonedas');
        Route::post('/', [MonedaController::class, 'postMoneda'])->name('postMoneda');
        // Route::put('/{terreno}', [TerrenoController::class, 'update'])->name('update');
        // Route::delete('/{terreno}', [TerrenoController::class, 'destroy'])->name('destroy');
        // Route::patch('/{terreno}/condicion', [TerrenoController::class, 'setCondicion'])->name('condicion');
        

    });

    