<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('auth/Login');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Rutas protegidas por rol de administrador
Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {

    //VISTAS INERTIA
    Route::get('proyectos', fn () => Inertia::render('Proyectos'))->name('proyectos');
    Route::get('terrenos', fn () => Inertia::render('Terrenos'))->name('terrenos');
    Route::get('categorias', fn () => Inertia::render('Categorias'))->name('categorias');

    //FUNCIONES CONTROLADORES
        
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/categorias_terrenos.php';
require __DIR__.'/accesos.php';
