<?php
namespace App\Http\Controllers;

use App\Models\Cuadra;
use Illuminate\Http\Request;

class CuadraController extends Controller
{
    /**
     * Obtiene todas las cuadras asociadas a un barrio.
     */
    public function getCuadrasPorBarrio($idbarrio)
    {
        $cuadras = Cuadra::where('idbarrio', $idbarrio)
            ->select('id', 'nombre')
            ->get();

        return response()->json([
            'success' => true,
            'cuadras' => $cuadras,
        ]);
    }

    /**
     * Obtiene todas las cuadras (opcional, si lo necesitas en otro lugar).
     */
    public function getAllCuadras()
    {
        $cuadras = Cuadra::select('id', 'idbarrio', 'nombre')
            ->with('barrio')
            ->get();

        return response()->json([
            'success' => true,
            'cuadras' => $cuadras,
        ]);
    }

    public function postCuadras(Request $request)
    {
        $request->validate([
            '*.idbarrio' => 'required|integer|exists:barrios,id',
            '*.cuadras' => 'required|array|min:1',
            '*.cuadras.*' => 'string|max:255',
        ]);

        $data = [];

        foreach ($request->all() as $item) {
            foreach ($item['cuadras'] as $nombreCuadra) {
                $data[] = [
                    'idbarrio' => $item['idbarrio'],
                    'nombre' => $nombreCuadra,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        Cuadra::insert($data);

        return response()->json([
            'success' => true,
            'message' => 'Cuadras registradas correctamente',
            'cuadras' => $data,
        ]);
    }




    public function getUltNombreCuadra(Request $request)
    {
        $nombre = Cuadra::orderBy('nombre', 'desc')->first();

        return response()->json([
            'success' => true,
            'nombre' => $nombre ? $nombre->nombre : '1',
        ]);
    }

}
