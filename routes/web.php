<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('auth/Login');
})->name('home');
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('mapa/{proyecto}', function ($proyectoId) {
        return Inertia::render('mapas/MapaProyecto', [
            'proyectoId' => $proyectoId
        ]);
    })->name('mapa.proyecto');
});
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('api/proyectos-list', [App\Http\Controllers\MapaController::class, 'getProyectos']);
});
Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    //VISTAS INERTIA
    Route::get('terrenos', fn() => Inertia::render('Terrenos'))->name('terrenos');
    Route::get('categorias', fn() => Inertia::render('Categorias'))->name('categorias');
    
});

// Archivos de rutas adicionales
require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/categorias_terrenos.php';
require __DIR__ . '/accesos.php';
require __DIR__ . '/proyectos.php';
require __DIR__ . '/terrenos.php';
require __DIR__ . '/mapas.php';
require __DIR__.'/documentos.php';
