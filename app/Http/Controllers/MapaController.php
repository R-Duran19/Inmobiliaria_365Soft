<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\Terreno;
use App\Models\Cuadra;
use App\Models\Barrio;
use App\Models\CategoriaTerreno;
use Illuminate\Http\Request;

class MapaController extends Controller
{
    public function getProyectos()
    {
        $proyectos = Proyecto::where('estado', true)
            ->withCount([
                'terrenos as total_terrenos' => function ($query) {
                    $query->where('condicion', true);
                },
                'terrenos as terrenos_disponibles' => function ($query) {
                    $query->where('estado', 0)->where('condicion', true);
                },
                'terrenos as terrenos_vendidos' => function ($query) {
                    $query->where('estado', 1);
                },
                'terrenos as terrenos_reservados' => function ($query) {
                    $query->where('estado', 2);
                }
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($proyecto) {
                return [
                    'id' => $proyecto->id,
                    'nombre' => $proyecto->nombre,
                    'descripcion' => $proyecto->descripcion,
                    'ubicacion' => $proyecto->ubicacion,
                    'fecha_lanzamiento' => $proyecto->fecha_lanzamiento,
                    'numero_lotes' => $proyecto->numero_lotes,
                    'fotografia' => $proyecto->fotografia ? asset($proyecto->fotografia) : null,
                    'poligono' => $proyecto->poligono ? json_decode($proyecto->poligono->toJson()) : null,
                    'total_terrenos' => $proyecto->total_terrenos ?? 0,
                    'terrenos_disponibles' => $proyecto->terrenos_disponibles ?? 0,
                    'terrenos_vendidos' => $proyecto->terrenos_vendidos ?? 0,
                    'terrenos_reservados' => $proyecto->terrenos_reservados ?? 0,
                ];
            });

        return response()->json($proyectos);
    }

    /**
     * Obtener información detallada de un proyecto específico
     */
    public function getProyecto($proyectoId)
    {
        $proyecto = Proyecto::withCount([
            'terrenos',
            'terrenos as terrenos_disponibles_count' => function ($query) {
                $query->where('estado', 0)->where('condicion', true);
            },
            'terrenos as terrenos_vendidos_count' => function ($query) {
                $query->where('estado', 1);
            },
            'terrenos as terrenos_reservados_count' => function ($query) {
                $query->where('estado', 2);
            }
        ])->findOrFail($proyectoId);

        return response()->json([
            'id' => $proyecto->id,
            'nombre' => $proyecto->nombre,
            'descripcion' => $proyecto->descripcion,
            'ubicacion' => $proyecto->ubicacion,
            'fecha_lanzamiento' => $proyecto->fecha_lanzamiento,
            'numero_lotes' => $proyecto->numero_lotes,
            'fotografia' => $proyecto->fotografia ? asset($proyecto->fotografia) : null,
            'poligono' => $proyecto->poligono ? json_decode($proyecto->poligono->toJson()) : null,
            'total_terrenos' => $proyecto->terrenos_count,
            'terrenos_disponibles' => $proyecto->terrenos_disponibles_count,
            'terrenos_vendidos' => $proyecto->terrenos_vendidos_count,
            'terrenos_reservados' => $proyecto->terrenos_reservados_count,
        ]);
    }

    /**
     * Obtener BARRIOS en formato GeoJSON (zoom bajo: < 7)
     */
    public function getBarriosGeoJSON($proyectoId)
    {
        $barrios = Barrio::where('idproyecto', $proyectoId)
            ->whereNotNull('poligono')
            ->get();

        $features = [];

        foreach ($barrios as $barrio) {
            if (!$barrio->poligono) {
                continue;
            }

            $originalGeometry = json_decode($barrio->poligono->toJson(), true);

            $features[] = [
                'type' => 'Feature',
                'properties' => [
                    'id' => $barrio->id,
                    'nombre' => $barrio->nombre,
                    'tipo' => 'barrio',
                ],
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => $originalGeometry['coordinates']
                ]
            ];
        }

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features
        ]);
    }

    /**
     * Obtener CUADRAS en formato GeoJSON (zoom medio: 7-14)
     */
    public function getCuadrasGeoJSON($proyectoId)
    {
        $cuadras = Cuadra::whereHas('barrio', function ($query) use ($proyectoId) {
            $query->where('idproyecto', $proyectoId);
        })
            ->whereNotNull('poligono')
            ->with('barrio')
            ->get();

        $features = [];

        foreach ($cuadras as $cuadra) {
            if (!$cuadra->poligono) {
                continue;
            }

            $originalGeometry = json_decode($cuadra->poligono->toJson(), true);

            $features[] = [
                'type' => 'Feature',
                'properties' => [
                    'id' => $cuadra->id,
                    'nombre' => $cuadra->nombre,
                    'barrio' => $cuadra->barrio ? $cuadra->barrio->nombre : null,
                    'tipo' => 'cuadra',
                ],
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => $originalGeometry['coordinates']
                ]
            ];
        }

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features
        ]);
    }

    /**
     * Obtener TERRENOS en formato GeoJSON (zoom alto: 15-19)
     */
    public function getTerrenosGeoJSON($proyectoId)
    {
        $terrenos = Terreno::where('idproyecto', $proyectoId)
            ->where('condicion', true)
            ->whereNotNull('poligono')
            ->with('categorias_terrenos')
            ->get();

        $features = [];

        foreach ($terrenos as $terreno) {
            if (!$terreno->poligono) {
                continue;
            }

            $originalGeometry = json_decode($terreno->poligono->toJson(), true);

            $colorCategoria = $terreno->categorias_terrenos
                ? $terreno->categorias_terrenos->color
                : '#6b7280';

            $nombreCategoria = $terreno->categorias_terrenos
                ? $terreno->categorias_terrenos->nombre
                : ($terreno->categoria ?? 'Sin categoría');

            $features[] = [
                'type' => 'Feature',
                'properties' => [
                    'id' => $terreno->id,
                    'codigo' => $terreno->numero_terreno, 
                    'ubicacion' => $terreno->ubicacion,
                    'categoria' => $nombreCategoria,
                    'categoria_color' => $colorCategoria,
                    'superficie' => $terreno->superficie,
                    'cuota_inicial' => $terreno->cuota_inicial,
                    'cuota_mensual' => $terreno->cuota_mensual,
                    'precio_venta' => $terreno->precio_venta,
                    'estado' => $terreno->estado,
                    'condicion' => $terreno->condicion,
                    'estado_label' => $this->getEstadoLabel($terreno->estado),
                    'tipo' => 'terreno',
                ],
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => $originalGeometry['coordinates']
                ]
            ];
        }

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features
        ]);
    }

    /**
     * Obtener categorías activas de un proyecto
     */
    public function getCategorias($proyectoId)
    {
        $categorias = CategoriaTerreno::where('idproyecto', $proyectoId)
            ->where('estado', true)
            ->withCount(['terrenos' => function ($query) {
                $query->where('condicion', true);
            }])
            ->orderBy('nombre', 'asc')
            ->get()
            ->map(function ($categoria) {
                return [
                    'id' => $categoria->id,
                    'nombre' => $categoria->nombre,
                    'color' => $categoria->color,
                    'total_terrenos' => $categoria->terrenos_count ?? 0
                ];
            });

        return response()->json($categorias);
    }


    private function getEstadoLabel($estado)
    {
        switch ($estado) {
            case 0:
                return 'Disponible';
            case 1:
                return 'Vendido';
            case 2:
                return 'Reservado';
            default:
                return 'Desconocido';
        }
    }
}