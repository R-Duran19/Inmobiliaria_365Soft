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
}
