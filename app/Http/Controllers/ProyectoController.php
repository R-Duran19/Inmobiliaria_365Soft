<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\File;

class ProyectoController extends Controller
{
    public function index(Request $request)
    {
        $proyectos = Proyecto::query()
            ->when($request->search, function ($query, $search) {
                $query->where('nombre', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Proyectos', [
            'proyectos' => $proyectos,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_lanzamiento' => 'nullable|date',
            'numero_lotes' => 'nullable|integer',
            'ubicacion' => 'nullable|string',
            'fotografia' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->all();

        if ($request->hasFile('fotografia')) {

            $directory = public_path('fotos_proyectos');
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            $file = $request->file('fotografia');
            $filename = time() . '_' . $file->getClientOriginalName();
            
            $file->move($directory, $filename);
            
            $data['fotografia'] = 'fotos_proyectos/' . $filename;
        }

        Proyecto::create($data);
        return redirect()->route('proyectos')->with('success', 'Proyecto creado correctamente.');
    }

    public function update(Request $request, Proyecto $proyecto)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_lanzamiento' => 'nullable|date',
            'numero_lotes' => 'nullable|integer',
            'ubicacion' => 'nullable|string',
            'fotografia' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->only(['nombre', 'descripcion', 'fecha_lanzamiento', 'numero_lotes', 'ubicacion']);

        if ($request->hasFile('fotografia')) {
            if ($proyecto->fotografia) {
                $oldFile = public_path($proyecto->fotografia);
                if (File::exists($oldFile)) {
                    File::delete($oldFile);
                }
            }

            $directory = public_path('fotos_proyectos');
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            $file = $request->file('fotografia');
            $filename = time() . '_' . $file->getClientOriginalName();
            
            $file->move($directory, $filename);
            
            $data['fotografia'] = 'fotos_proyectos/' . $filename;
        } else {
            unset($data['fotografia']);
        }

        $proyecto->update($data);
        return redirect()->back()->with('success', 'Proyecto actualizado correctamente');
    }

    public function destroy(Proyecto $proyecto)
    {
        if ($proyecto->fotografia) {
            $file = public_path($proyecto->fotografia);
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        $proyecto->delete();

        return response()->json([
            'success' => true,
            'message' => 'Proyecto eliminado correctamente',
        ]);
    }

    public function desactivar(Proyecto $proyecto)
    {
        $proyecto->estado = false;
        $proyecto->save();

        return response()->json([
            'success' => true,
            'message' => 'Proyecto desactivado correctamente',
            'data' => $proyecto
        ]);
    }

    public function activar(Proyecto $proyecto)
    {
        $proyecto->estado = true;
        $proyecto->save();

        return response()->json([
            'success' => true,
            'message' => 'Proyecto activado correctamente',
            'data' => $proyecto
        ]);
    }

    public function export()
    {
        // Implementar lógica de exportación
        return response()->json(['message' => 'Funcionalidad de exportación en desarrollo.']);
    }
}