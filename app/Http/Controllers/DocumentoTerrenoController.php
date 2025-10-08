<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\File;
use App\Models\DocumentoTerreno;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DocumentoTerrenoController extends Controller
{
    public function index($idt_terreno)
    {
        return DocumentoTerreno::where('idt_terreno', $idt_terreno)->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'archivos.*' => 'required|file|max:10240',
            'terreno_id' => 'required|exists:terrenos,id',
        ]);

        $paths = [];

        $directory = public_path('documentos_clientes/' . $request->terreno_id);

        
        if (!\File::exists($directory)) {
            \File::makeDirectory($directory, 0755, true);
        }

        foreach ($request->file('archivos') as $archivo) {
            $filename = time() . '_' . $archivo->getClientOriginalName();

            
            $archivo->move($directory, $filename);

            
            $doc = new DocumentoTerreno();
            $doc->idterreno = $request->terreno_id;
            $doc->nombre_documento = $filename;
            $doc->created_at = now();  
            $doc->updated_at = null;
            $doc->save();

            $paths[] = 'documentos_clientes/' . $request->terreno_id . '/' . $filename;
        }

        return response()->json([
            'message' => 'Archivos subidos correctamente',
            'paths' => $paths,
        ]);
    }
    public function destroy($id)
    { 
        $documento = DocumentoTerreno::find($id);
        if (!$documento) {
            return response()->json(['message' => 'Documento no encontrado.'], 404);
        }
        $ruta_archivo = public_path('documentos_clientes/' . $documento->idterreno . '/' . $documento->nombre_documento);
        if (File::exists($ruta_archivo)) {
            File::delete($ruta_archivo);
        } else {
            
            \Log::warning("Archivo físico no encontrado para el registro ID: {$id}");
        }
        $documento->delete();

        return response()->json(['message' => 'Documento y registro eliminados correctamente.'], 200);
    }
    public function listByTerreno($terrenoId)
    {
        $documentos = DocumentoTerreno::where('idterreno', $terrenoId)->get();
        return response()->json($documentos);
    }
}
