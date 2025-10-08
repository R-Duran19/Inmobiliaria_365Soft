<?php

namespace App\Http\Controllers;
use App\Models\Terreno;
use App\Models\Proyecto;
use Illuminate\Http\Request;
use Inertia\Inertia;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Exports\TerrenosExport;

class TerrenoController extends Controller
{
    
    public function index(Request $request)
    {
        $query = Terreno::select('id', 'idproyecto', 'idcategoria', 'ubicacion', 'superficie', 'cuota_inicial', 'cuota_mensual', 'precio_venta', 'estado', 'condicion') 
        ->with([
            'proyecto:id,nombre',           
            'categorias_terrenos:id,nombre' 
        ]);

        if ($request->has('ubicacion')) {
            $query->where('ubicacion', 'like', '%' . $request->ubicacion . '%');
        }

        if ($request->has('idproyecto')) {
            $query->where('idproyecto', $request->idproyecto);
        }

        if ($request->has('idcategoria')) {
            $query->where('idcategoria', $request->idcategoria);
        }

        $terrenos = $query->get();

    return Inertia::render('Terrenos', [
        'terrenos' => $terrenos,
    ]);
    // return response()->json([
    //     'terrenos' => $terrenos,
    // ]);

}



    
    public function store(Request $request)
    {
   
        $data = $request->validate([
            'idproyecto' => 'required|exists:proyectos,id',
            'idcategoria' => 'required|exists:categorias_terrenos,id',
            'ubicacion' => 'required|string|max:255',
            'superficie' => 'required|string|max:255',
            'cuota_inicial' => 'required|numeric',
            'cuota_mensual' => 'required|numeric',
            'precio_venta' => 'required|numeric',
            'estado' => 'nullable|integer',
            'condicion' => 'nullable|boolean',
            'poligono_geojson' => 'nullable|array',
        ]);

        // Convertir GeoJSON a Polygon si viene
        if (isset($data['poligono_geojson'])) {
            $coordinates = $data['poligono_geojson']['coordinates'][0];
            $points = array_map(fn($coord) => new Point($coord[1], $coord[0]), $coordinates);
            $data['poligono'] = new Polygon([new LineString($points)]);
            unset($data['poligono_geojson']);
        }

        // Crear el terreno
        $terreno = Terreno::create($data);

        // Cargar relaciones
        $terreno->load(['proyecto', 'categorias_terrenos']);

        return response()->json([
            'success' => true,
            'terreno' => $terreno,
        ], 201);
    }

    
    public function update(Request $request, $id)
    {
        $terreno = Terreno::findOrFail($id);

        $data = $request->validate([
            'idproyecto' => 'required|exists:proyectos,id',
            'idcategoria' => 'required|exists:categorias_terrenos,id',
            'ubicacion' => 'required|string|max:255',
            'superficie' => 'required|string|max:255',
            'cuota_inicial' => 'required|numeric',
            'cuota_mensual' => 'required|numeric',
            'precio_venta' => 'required|numeric',
            'estado' => 'nullable|integer',
            'condicion' => 'nullable|boolean',
        ]);

        if (isset($data['poligono_geojson'])) {
            $coordinates = $data['poligono_geojson']['coordinates'][0];
            $points = array_map(fn($coord) => new Point($coord[1], $coord[0]), $coordinates);
            $data['poligono'] = new Polygon([new LineString($points)]);
            unset($data['poligono_geojson']);
        }

        $terreno->update($data);

        $terreno->load(['proyecto', 'categorias_terrenos']);

        return response()->json([
            'success' => true,
            'terreno' => $terreno
        ]);
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

    
    public function export($idproyecto)
    {
        $terrenos = \App\Models\Terreno::where('idproyecto', $idproyecto)
            ->with('proyecto')
            ->get();

        if ($terrenos->isEmpty()) {
            return response()->json(['message' => 'No hay terrenos para exportar.'], 404);
        }

        $export = new \App\Http\Exports\TerrenosExport($terrenos);
        $spreadsheet = $export->export();

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'terrenos_exportados_' . date('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }






    public function getTerrenos(Request $request)
    {
        
         $query = Terreno::select('id', 'idproyecto', 'idcategoria', 'ubicacion', 'superficie', 'cuota_inicial', 'cuota_mensual', 'precio_venta', 'estado', 'condicion') 
        ->with([
        'proyecto:id,nombre',            
        'categorias_terrenos:id,nombre'  
    ]);

        
        if ($request->has('ubicacion') && $request->ubicacion) {
            $query->where('ubicacion', 'like', '%' . $request->ubicacion . '%');
        }

        
        if ($request->has('idcategoria') && $request->idcategoria) {
            $query->where('idcategoria', $request->idcategoria);
        }

        
        $terrenos = $query->get();

        
        return response()->json([
            'success' => true,
            'terrenos' => $terrenos,
        ]);
    }


}