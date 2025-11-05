<?php

use App\Http\Controllers\PolygonEditorController;
use Illuminate\Support\Facades\Route;

// Ruta principal del editor de mapas
Route::middleware(['auth', 'verified', 'role:admin'])
    ->get('mapa-editor', [PolygonEditorController::class, 'index'])
    ->name('mapa-editor');


Route::middleware(['auth', 'verified', 'role:admin'])
    ->get('mapa-editor/{proyecto}', [PolygonEditorController::class, 'irIndex'])
    ->name('mapa-editor.proyecto');


// Rutas API del editor de polígonos
Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('polygon-editor')
    ->name('polygon-editor.')
    ->group(function () {
        Route::get('/initial-data', [PolygonEditorController::class, 'getInitialData'])
            ->name('initial-data');
        
        Route::get('/barrios/{idProyecto}', [PolygonEditorController::class, 'getBarriosByProyecto'])
            ->name('barrios');
        
        Route::get('/cuadras/{idBarrio}', [PolygonEditorController::class, 'getCuadrasByBarrio'])
            ->name('cuadras');
        
        Route::post('/save', [PolygonEditorController::class, 'savePolygons'])
            ->name('save');

        Route::get('/poligonos/{proyecto}', [PolygonEditorController::class, 'getPoligonos']);

    });