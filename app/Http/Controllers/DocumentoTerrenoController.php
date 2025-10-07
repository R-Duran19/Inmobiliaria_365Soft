<?php

namespace App\Http\Controllers;

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

        // Crear directorio si no existe
        if (!\File::exists($directory)) {
            \File::makeDirectory($directory, 0755, true);
        }

        foreach ($request->file('archivos') as $archivo) {
            $filename = time() . '_' . $archivo->getClientOriginalName();

            // Mover archivo al directorio
            $archivo->move($directory, $filename);

            // Guardar registro en la base de datos
            $doc = new DocumentoTerreno();
            $doc->idterreno = $request->terreno_id;
            $doc->nombre_documento = $filename;
            $doc->created_at = now();  // Fecha actual
            $doc->updated_at = null;
            $doc->save();

            $paths[] = 'documentos_clientes/' . $request->terreno_id . '/' . $filename;
        }

        return response()->json([
            'message' => 'Archivos subidos correctamente',
            'paths' => $paths,
        ]);
    }
    public function listByTerreno($terrenoId)
    {
        $documentos = DocumentoTerreno::where('idterreno', $terrenoId)->get();
        return response()->json($documentos);
    }
}
