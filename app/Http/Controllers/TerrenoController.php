<?php

namespace App\Http\Controllers;
use App\Models\Terreno;
use App\Models\Proyecto;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TerrenoController extends Controller
{
    
   public function index(Request $request)
{
    $query = Terreno::with('proyecto'); // 👈 aquí ya trae el proyecto relacionado

    if ($request->has('ubicacion')) {
        $query->where('ubicacion', 'like', '%' . $request->ubicacion . '%');
    }

    if ($request->has('idproyecto')) {
        $query->where('idproyecto', $request->idproyecto);
    }

    $terrenos = $query->get();

    return Inertia::render('Terrenos', [
        'terrenos' => $terrenos,
    ]);

}



    
    public function store(Request $request)
    {
        $terreno = Terreno::create($request->all());
        $terreno->load('proyecto');
        return response()->json($terreno, 201);
    }

    
    public function update(Request $request, $id)
    {
        $terreno = Terreno::findOrFail($id);
        $terreno->update($request->all());
        $terreno->load('proyecto');
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

    public function getTerrenos(Request $request)
    {
        $query = Terreno::with('proyecto'); // trae la relación con proyecto

        if ($request->has('ubicacion')) {
            $query->where('ubicacion', 'like', '%' . $request->ubicacion . '%');
        }

        if ($request->has('idproyecto')) {
            $query->where('idproyecto', $request->idproyecto);
        }

        $terrenos = $query->get();

        return response()->json($terrenos);
    }

}