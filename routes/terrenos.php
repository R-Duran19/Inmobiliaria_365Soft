<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;



Route::get('/terrenos', [TerrenoController::class, 'index'])->name('terrenos.index');
Route::post('/terrenos', [TerrenoController::class, 'store'])->name('terrenos.store');
Route::put('/terrenos/{id}', [TerrenoController::class, 'update'])->name('terrenos.update');
Route::delete('/terrenos/{id}', [TerrenoController::class, 'destroy'])->name('terrenos.destroy');
Route::patch('/terrenos/{id}/condicion', [TerrenoController::class, 'setCondicion']);



Route::get('/terrenos/export', [TerrenoController::class, 'export'])->name('terrenos.export');
