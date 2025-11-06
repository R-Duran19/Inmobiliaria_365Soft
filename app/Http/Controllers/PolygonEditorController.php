<?php

namespace App\Http\Controllers;


use Inertia\Inertia;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Proyecto;
use App\Models\Barrio;
use App\Models\Cuadra;
use App\Models\Terreno;
use App\Models\CategoriaTerreno;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Polygon;



class PolygonEditorController extends Controller
{
    /**
     * Obtener datos iniciales para el editor
     * (proyectos, categorías, etc.)
     */
    public function index(Request $request)
    {
        return inertia('MapaEditor/Index');
    }
    
    public function editarMapa($proyecto)
    {
        return inertia('MapaEditor/editMapa', [
            'selectedProyectoId' => $proyecto
        ]);
    }

    public function irIndex($proyecto)
    {
        return inertia('MapaEditor/Index', [
            'selectedProyectoId' => $proyecto
        ]);
    }


    
    public function getInitialData(Request $request)
    {
        try {
            $proyectos = Proyecto::select('id', 'nombre')->where('estado', true)->get();
            $categorias = CategoriaTerreno::select('id', 'nombre', 'color', 'idproyecto')->where('estado', true)->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'proyectos' => $proyectos,
                    'categorias' => $categorias,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo datos iniciales', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos iniciales',
            ], 500);
        }
    }

    /**
     * Obtener barrios de un proyecto
     */
    public function getBarriosByProyecto(Request $request, $idProyecto)
    {
        try {
            $barrios = Barrio::where('idproyecto', $idProyecto)
                ->select('id', 'nombre')
                ->get();

            return response()->json([
                'success' => true,
                'barrios' => $barrios,
            ], 200);
        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo barrios', [
                'idproyecto' => $idProyecto,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener barrios',
            ], 500);
        }
    }

    /**
     * Obtener cuadras de un barrio
     */
    public function getCuadrasByBarrio(Request $request, $idBarrio)
    {
        try {
            $cuadras = Cuadra::where('idbarrio', $idBarrio)
                ->select('id', 'nombre')
                ->get();

            return response()->json([
                'success' => true,
                'cuadras' => $cuadras,
            ], 200);
        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo cuadras', [
                'idbarrio' => $idBarrio,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener cuadras',
            ], 500);
        }
    }

    /**
     * Guardar polígonos dibujados
     */
    /**
 * Guardar polígonos dibujados
 */
