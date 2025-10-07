<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentoController extends Controller
{
    public function index()
    {
        // Retorna la vista que renderiza Vue
        return inertia('Documentos/Documentos', [
            // ...
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'archivos.*' => 'required|file|max:10240', // 10 MB máximo por archivo
        ]);

        $paths = [];
        foreach ($request->file('archivos') as $archivo) {
            $paths[] = $archivo->store('documentos', 'public');
        }

        return response()->json([
            'message' => 'Archivos subidos correctamente',
            'paths' => $paths,
        ]);
    }

    public function list()
    {
        $files = Storage::disk('public')->files('documentos');
        return response()->json($files);
    }

    public function destroy($id)
    {
        $path = "documentos/$id";
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            return response()->json(['message' => 'Archivo eliminado']);
        }

        return response()->json(['error' => 'Archivo no encontrado'], 404);
    }
}
