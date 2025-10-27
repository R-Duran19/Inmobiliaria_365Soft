<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Moneda;


class MonedaController extends Controller
{
    public function getMonedas(Request $request)
    {
        $monedas = Moneda::orderBy('nombre', 'asc')->get();

        return response()->json([
            'monedas' => $monedas,
        ]);

    }

    public function postMoneda(Request $request)
    {
   
        $data = $request->validate([
            'nombre' => 'required|string|max:50', 
            'pais' => 'required|string|max:30',
            'abreviacion' => 'required|string|max:255',
            'tipo_cambio' => 'required|decimal:0,2',
            'activo' => 'required|boolean',
        ]);

 
        $moneda = Moneda::create($data);

        return response()->json([
            'success' => true,
            'moneda' => $moneda,
        ], 201);
    }
}