public function savePolygons(Request $request)
{
    $validator = Validator::make($request->all(), [
        'poligonos' => 'required|array|min:1',
        'poligonos.*.tipo' => 'required|in:proyecto,barrio,cuadra,terreno',
        'poligonos.*.geometry' => 'required|array',
        'poligonos.*.properties' => 'required|array',
    ]);

    if ($validator->fails()) {
        return back()->with([
            'flash' => [
                'success' => false,
                'errors' => $validator->errors(),
            ]
        ]);
    }

    try {
        DB::beginTransaction();

        $result = [
            'proyecto' => ['creado' => 0, 'actualizado' => 0, 'error' => 0],
            'barrios' => ['creados' => 0, 'actualizados' => 0, 'errores' => 0],
            'cuadras' => ['creadas' => 0, 'actualizadas' => 0, 'errores' => 0],
            'terrenos' => ['creados' => 0, 'actualizados' => 0, 'errores' => 0],
            'errores' => [],
        ];

        foreach ($request->poligonos as $index => $poligono) {
            try {
                $tipo = $poligono['tipo'];
                $geometry = $poligono['geometry'];
                $properties = $poligono['properties'];

                switch ($tipo) {
                    case 'proyecto':
                        $this->saveProyecto($geometry, $properties, $result);
                        break;

                    case 'barrio':
                        $this->saveBarrio($geometry, $properties, $result);
                        break;

                    case 'cuadra':
                        $this->saveCuadra($geometry, $properties, $result);
                        break;

                    case 'terreno':
                        $this->saveTerreno($geometry, $properties, $result);
                        break;
                }
            } catch (\Exception $e) {
                $result['errores'][] = "Polígono #{$index} ({$tipo}): {$e->getMessage()}";
                $result[$tipo === 'cuadra' ? 'cuadras' : $tipo . 's']['errores']++;
            }
        }

DB::commit();

Log::info('✅ Polígonos guardados exitosamente', ['resultado' => $result]);

return redirect()->back()->with([
    'flash' => [
        'success' => true,
        'message' => 'Polígonos guardados exitosamente',
        'resultado' => $result,
    ]
]);
    } catch (\Exception $e) {
DB::rollBack();

Log::error('❌ Error guardando polígonos', [
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
]);



    }
}

    // ========== MÉTODOS PRIVADOS ==========

    private function saveProyecto($geometry, $properties, &$result)
    {
        $nombre = $properties['nombre'] ?? $properties['PROYECTO'] ?? null;

        if (!$nombre) {
            throw new \Exception('Nombre de proyecto no especificado');
        }

        $poligono = $this->createPolygon($geometry['coordinates']);

        // Buscar proyecto existente
        $proyecto = Proyecto::where('nombre', $nombre)->first();

        if ($proyecto) {
            // Actualizar solo si no tiene polígono
            if ($proyecto->poligono === null) {
                $proyecto->update(['poligono' => $poligono]);
                $result['proyecto']['actualizado']++;
                Log::info('🔄 Polígono de proyecto actualizado', ['proyecto' => $nombre]);
            } else {
                Log::debug('⏭️ Proyecto ya tiene polígono', ['proyecto' => $nombre]);
            }
        } else {
            throw new \Exception("Proyecto '{$nombre}' no existe. Debes crearlo primero manualmente.");
        }
    }

    private function saveBarrio($geometry, $properties, &$result)
    {
        $nombre = $properties['nombre'] ?? null;
        $idProyecto = $properties['idproyecto'] ?? null;

        if (!$nombre || !$idProyecto) {
            throw new \Exception('Nombre de barrio o ID de proyecto no especificado');
        }

        // Verificar que el proyecto existe
        if (!Proyecto::find($idProyecto)) {
            throw new \Exception("Proyecto ID {$idProyecto} no existe");
        }

        $poligono = $this->createPolygon($geometry['coordinates']);

        // Buscar barrio existente
        $barrio = Barrio::where('idproyecto', $idProyecto)
            ->where('nombre', $nombre)
            ->first();

        if ($barrio) {
            if ($barrio->poligono === null) {
                $barrio->update(['poligono' => $poligono]);
                $result['barrios']['actualizados']++;
                Log::info('🔄 Polígono de barrio actualizado', ['barrio' => $nombre]);
            } else {
                Log::debug('⏭️ Barrio ya tiene polígono', ['barrio' => $nombre]);
            }
        } else {
            // Crear nuevo barrio
            Barrio::create([
                'idproyecto' => $idProyecto,
                'nombre' => $nombre,
                'poligono' => $poligono,
            ]);
            $result['barrios']['creados']++;
            Log::info('✅ Barrio creado', ['barrio' => $nombre, 'idproyecto' => $idProyecto]);
        }
    }

    private function saveCuadra($geometry, $properties, &$result)
    {
        $nombre = $properties['nombre'] ?? null;
        $idBarrio = $properties['idbarrio'] ?? null;

        if (!$nombre || !$idBarrio) {
            throw new \Exception('Nombre de cuadra o ID de barrio no especificado');
        }

        // Verificar que el barrio existe
        if (!Barrio::find($idBarrio)) {
            throw new \Exception("Barrio ID {$idBarrio} no existe");
        }

        $poligono = $this->createPolygon($geometry['coordinates']);

        // Buscar cuadra existente
        $cuadra = Cuadra::where('idbarrio', $idBarrio)
            ->where('nombre', $nombre)
            ->first();

        if ($cuadra) {
            if ($cuadra->poligono === null) {
                $cuadra->update(['poligono' => $poligono]);
                $result['cuadras']['actualizadas']++;
                Log::info('🔄 Polígono de cuadra actualizado', ['cuadra' => $nombre]);
            } else {
                Log::debug('⏭️ Cuadra ya tiene polígono', ['cuadra' => $nombre]);
            }
        } else {
            // Crear nueva cuadra
            Cuadra::create([
                'idbarrio' => $idBarrio,
                'nombre' => $nombre,
                'poligono' => $poligono,
            ]);
            $result['cuadras']['creadas']++;
            Log::info('✅ Cuadra creada', ['cuadra' => $nombre, 'idbarrio' => $idBarrio]);
        }
    }

    private function saveTerreno($geometry, $properties, &$result)
    {
        $numero = $properties['numero'] ?? null;
        $idCuadra = $properties['idcuadra'] ?? null;
        $idProyecto = $properties['idproyecto'] ?? null;
        $superficie = $properties['superficie'] ?? '0';
        $idCategoria = $properties['idcategoria'] ?? null;

        if (!$numero || !$idCuadra || !$idProyecto) {
            throw new \Exception('Número de terreno, ID de cuadra o ID de proyecto no especificado');
        }

        // Verificar que la cuadra existe
        $cuadra = Cuadra::with('barrio')->find($idCuadra);
        if (!$cuadra) {
            throw new \Exception("Cuadra ID {$idCuadra} no existe");
        }

        // Si no hay categoría, usar la primera disponible del proyecto
        if (!$idCategoria) {
            $categoriaDefault = CategoriaTerreno::where('idproyecto', $idProyecto)->first();
            if (!$categoriaDefault) {
                throw new \Exception("No hay categorías disponibles para el proyecto {$idProyecto}");
            }
            $idCategoria = $categoriaDefault->id;
        }

        $poligono = $this->createPolygon($geometry['coordinates']);

        // Generar ubicación automática
        $ubicacion = "{$cuadra->barrio->nombre} {$cuadra->nombre} LT {$numero}";

        // Buscar terreno existente
        $terreno = Terreno::where('idcuadra', $idCuadra)
            ->where('numero_terreno', $numero)
            ->first();

        if ($terreno) {
            if ($terreno->poligono === null) {
                $terreno->update(['poligono' => $poligono]);
                $result['terrenos']['actualizados']++;
                Log::info('🔄 Polígono de terreno actualizado', ['terreno' => $numero]);
            } else {
                Log::debug('⏭️ Terreno ya tiene polígono', ['terreno' => $numero]);
            }
        } else {
            // Crear nuevo terreno
            Terreno::create([
                'idproyecto' => $idProyecto,
                'idcategoria' => $idCategoria,
                'idcuadra' => $idCuadra,
                'numero_terreno' => $numero,
                'ubicacion' => $ubicacion,
                'superficie' => $superficie,
                'cuota_inicial' => 0,
                'cuota_mensual' => 0,
                'precio_venta' => 0,
                'estado' => 0,
                'condicion' => true,
                'poligono' => $poligono,
            ]);
            $result['terrenos']['creados']++;
            Log::info('✅ Terreno creado', ['terreno' => $numero, 'idcuadra' => $idCuadra]);
        }
    }

    private function createPolygon($coordinates)
    {
        // Si es MultiPolygon, tomar el primer polígono
        if (isset($coordinates[0][0][0]) && is_array($coordinates[0][0][0])) {
            $coordinates = $coordinates[0];
        }

        $coords = $coordinates[0];

        $points = array_map(function ($coord) {
            $longitude = $coord[0];
            $latitude = $coord[1];
            return new Point($latitude, $longitude);
        }, $coords);

        return new Polygon([new LineString($points)]);
    }

    // public function getPoligonos($proyectoId)
    // {
    //     $barrios = Barrio::where('idproyecto', $proyectoId)->whereNotNull('poligono')->get();
    //     $cuadras = Cuadra::whereHas('barrio', fn($q) => $q->where('idproyecto', $proyectoId))->whereNotNull('poligono')->get();
    //     $terrenos = Terreno::whereHas('cuadra.barrio', fn($q) => $q->where('idproyecto', $proyectoId))->whereNotNull('poligono')->get();

    //     return response()->json([
    //         'success' => true,
    //         'data' => [
    //             'barrios' => $barrios,
    //             'cuadras' => $cuadras,
    //             'terrenos' => $terrenos,
    //         ]
    //     ]);
    // }

