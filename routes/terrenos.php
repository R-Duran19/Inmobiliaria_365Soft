<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TerrenoController;
use Inertia\Inertia;

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




Route::middleware(['auth', 'verified', 'role:admin'])
    ->get('proyectos/{idproyecto}/terrenos', function ($idproyecto) {
        return Inertia::render('Terrenos/TerrenosPorProyecto', [
            'proyectoId' => $idproyecto,
        ]);
    })->name('terrenos.getTerrenosPorProyecto');


Route::middleware(['auth', 'verified', 'role:admin'])
    ->get('api/proyectos/{idproyecto}/terrenos', [TerrenoController::class, 'getTerrenosPorProyecto'])
    ->name('api.terrenos.getTerrenosPorProyecto');

// Route::get('api/terrenos', [TerrenoController::class, 'getTerrenos']);