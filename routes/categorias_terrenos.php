<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentoTerrenoController;

Route::middleware(['auth', 'verified'])->group(function () {
    // Listar documentos de un terreno específico
    Route::get('documentos_terreno/terreno/{id}', [DocumentoTerrenoController::class, 'index'])
        ->name('documentos_terreno.index');

    // Subir/crear un nuevo documento
    Route::post('documentos_terreno', [DocumentoTerrenoController::class, 'store'])
        ->name('documentos_terreno.store');

    // Descargar un documento específico (opcional, si quieres manejar descargas)
    Route::get('documentos_terreno/descargar/{id}', [DocumentoTerrenoController::class, 'descargar'])
        ->name('documentos_terreno.descargar');

    // Eliminar un documento
    Route::delete('documentos_terreno/{id}', [DocumentoTerrenoController::class, 'destroy'])
        ->name('documentos_terreno.destroy');

    // Exportar todos los documentos de un terreno (opcional)
    Route::get('documentos_terreno/exportar/{id}', [DocumentoTerrenoController::class, 'exportar'])
        ->name('documentos_terreno.exportar');
});
