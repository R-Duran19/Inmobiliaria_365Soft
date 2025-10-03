<?php
namespace App\Http\Controllers;

use App\Models\CategoriaTerreno;
use App\Models\Proyecto;
use Illuminate\Http\Request;

class CategoriaTerrenoController extends Controller
{
    public function index()
    {
        // Obtener todas las categorías con su proyecto relacionado
        $categorias = CategoriaTerreno::with('proyecto')->get();
        return response()->json([
            'success' => true,
            'data' => $categorias
        ]);
    }

    public function store(Request $request)
    {
        // Validación de los datos
        $request->validate([
            'nombre' => 'required|string|max:255',
            'idproyecto' => 'required|exists:proyectos,id',
            'estado' => 'sometimes|boolean',
        ]);

        // Crear la nueva categoría
        $categoria = CategoriaTerreno::create([
            'nombre' => $request->nombre,
            'idproyecto' => $request->idproyecto,
            'estado' => $request->estado ?? true,
        ]);

        // Retornar respuesta JSON
        return response()->json([
            'success' => true,
            'message' => 'Categoría creada correctamente',
            'data' => $categoria
        ]);
    }

    public function update(Request $request, $id)
    {
        // Buscar la categoría por id
        $categoria = CategoriaTerreno::findOrFail($id);

        // Validación de los datos
        $request->validate([
            'nombre' => 'required|string|max:255',
            'idproyecto' => 'required|exists:proyectos,id',
            'estado' => 'sometimes|boolean',
        ]);

        // Actualizar los campos
        $categoria->update([
            'nombre' => $request->nombre,
            'idproyecto' => $request->idproyecto,
            'estado' => $request->estado ?? $categoria->estado,
        ]);

        // Retornar respuesta JSON
        return response()->json([
            'success' => true,
            'message' => 'Categoría actualizada correctamente',
            'data' => $categoria
        ]);
    }

    public function destroy($id)
    {
        $categoria = CategoriaTerreno::findOrFail($id);
        $categoria->delete();
        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada correctamente',
        ]);
    }

    public function desactivar($id)
    {
        // Buscar la categoría por id
        $categoria = CategoriaTerreno::findOrFail($id);
        // Cambiar estado a false
        $categoria->estado = false;
        $categoria->save();
        // Retornar respuesta JSON
        return response()->json([
            'success' => true,
            'message' => 'Categoría desactivada correctamente',
            'data' => $categoria
        ]);
    }

    public function activar($id)
    {
        // Buscar la categoría por id
        $categoria = CategoriaTerreno::findOrFail($id);

        // Cambiar estado a true
        $categoria->estado = true;
        $categoria->save();

        // Retornar respuesta JSON
        return response()->json([
            'success' => true,
            'message' => 'Categoría activada correctamente',
            'data' => $categoria
        ]);
    }

    public function proyectos()
    {
        $proyectos = Proyecto::where('estado', 1)->get(['id', 'nombre'])->toArray();
        return response()->json([
            'success' => true,
            'data' => $proyectos
        ]);
    }
}
