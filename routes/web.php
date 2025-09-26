<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('auth/Login');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::get('proyectos', function () {
    return Inertia::render('Proyectos');
})->middleware(['auth', 'verified'])->name('proyectos');

Route::get('terrenos', function () {
    return Inertia::render('Terrenos');
})->middleware(['auth', 'verified'])->name('terrenos');

Route::get('categorias', function () {
    return Inertia::render('Categorias');
})->middleware(['auth', 'verified'])->name('categorias');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
