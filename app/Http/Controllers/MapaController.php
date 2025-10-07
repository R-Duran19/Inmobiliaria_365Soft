<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\Terreno;
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
     * Incluye contadores y estadísticas para el panel de información del mapa
     */
    public function getProyecto($proyectoId)
    {
        $proyecto = Proyecto::withCount([
            'terrenos',
            'terrenos as terrenos_disponibles_count' => function($query) {
                $query->where('estado', 0)->where('condicion', true);
            },
            'terrenos as terrenos_vendidos_count' => function($query) {
                $query->where('estado', 1);
            },
            'terrenos as terrenos_reservados_count' => function($query) {
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
            'total_terrenos' => $proyecto->terrenos_count,
            'terrenos_disponibles' => $proyecto->terrenos_disponibles_count,
            'terrenos_vendidos' => $proyecto->terrenos_vendidos_count,
            'terrenos_reservados' => $proyecto->terrenos_reservados_count,
        ]);
    }

    /**
     * Obtener terrenos de un proyecto en formato GeoJSON para Leaflet/Mapbox
     * Solo terrenos visibles en el mapa con condición activa
     */
    public function getTerrenosGeoJSON($proyectoId)
    {
        $terrenos = Terreno::where('idproyecto', $proyectoId)
            ->where('condicion', true)
            ->whereNotNull('poligono')
            ->get();

        $features = [];
        
        foreach ($terrenos as $terreno) {
            if (!$terreno->poligono) {
                continue;
            }

            // Obtener geometría original desde MySQL
            $originalGeometry = json_decode($terreno->poligono->toJson(), true);
            
            // INVERTIR coordenadas: MySQL devuelve [lat, lon], GeoJSON necesita [lon, lat]
            $invertedCoordinates = [];
            foreach ($originalGeometry['coordinates'] as $ring) {
                $invertedRing = [];
                foreach ($ring as $point) {
                    // Invertir: [lat, lon] -> [lon, lat]
                    $invertedRing[] = [$point[1], $point[0]];
                }
                $invertedCoordinates[] = $invertedRing;
            }

            $features[] = [
                'type' => 'Feature',
                'properties' => [
                    'id' => $terreno->id,
                    'codigo' => $terreno->codigo,
                    'ubicacion' => $terreno->ubicacion,
                    'categoria' => $terreno->categoria,
                    'superficie' => $terreno->superficie,
                    'cuota_inicial' => $terreno->cuota_inicial,
                    'cuota_mensual' => $terreno->cuota_mensual,
                    'precio_venta' => $terreno->precio_venta,
                    'estado' => $terreno->estado,
                    'condicion' => $terreno->condicion,
                    // Etiquetas legibles para tooltips
                    'estado_label' => $this->getEstadoLabel($terreno->estado),
                ],
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => $invertedCoordinates
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
     * Para filtros de categorías en el mapa
     */
    public function getCategorias($proyectoId)
    {
        $categorias = CategoriaTerreno::where('idproyecto', $proyectoId)
            ->where('estado', true)
            ->select('id', 'nombre', 'descripcion', 'color')
            ->orderBy('nombre', 'asc')
            ->get();

        return response()->json($categorias);
    }

    /**
     * Helper: Obtener etiqueta legible del estado
     */
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