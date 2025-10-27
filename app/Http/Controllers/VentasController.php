<?php

namespace App\Http\Controllers;

use App\Models\Terreno;
use Inertia\Inertia;

class VentasController extends Controller
{
    public function show($id)
    {        
        $terreno = Terreno::select('id', 'idproyecto', 'cuota_inicial', 'cuota_mensual', 'precio_venta', 'ubicacion')
            ->with([
                'proyecto:id,nombre', 
            ])
            ->where('id', $id)
            ->firstOrFail();

        
        return Inertia::render('Ventas', [
            'terreno' => $terreno
        ]);
    }
}
