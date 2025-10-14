<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proyecto;
use Inertia\Inertia;

class ImportarMapaController extends Controller
{
    public function index(Request $request)
    {
        $proyectos = Proyecto::with(['barrios.cuadras.terrenos'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($proyecto) {
                $barrios = $proyecto->barrios;
                $cuadras = $barrios->flatMap->cuadras;
                $terrenos = $cuadras->flatMap->terrenos;

                return [
                    'id' => $proyecto->id,
                    'nombre' => $proyecto->nombre,
                    'ubicacion' => $proyecto->ubicacion,
                    'estado' => $proyecto->estado,
                    'estadisticas' => [
                        'barrios' => [
                            'total' => $barrios->count(),
                            'con_poligono' => $barrios->whereNotNull('poligono')->count(),
                        ],
                        'cuadras' => [
                            'total' => $cuadras->count(),
                            'con_poligono' => $cuadras->whereNotNull('poligono')->count(),
                        ],
                        'terrenos' => [
                            'total' => $terrenos->count(),
                            'con_poligono' => $terrenos->whereNotNull('poligono')->count(),
                        ],
                    ],
                ];
            });

        return Inertia::render('Importar', [ // 👈 Cambiado
            'proyectos' => $proyectos,
        ]);
    }


    public function mostrarImportador($proyectoId)
    {
        $proyecto = Proyecto::findOrFail($proyectoId);

        return Inertia::render('ImportarMapa/ImportWizard', [
            'proyecto' => $proyecto,
        ]);
    }
}
