<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MapaController;
use App\Http\Controllers\VentasController;
use Inertia\Inertia;


Route::middleware(['auth', 'verified'])->group(function () {
    
    Route::get('mapa/{proyecto}', function ($proyectoId) {
        return Inertia::render('mapas/MapaProyecto', [
            'proyectoId' => $proyectoId
        ]);
    })->name('mapa.proyecto');

    
    Route::get('ventas/{terreno}', [VentasController::class, 'show'])->name('ventas.show');
});


Route::middleware(['auth', 'verified'])
    ->prefix('api/mapa')
    ->group(function () {
        Route::get('proyectos/{proyectoId}', [MapaController::class, 'getProyecto']);
        Route::get('proyectos/{proyectoId}/barrios', [MapaController::class, 'getBarriosGeoJSON']);
        Route::get('proyectos/{proyectoId}/cuadras', [MapaController::class, 'getCuadrasGeoJSON']);
        Route::get('proyectos/{proyectoId}/terrenos', [MapaController::class, 'getTerrenosGeoJSON']);
        Route::get('proyectos/{proyectoId}/categorias', [MapaController::class, 'getCategorias']);
    });