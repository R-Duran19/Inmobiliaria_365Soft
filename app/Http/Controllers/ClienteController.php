<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;

class ClienteController extends Controller
{
    public function buscarCliente($ci)
    {
 
        $cliente = Cliente::where('ci', $ci)->first();

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'cliente' => $cliente,
        ]);
    }

    public function postCliente(Request $request)
    {
 
       

        $data = $request->validate([
            'nombre'              => 'required|string|max:30',
            'apellido_materno'    => 'nullable|string|max:30',
            'apellido_paterno'    => 'required|string|max:30',
            'telefono'            => 'nullable|string|max:20',
            'telefono_referencia' => 'nullable|string|max:20',
            'direccion'           => 'nullable|string|max:150',
            'ci'                  => 'required|string|max:20',
            'codigo_carnet'       => 'required|string|max:20',
        ]);


 
        $cliente = Cliente::create($data);

        return response()->json([
            'success' => true,
            'cliente' => $cliente,
        ], 201);
    }

    


}
