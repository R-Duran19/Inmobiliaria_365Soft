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
            'pais' => 'required|string|max:100',
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

    public function deleteMoneda($id)
    {
        $moneda = Moneda::findOrFail($id);
        $moneda->delete();
        return response()->json(null, 204);
    }

    public function putMoneda(Request $request, $id)
    {
        $moneda = Moneda::findOrFail($id);

        $data = $request->validate([
            'nombre' => 'required|string|max:50', 
            'pais' => 'required|string|max:100',
            'abreviacion' => 'required|string|max:255',
            'tipo_cambio' => 'required|decimal:0,2',
            'activo' => 'required|boolean',
        ]);


        $moneda->update($data);


        return response()->json([
            'success' => true,
            'moneda' => $moneda
        ]);
    }

    public function setActivo($id)
    {
        $moneda = Moneda::findOrFail($id);
        $moneda->activo = !$moneda->activo; 
        $moneda->save();

        return response()->json([
            'message' => 'Moneda actualizada correctamente'
        ]);
    }
}
