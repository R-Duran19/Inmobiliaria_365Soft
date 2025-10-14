<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Proyecto;
use App\Models\Barrio;
use App\Models\Cuadra;
use App\Models\Terreno;
use App\Models\CategoriaTerreno;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Polygon;

class ImportJSONController extends Controller
{
    /**
     * Analizar GeoJSON y detectar qué contiene
     */
    public function analyze(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'geojson' => 'required|array',
            'geojson.features' => 'required|array|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $features = $request->geojson['features'];
            $analysis = $this->analyzeFeatures($features);

            return response()->json([
                'success' => true,
                'analysis' => $analysis,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al analizar el archivo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener entidades disponibles para mapeo
     */
    public function getAvailableEntities(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idproyecto' => 'nullable|exists:proyectos,id',
            'idbarrio' => 'nullable|exists:barrios,id',
            'type' => 'required|in:proyectos,barrios,cuadras',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $type = $request->type;
        $entities = [];

        switch ($type) {
            case 'proyectos':
                $entities = Proyecto::select('id', 'nombre')->get();
                break;

            case 'barrios':
                if ($request->idproyecto) {
                    $entities = Barrio::where('idproyecto', $request->idproyecto)
                        ->select('id', 'nombre')
                        ->get();
                }
                break;

            case 'cuadras':
                if ($request->idbarrio) {
                    $entities = Cuadra::where('idbarrio', $request->idbarrio)
                        ->select('id', 'nombre')
                        ->get();
                }
                break;
        }

        return response()->json([
            'success' => true,
            'entities' => $entities,
        ], 200);
    }

    /**
     * Detectar conflictos antes de importar
     */
    public function detectConflicts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'geojson' => 'required|array',
            'mapping' => 'required|array',
            'mapping.proyecto_nombre' => 'nullable|string',
            'mapping.idproyecto' => 'nullable|exists:proyectos,id',
            'mapping.idbarrio' => 'nullable|exists:barrios,id',
            'mapping.idcuadra' => 'nullable|exists:cuadras,id',
            'mapping.cuadra_map' => 'nullable|array',
            'mapping.cuadra_map.*' => 'nullable|exists:cuadras,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $conflicts = $this->findConflicts($request->geojson['features'], $request->mapping);

            return response()->json([
                'success' => true,
                'conflicts' => $conflicts,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al detectar conflictos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Importar GeoJSON con configuración flexible
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'geojson' => 'required|array',
            'geojson.features' => 'required|array|min:1',
            'mapping' => 'required|array',
            'conflictResolution' => 'required|in:overwrite_all,skip_all,ask',
            'conflictDecisions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $result = $this->processImport(
                $request->geojson['features'],
                $request->mapping,
                $request->conflictResolution,
                $request->conflictDecisions ?? []
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Importación completada exitosamente',
                'summary' => $result,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error durante la importación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ========== MÉTODOS PRIVADOS ==========

    /**
     * Analizar features y clasificar
     */
    private function analyzeFeatures($features)
    {
        $proyecto = null;
        $barrios = [];
        $cuadras = [];
        $terrenos = [];
        $errors = [];

        foreach ($features as $index => $feature) {
            if (!isset($feature['properties']) || !isset($feature['geometry'])) {
                $errors[] = "Feature #{$index}: Estructura inválida";
                continue;
            }

            $props = $feature['properties'];
            $normalizedProps = $this->normalizeProperties($props);

            $classified = $this->classifyFeature($normalizedProps, $index);

            if ($classified['type'] === 'proyecto') {
                $proyecto = $classified['data'];
            } elseif ($classified['type'] === 'barrio') {
                $barrios[] = $classified['data'];
            } elseif ($classified['type'] === 'cuadra') {
                $cuadras[] = $classified['data'];
            } elseif ($classified['type'] === 'terreno') {
                $terrenos[] = $classified['data'];
            } elseif ($classified['type'] === 'error') {
                $errors[] = $classified['message'];
            }
        }

        $importType = $this->determineImportType(
            $proyecto !== null,
            count($barrios),
            count($cuadras),
            count($terrenos)
        );

        return [
            'total_features' => count($features),
            'proyecto' => $proyecto,
            'barrios' => [
                'count' => count($barrios),
                'items' => $barrios,
            ],
            'cuadras' => [
                'count' => count($cuadras),
                'items' => $cuadras,
            ],
            'terrenos' => [
                'count' => count($terrenos),
                'items' => $terrenos,
            ],
            'import_type' => $importType,
            'errors' => $errors,
            'valid' => empty($errors),
        ];
    }

    /**
     * Normalizar propiedades del feature
     */
    private function normalizeProperties($props)
    {
        $normalized = [];
        foreach ($props as $key => $value) {
            $normalizedKey = trim($key);
            $normalized[$normalizedKey] = is_string($value) ? trim($value) : $value;
        }
        return $normalized;
    }

    /**
     * Clasificar un feature según sus propiedades
     */
    private function classifyFeature($props, $index)
    {
        // PATRÓN 1: PROYECTO
        foreach ($props as $key => $value) {
            $keyTrimmed = trim($key);
            $valueTrimmed = is_string($value) ? trim($value) : strval($value);

            // "proyecto" -> nombre (PROYECTO)
            if (strtoupper($keyTrimmed) === 'PROYECTO' && !empty($valueTrimmed)) {
                return [
                    'type' => 'proyecto',
                    'data' => [
                        'nombre' => $valueTrimmed,
                        'index' => $index,
                    ]
                ];
            }
        }

        // PATRÓN 2: MZ XXX -> número (TERRENO)
        foreach ($props as $key => $value) {
            $keyTrimmed = trim($key);
            $valueTrimmed = is_string($value) ? trim($value) : strval($value);

            if (preg_match('/^MZ\s*\d+$/i', $keyTrimmed)) {
                return [
                    'type' => 'terreno',
                    'data' => [
                        'numero' => $valueTrimmed,
                        'cuadra_ref' => $keyTrimmed,
                        'index' => $index,
                    ]
                ];
            }

            // PATRÓN 3: UV XXX -> MZ XXX (CUADRA)
            if (preg_match('/^UV\s*\d+$/i', $keyTrimmed) && preg_match('/^MZ\s*\d+$/i', $valueTrimmed)) {
                return [
                    'type' => 'cuadra',
                    'data' => [
                        'nombre' => $valueTrimmed,
                        'barrio_ref' => $keyTrimmed,
                        'index' => $index,
                    ]
                ];
            }

            // PATRÓN 4: Solo UV XXX (BARRIO)
            if (preg_match('/^UV\s*\d+$/i', $keyTrimmed) && !preg_match('/^MZ\s*\d+$/i', $valueTrimmed)) {
                return [
                    'type' => 'barrio',
                    'data' => [
                        'nombre' => $keyTrimmed,
                        'index' => $index,
                    ]
                ];
            }
        }

        // PATRÓN 5: Fallback formato antiguo
        return $this->classifyFeatureLegacy($props, $index);
    }

    /**
     * Clasificación por formato antiguo (compatibilidad)
     */
    private function classifyFeatureLegacy($props, $index)
    {
        $barrioKey = null;
        $cuadraKey = null;
        $numeroKey = null;

        foreach ($props as $key => $value) {
            $keyUpper = strtoupper(trim($key));
            $valueStr = is_string($value) ? trim($value) : '';

            if ($keyUpper === 'CUADRA' && preg_match('/^MZ\s*\d+$/i', $valueStr)) {
                $cuadraKey = $key;
            }

            if ($keyUpper === 'BARRIO' && preg_match('/^UV\s*\d+$/i', $valueStr)) {
                $barrioKey = $key;
            }

            if (in_array($keyUpper, ['NUMERO', 'LOTE', 'TERRENO', 'LT'])) {
                $numeroKey = $key;
            }
        }

        if ($numeroKey && isset($props[$numeroKey])) {
            return [
                'type' => 'terreno',
                'data' => [
                    'numero' => $props[$numeroKey],
                    'cuadra_ref' => null,
                    'index' => $index,
                ]
            ];
        }

        if ($cuadraKey) {
            return [
                'type' => 'cuadra',
                'data' => [
                    'nombre' => $props[$cuadraKey],
                    'barrio_ref' => null,
                    'index' => $index,
                ]
            ];
        }

        if ($barrioKey && isset($props[$barrioKey])) {
            return [
                'type' => 'barrio',
                'data' => [
                    'nombre' => $props[$barrioKey],
                    'index' => $index,
                ]
            ];
        }

        return [
            'type' => 'error',
            'message' => "Feature #{$index}: No se detectó patrón válido (props: " . implode(', ', array_keys($props)) . ")"
        ];
    }

    /**
     * Determinar tipo de importación
     */
    private function determineImportType($tieneProyecto, $barriosCount, $cuadrasCount, $terrenosCount)
    {
        if ($tieneProyecto) {
            if ($barriosCount > 0 && $cuadrasCount > 0 && $terrenosCount > 0) {
                return 'complete_with_proyecto';
            }
            return 'proyecto_only';
        }

        if ($barriosCount > 0 && $cuadrasCount > 0 && $terrenosCount > 0) {
            return 'complete';
        } elseif ($barriosCount > 0 && $cuadrasCount === 0 && $terrenosCount === 0) {
            return 'barrios_only';
        } elseif ($cuadrasCount > 0 && $terrenosCount > 0) {
            return 'cuadras_terrenos';
        } elseif ($cuadrasCount > 0) {
            return 'cuadras_only';
        } elseif ($terrenosCount > 0) {
            return 'terrenos_only';
        }

        return 'unknown';
    }

    /**
     * Encontrar conflictos
     */
    private function findConflicts($features, $mapping)
    {
        $conflicts = [
            'proyecto' => null,
            'barrios' => [],
            'cuadras' => [],
            'terrenos' => [],
        ];

        foreach ($features as $index => $feature) {
            $props = $feature['properties'];
            $normalizedProps = $this->normalizeProperties($props);
            $classified = $this->classifyFeature($normalizedProps, $index);

            if ($classified['type'] === 'proyecto') {
                $nombre = $classified['data']['nombre'];
                $existing = Proyecto::where('nombre', $nombre)->first();

                if ($existing) {
                    $conflicts['proyecto'] = [
                        'index' => $index,
                        'nombre' => $nombre,
                        'tiene_poligono' => !is_null($existing->poligono),
                        'id' => $existing->id,
                    ];
                }
            } elseif ($classified['type'] === 'barrio') {
                $nombre = $classified['data']['nombre'];
                $idProyecto = $mapping['idproyecto'] ?? null;

                if ($idProyecto) {
                    $existing = Barrio::where('idproyecto', $idProyecto)
                        ->where('nombre', $nombre)
                        ->first();

                    if ($existing) {
                        $conflicts['barrios'][] = [
                            'index' => $index,
                            'nombre' => $nombre,
                            'tiene_poligono' => !is_null($existing->poligono),
                            'id' => $existing->id,
                        ];
                    }
                }
            } elseif ($classified['type'] === 'cuadra') {
                $nombre = $classified['data']['nombre'];
                $barrioId = $this->findBarrioIdByName($classified['data']['barrio_ref'], $mapping);

                if ($barrioId) {
                    $existing = Cuadra::where('idbarrio', $barrioId)
                        ->where('nombre', $nombre)
                        ->first();

                    if ($existing) {
                        $conflicts['cuadras'][] = [
                            'index' => $index,
                            'nombre' => $nombre,
                            'tiene_poligono' => !is_null($existing->poligono),
                            'id' => $existing->id,
                        ];
                    }
                }
            } elseif ($classified['type'] === 'terreno') {
                $numero = $classified['data']['numero'];
                $cuadraId = $this->findCuadraIdByName($classified['data']['cuadra_ref'], $mapping);

                if ($cuadraId) {
                    $existing = Terreno::where('idcuadra', $cuadraId)
                        ->where('numero_terreno', $numero)
                        ->first();

                    if ($existing) {
                        $conflicts['terrenos'][] = [
                            'index' => $index,
                            'numero' => $numero,
                            'tiene_poligono' => !is_null($existing->poligono),
                            'id' => $existing->id,
                        ];
                    }
                }
            }
        }

        return $conflicts;
    }

    /**
     * Procesar importación completa
     */
    private function processImport($features, $mapping, $conflictResolution, $conflictDecisions)
    {
        $summary = [
            'proyecto' => ['creado' => false, 'actualizado' => false, 'omitido' => false],
            'barrios' => ['creados' => 0, 'actualizados' => 0, 'omitidos' => 0],
            'cuadras' => ['creadas' => 0, 'actualizadas' => 0, 'omitidas' => 0],
            'terrenos' => ['creados' => 0, 'actualizados' => 0, 'omitidos' => 0],
        ];

        $categoriaDefault = CategoriaTerreno::first();
        if (!$categoriaDefault) {
            throw new \Exception('No hay categorías de terreno. Crea al menos una.');
        }

        // Primer paso: procesar proyecto si existe
        foreach ($features as $index => $feature) {
            $props = $feature['properties'];
            $normalizedProps = $this->normalizeProperties($props);
            $classified = $this->classifyFeature($normalizedProps, $index);

            if ($classified['type'] === 'proyecto') {
                $hasConflict = $this->hasConflict($index, $feature, $mapping);

                // ← Para proyectos, NO usar shouldSkip normal
                $shouldSkip = false;

                if ($hasConflict && $conflictResolution === 'skip_all') {
                    $shouldSkip = true;
                }
                // Para proyectos: siempre procesar en 'ask' o 'overwrite_all'

                if ($shouldSkip) {
                    $summary['proyecto']['omitido'] = true;
                } else {
                    $result = $this->processProyecto($feature, $mapping, $classified['data']);
                    if ($result === 'creado') {
                        $summary['proyecto']['creado'] = true;
                        $mapping['idproyecto'] = Proyecto::where('nombre', $classified['data']['nombre'])->first()->id;
                    } else {
                        $summary['proyecto']['actualizado'] = true;
                    }
                }
            }
        }

        // Segundo paso: procesar barrios, cuadras, terrenos
        foreach ($features as $index => $feature) {
            $props = $feature['properties'];
            $normalizedProps = $this->normalizeProperties($props);
            $classified = $this->classifyFeature($normalizedProps, $index);

            if ($classified['type'] === 'error' || $classified['type'] === 'proyecto') {
                continue;
            }

            $hasConflict = $this->hasConflict($index, $feature, $mapping);
            $shouldSkip = $this->shouldSkipFeature($hasConflict, $conflictResolution, $conflictDecisions, $index);

            if ($shouldSkip) {
                if ($classified['type'] === 'barrio') {
                    $summary['barrios']['omitidos']++;
                } elseif ($classified['type'] === 'cuadra') {
                    $summary['cuadras']['omitidas']++;
                } elseif ($classified['type'] === 'terreno') {
                    $summary['terrenos']['omitidos']++;
                }
                continue;
            }

            if ($classified['type'] === 'barrio') {
                $result = $this->processBarrio($feature, $mapping, $classified['data']);
                $summary['barrios'][$result]++;
            } elseif ($classified['type'] === 'cuadra') {
                $result = $this->processCuadra($feature, $mapping, $classified['data']);
                $summary['cuadras'][$result]++;
            } elseif ($classified['type'] === 'terreno') {
                $result = $this->processTerreno($feature, $mapping, $categoriaDefault, $classified['data']);
                $summary['terrenos'][$result]++;
            }
        }

        return $summary;
    }

    private function hasConflict($index, $feature, $mapping)
    {
        $props = $feature['properties'];
        $normalizedProps = $this->normalizeProperties($props);
        $classified = $this->classifyFeature($normalizedProps, $index);

        if ($classified['type'] === 'proyecto') {
            $nombre = $classified['data']['nombre'];
            return Proyecto::where('nombre', $nombre)->exists();
        }

        if ($classified['type'] === 'barrio') {
            $nombre = $classified['data']['nombre'];
            $idProyecto = $mapping['idproyecto'] ?? null;

            if ($idProyecto) {
                return Barrio::where('idproyecto', $idProyecto)
                    ->where('nombre', $nombre)
                    ->exists();
            }
        }

        if ($classified['type'] === 'cuadra') {
            $nombre = $classified['data']['nombre'];
            $barrioId = $this->findBarrioIdByName($classified['data']['barrio_ref'], $mapping);

            if ($barrioId) {
                return Cuadra::where('idbarrio', $barrioId)
                    ->where('nombre', $nombre)
                    ->exists();
            }
        }

        if ($classified['type'] === 'terreno') {
            $numero = $classified['data']['numero'];
            $cuadraId = $this->findCuadraIdByName($classified['data']['cuadra_ref'], $mapping);

            if ($cuadraId) {
                return Terreno::where('idcuadra', $cuadraId)
                    ->where('numero_terreno', $numero)
                    ->exists();
            }
        }

        return false;
    }

    private function shouldSkipFeature($hasConflict, $resolution, $decisions, $index)
    {
        if (!$hasConflict) {
            return false;
        }

        if ($resolution === 'skip_all') {
            return true;
        }

        if ($resolution === 'ask') {
            return !isset($decisions[$index]) || $decisions[$index] !== 'overwrite';
        }

        return false;
    }

    private function processProyecto($feature, $mapping, $classifiedData)
    {
        $nombre = $classifiedData['nombre'];

        $existing = Proyecto::where('nombre', $nombre)->first();

        // ← Si NO existe, tirar error
        if (!$existing) {
            throw new \Exception("Proyecto '{$nombre}' no encontrado en la base de datos. Solo se pueden actualizar proyectos existentes.");
        }

        // Si existe y no tiene polígono, actualizar
        if ($existing->poligono === null) {
            $poligono = $this->createPolygon($feature['geometry']['coordinates']);
            $existing->update(['poligono' => $poligono]);
            return 'actualizado';
        }

        // Si existe y ya tiene polígono, dejar igual
        return 'actualizado';
    }

    private function processBarrio($feature, $mapping, $classifiedData)
    {
        $nombre = $classifiedData['nombre'];
        $idProyecto = $mapping['idproyecto'];

        $existing = Barrio::where('idproyecto', $idProyecto)
            ->where('nombre', $nombre)
            ->first();

        $poligono = $this->createPolygon($feature['geometry']['coordinates']);

        if ($existing) {
            if ($existing->poligono === null) {
                $existing->update(['poligono' => $poligono]);
            }
            return 'actualizados';
        }

        Barrio::create([
            'idproyecto' => $idProyecto,
            'nombre' => $nombre,
            'poligono' => $poligono,
        ]);

        return 'creados';
    }

    private function processCuadra($feature, $mapping, $classifiedData)
    {
        $nombre = $classifiedData['nombre'];
        $barrioRef = $classifiedData['barrio_ref'];

        $barrioId = $this->findBarrioIdByName($barrioRef, $mapping);

        if (!$barrioId) {
            throw new \Exception("No se encontró el barrio '{$barrioRef}' para la cuadra '{$nombre}'");
        }

        $existing = Cuadra::where('idbarrio', $barrioId)
            ->where('nombre', $nombre)
            ->first();

        $poligono = $this->createPolygon($feature['geometry']['coordinates']);

        if ($existing) {
            if ($existing->poligono === null) {
                $existing->update(['poligono' => $poligono]);
            }
            return 'actualizadas';
        }

        Cuadra::create([
            'idbarrio' => $barrioId,
            'nombre' => $nombre,
            'poligono' => $poligono,
        ]);

        return 'creadas';
    }

    private function processTerreno($feature, $mapping, $categoria, $classifiedData)
    {
        $numero = $classifiedData['numero'];
        $cuadraRef = $classifiedData['cuadra_ref'];

        $cuadraId = $this->findCuadraIdByName($cuadraRef, $mapping);

        if (!$cuadraId) {
            throw new \Exception("No se encontró la cuadra '{$cuadraRef}' para el terreno '{$numero}'");
        }

        $cuadra = Cuadra::with('barrio')->findOrFail($cuadraId);

        $existing = Terreno::where('idcuadra', $cuadraId)
            ->where('numero_terreno', $numero)
            ->first();

        $poligono = $this->createPolygon($feature['geometry']['coordinates']);

        if ($existing) {
            if ($existing->poligono === null) {
                $existing->update(['poligono' => $poligono]);
            }
            return 'actualizados';
        }

        $ubicacion = "{$cuadra->barrio->nombre} {$cuadra->nombre} LT {$numero}";

        Terreno::create([
            'idproyecto' => $mapping['idproyecto'],
            'idcategoria' => $categoria->id,
            'idcuadra' => $cuadraId,
            'numero_terreno' => $numero,
            'ubicacion' => $ubicacion,
            'superficie' => '0',
            'cuota_inicial' => 0,
            'cuota_mensual' => 0,
            'precio_venta' => 0,
            'estado' => 0,
            'condicion' => true,
            'poligono' => $poligono,
        ]);

        return 'creados';
    }

    private function findBarrioIdByName($barrioName, $mapping)
    {
        if (isset($mapping['idbarrio'])) {
            return $mapping['idbarrio'];
        }

        if (!$barrioName || !isset($mapping['idproyecto'])) {
            return null;
        }

        $barrio = Barrio::where('idproyecto', $mapping['idproyecto'])
            ->where('nombre', $barrioName)
            ->first();

        return $barrio ? $barrio->id : null;
    }

    private function findCuadraIdByName($cuadraName, $mapping)
    {
        if (isset($mapping['idcuadra'])) {
            return $mapping['idcuadra'];
        }

        if (isset($mapping['cuadra_map']) && is_array($mapping['cuadra_map'])) {
            foreach ($mapping['cuadra_map'] as $key => $id) {
                if ($this->normalizeRef($key) === $this->normalizeRef($cuadraName)) {
                    return $id;
                }
            }
        }

        if (!$cuadraName || !isset($mapping['idproyecto'])) {
            return null;
        }

        $cuadra = Cuadra::whereHas('barrio', function ($query) use ($mapping) {
            $query->where('idproyecto', $mapping['idproyecto']);
            if (isset($mapping['idbarrio'])) {
                $query->where('id', $mapping['idbarrio']);
            }
        })->where('nombre', $cuadraName)->first();

        return $cuadra ? $cuadra->id : null;
    }

    private function normalizeRef($ref)
    {
        return strtoupper(preg_replace('/\s+/', '', trim($ref)));
    }

    private function createPolygon($coordinates)
    {
        $coords = $coordinates[0];
        $points = array_map(function ($coord) {
            $longitude = $coord[0];
            $latitude = $coord[1];

            return new Point($latitude, $longitude);
        }, $coords);

        return new Polygon([new LineString($points)]);
    }
}
