<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TerrenoController extends Controller
{
    
    public function index(Request $request)
    {
        $query = Terreno::query();

        if ($request->has('ubicacion')) {
            $query->where('ubicacion', 'like', '%' . $request->ubicacion . '%');
        }

        if ($request->has('idproyecto')) {
            $query->where('idproyecto', $request->idproyecto);
        }

        return response()->json($query->get());
    }

    
    public function store(Request $request)
    {
        $terreno = Terreno::create($request->all());
        return response()->json($terreno, 201);
    }

    
    public function update(Request $request, $id)
    {
        $terreno = Terreno::findOrFail($id);
        $terreno->update($request->all());
        return response()->json($terreno);
    }

    
    public function destroy($id)
    {
        $terreno = Terreno::findOrFail($id);
        $terreno->delete();
        return response()->json(null, 204);
    }

    public function setCondicion($id)
    {
        $terreno = Terreno::findOrFail($id);
        $terreno->condicion = !$terreno->condicion; 
        $terreno->save();

        return response()->json([
            'id' => $terreno->id,
            'condicion' => $terreno->condicion,
            'message' => 'Condición actualizada correctamente'
        ]);
    }

    
    public function export(Request $request)
    {
        $format = $request->get('format', 'excel');

        if ($format === 'pdf') {
            $terrenos = Terreno::all();
            $pdf = Pdf::loadView('exports.terrenos', compact('terrenos'));
            return $pdf->download('terrenos.pdf');
        }

        return Excel::download(new TerrenosExport, 'terrenos.xlsx');
    }
}