<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClienteController;
use Inertia\Inertia;

Route::middleware([])
    ->prefix('clientes')
    ->name('clientes.')
    ->group(function () {
        Route::get('/{ci}', [ClienteController::class, 'buscarCliente'])->name('clientes.buscarCliente');    
        Route::post('/', [ClienteController::class, 'postCliente'])->name('clientes.postCliente');   
    });