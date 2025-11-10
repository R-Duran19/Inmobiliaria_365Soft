<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProyectoController;

Route::middleware(['auth', 'verified', 'role:admin'])
    ->get('proyectos', [ProyectoController::class, 'index'])
    ->name('proyectos');

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('proyectos')
    ->name('proyectos.')
    ->group(function () {
        Route::post('/', [ProyectoController::class, 'store'])->name('store');
        Route::post('/importar', [ProyectoController::class, 'importar'])->name('importar');
        Route::get('/exportar', [ProyectoController::class, 'exportar'])->name('proyectos.exportar');
        Route::get('/plantilla', [ProyectoController::class, 'descargarPlantilla'])->name('proyectos.plantilla');
        Route::match(['put', 'post'], '/{proyecto}', [ProyectoController::class, 'update'])->name('update');
        Route::delete('/{proyecto}', [ProyectoController::class, 'destroy'])->name('destroy');
        Route::patch('/{proyecto}/desactivar', [ProyectoController::class, 'desactivar'])->name('desactivar');
        Route::patch('/{proyecto}/activar', [ProyectoController::class, 'activar'])->name('activar');
        Route::get('/ultimoId', [ProyectoController::class, 'getUltimoId']);
        Route::post('/poligono/{idproyecto}', [ProyectoController::class, 'postPoligono']);
        Route::get('/poligono/{idproyecto}', [ProyectoController::class, 'getPoligono']);
});

Route::middleware(['auth', 'verified', 'role:admin'])
    ->get('/api/proyectos', [ProyectoController::class, 'apiIndex']);


    // Route::get('proyectos/ultimoId', [ProyectoController::class, 'getUltimoId']);