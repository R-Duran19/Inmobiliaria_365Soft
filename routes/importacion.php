<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImportarMapaController;
use App\Http\Controllers\ImportJSONController;
use Inertia\Inertia;

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {

    // 📂 Páginas principales
    Route::get('/importarmapa', [ImportarMapaController::class, 'index'])
        ->name('importarmapa');

    Route::get('/importarmapa/{proyecto}', [ImportarMapaController::class, 'mostrarImportador'])
        ->name('importarmapa.importar');

    // 🧙‍♂️ Wizard de importación (nueva página)
    Route::get('/importar/wizard', function () {
        return Inertia::render('ImportarMapa/ImportWizard');
    })->name('importar.wizard');

    // 🔍 API - Análisis de GeoJSON
    Route::post('/api/import/analyze', [ImportJSONController::class, 'analyze'])
        ->name('api.import.analyze');

    // 📋 API - Obtener entidades disponibles para mapeo
    Route::post('/api/import/entities', [ImportJSONController::class, 'getAvailableEntities'])
        ->name('api.import.entities');

    // ⚠️ API - Detectar conflictos
    Route::post('/api/import/conflicts', [ImportJSONController::class, 'detectConflicts'])
        ->name('api.import.conflicts');

    // ✅ API - Ejecutar importación
    Route::post('/api/import/execute', [ImportJSONController::class, 'import'])
        ->name('api.import.execute');

    // 🗑️ Ruta legacy (mantener por compatibilidad)
    Route::post('/import/geojson', [ImportJSONController::class, 'import'])
        ->name('import.geojson');
});