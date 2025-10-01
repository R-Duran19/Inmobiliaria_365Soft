<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
            'estado' => 'required|boolean',
        ]);

        Proyecto::create($request->all());

        return redirect()->route('proyectos')->with('success', 'Proyecto creado correctamente.');
    }

    public function update(Request $request, Proyecto $proyecto)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'estado' => 'required|boolean',
        ]);

        $proyecto->update($request->all());

        return back()->with('success', 'Proyecto actualizado correctamente');
    }

    public function destroy(Proyecto $proyecto)
    {
        $proyecto->delete();

        return response()->json([
            'success' => true,
            'message' => 'Proyecto eliminado correctamente',
        ]);
    }

    public function export()
    {
        // Implementar lógica de exportación
        return response()->json(['message' => 'Funcionalidad de exportación en desarrollo.']);
    }
}
