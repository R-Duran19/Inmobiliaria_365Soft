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
        // Obtener información del proyecto con contadores
        Route::get('proyectos/{proyectoId}', [MapaController::class, 'getProyecto']);
        
        // Obtener terrenos de un proyecto en formato GeoJSON
        Route::get('proyectos/{proyectoId}/terrenos', [MapaController::class, 'getTerrenosGeoJSON']);
        
        // Obtener categorías de un proyecto
        Route::get('proyectos/{proyectoId}/categorias', [MapaController::class, 'getCategorias']);
    });