public function getPoligonos($idProyecto)
{
    $barrios = Barrio::where('idproyecto', $idProyecto)
        ->whereNotNull('poligono')
        ->get();

    $cuadras = Cuadra::whereHas('barrio', fn($q) =>
        $q->where('idproyecto', $idProyecto)
    )
    ->whereNotNull('poligono')
    ->get();

    $terrenos = Terreno::whereHas('cuadra.barrio', fn($q) =>
        $q->where('idproyecto', $idProyecto)
    )
    ->whereNotNull('poligono')
    ->get();

    return response()->json([
        'success' => true,
        'data' => [
            'barrios' => $barrios->map(function ($barrio) {
                return [
                    'id' => $barrio->id,
                    'nombre' => $barrio->nombre,
                    'geometry' => $barrio->poligono, // Usa el accessor
                ];
            }),
            'cuadras' => $cuadras->map(function ($cuadra) {
                return [
                    'id' => $cuadra->id,
                    'nombre' => $cuadra->nombre,
                    'geometry' => $cuadra->poligono, // Usa el accessor
                ];
            }),
            'terrenos' => $terrenos->map(function ($terreno) {
                return [
                    'id' => $terreno->id,
                    'numero' => $terreno->numero_terreno,
                    'geometry' => $terreno->poligono, // Usa el accessor
                ];
            }),
        ],
    ]);
}


    public function updatePoligono(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tipo' => 'required|in:barrio,cuadra,terreno',
            'id' => 'required|integer',
            'poligono' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $modelo = null;
            switch ($request->input('tipo')) {
                case 'barrio':
                    $modelo = Barrio::find($request->id);
                    break;
                case 'cuadra':
                    $modelo = Cuadra::where('id', $request->id)
                                    ->whereNotNull('idbarrio')
                                    ->first();
                    break;
                case 'terreno':
                    $modelo = Terreno::find($request->id);
                    break;
            }

            if (!$modelo) {
                return response()->json([
                    'success' => false,
                    'message' => "No se encontró el {$request->tipo} con ID {$request->id}."
                ], 404);
            }

            $coordinates = $request->poligono['coordinates'][0]; // Primer anillo del polígono

            $lineString = new LineString(
                array_map(function ($coordinate) {
                    // El orden correcto para Point es (latitud, longitud)
                    return new Point($coordinate[1], $coordinate[0]);
                },  $coordinates)
            );

            $polygon = new Polygon([$lineString]);

            $modelo->poligono = $polygon;
            $modelo->save();

            $modelo->save();

            return response()->json([
                'success' => true,
                'message' => 'Polígono actualizado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el polígono: ' . $e->getMessage()
            ], 500);
        }
    }





}
