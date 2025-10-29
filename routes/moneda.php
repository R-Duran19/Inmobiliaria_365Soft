<?php
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MonedaController;


// Route::prefix('monedas')
//     ->name('monedas.')
//     ->group(function () {
//         Route::patch('/{moneda}/activo', [MonedaController::class, 'setActivo'])->name('setActivo');
        
//     });

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('monedas')
    ->name('monedas.')
    ->group(function () {
        Route::get('/', [MonedaController::class, 'getMonedas'])->name('getMonedas');
        Route::post('/', [MonedaController::class, 'postMoneda'])->name('postMoneda');
        Route::put('/{moneda}', [MonedaController::class, 'putMoneda'])->name('putMoneda');
        Route::delete('/{moneda}', [MonedaController::class, 'deleteMoneda'])->name('deleteMoneda');
        Route::patch('/{moneda}/activo', [MonedaController::class, 'setActivo'])->name('setActivo');
        

    });


    

    