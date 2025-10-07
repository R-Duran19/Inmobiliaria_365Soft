<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentoController;

Route::middleware(['auth', 'verified', 'role:admin'])
    ->get('documentos', [DocumentoController::class, 'index'])
    ->name('documentos');

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('documentos')
    ->name('documentos.')
    ->group(function () {
        Route::post('/upload', [DocumentoController::class, 'upload'])->name('upload');
        Route::get('/list', [DocumentoController::class, 'list'])->name('list');
        Route::delete('/{id}', [DocumentoController::class, 'destroy'])->name('destroy');
    });
