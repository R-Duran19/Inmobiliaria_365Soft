<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MapaController;
use Inertia\Inertia;

// Vista del mapa de un proyecto específico
Route::middleware(['auth', 'verified'])
    ->get('mapa/{proyecto}', function ($proyectoId) {
        return Inertia::render('mapas/MapaProyecto', [
            'proyectoId' => $proyectoId
        ]);
    })
    ->name('mapa.proyecto');

// API para obtener datos del mapa
Route::middleware(['auth', 'verified'])
    ->prefix('api/mapa')
    ->group(function () {
        Route::get('proyectos/{proyectoId}', [MapaController::class, 'getProyecto']);
        Route::get('proyectos/{proyectoId}/barrios', [MapaController::class, 'getBarriosGeoJSON']);
        Route::get('proyectos/{proyectoId}/cuadras', [MapaController::class, 'getCuadrasGeoJSON']);
        Route::get('proyectos/{proyectoId}/terrenos', [MapaController::class, 'getTerrenosGeoJSON']);
        Route::get('proyectos/{proyectoId}/categorias', [MapaController::class, 'getCategorias']);
    });