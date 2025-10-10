<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Barrio;

class BarrioController extends Controller
{
    public function postBarrios(Request $request)
    {
        $request->validate([
            'idproyecto' => 'required|integer|exists:proyectos,id',
            'barrios' => 'required|array|min:1',
            'barrios.*' => 'string|max:255',
        ]);

        $ids = [];
        foreach ($request->barrios as $nombreBarrio) {
            $barrio = Barrio::create([
                'idproyecto' => $request->idproyecto,
                'nombre' => $nombreBarrio,
            ]);
            $ids[] = $barrio->id;
        }

        return response()->json([
            'success' => true,
            'message' => 'Barrios registrados correctamente',
            'barrios' => $ids, 
        ]);
    }


    public function getUltimoIdBarrio(Request $request)
    {

        $barrio = Barrio::orderBy('id', 'desc')->first();

        return response()->json([
            'success' => true,
            'UltimoBarrio' => $barrio ? $barrio->id : 0,
        ]);

    }

    public function getBarriosPorProyecto($idproyecto)
    {
        $barrios = Barrio::where('idproyecto', $idproyecto)
            ->select('id', 'nombre')
            ->get();

        return response()->json([
            'success' => true,
            'barrios' => $barrios,
        ]);
    }
}
