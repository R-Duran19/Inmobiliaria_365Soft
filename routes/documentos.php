<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentoTerrenoController;

Route::middleware(['auth', 'verified', 'role:admin'])
    ->get('documentos', [DocumentoTerrenoController::class, 'index'])
    ->name('documentos');

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('documentos')
    ->name('documentos.')
    ->group(function () {
        Route::post('/upload', [DocumentoTerrenoController::class, 'store'])->name('upload');
        Route::get('/list/{terrenoId}', [DocumentoTerrenoController::class, 'listByTerreno'])->name('list.byTerreno');
        Route::delete('/{id}', [DocumentoTerrenoController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['auth', 'verified', 'role:admin'])
    ->get('documentos/{id}', function ($id) {
        return inertia('Documentos/Documentos', [
            'terrenoId' => $id,
        ]);
    })->name('documentos.terreno');

Route::middleware(['auth', 'verified', 'role:admin'])
    ->get('documentos/visualizar/{terrenoId}', [DocumentoTerrenoController::class, 'visualizar'])
    ->name('documentos.visualizar');