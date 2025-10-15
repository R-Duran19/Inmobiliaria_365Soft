<?php

namespace App\Http\Controllers;

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

class ImportJSONController extends Controller
{
    // Cache para optimizar búsquedas repetidas
    private $barrioCache = [];
    private $cuadraCache = [];

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
            Log::info('📊 Iniciando análisis de GeoJSON', [
                'total_features' => count($request->geojson['features']),
                'user_id' => auth()->id() ?? 'guest'
            ]);

            $features = $request->geojson['features'];
            $analysis = $this->analyzeFeatures($features);

            Log::info('✅ Análisis completado', [
                'import_type' => $analysis['import_type'],
                'valid' => $analysis['valid'],
                'proyecto' => $analysis['proyecto'] ? $analysis['proyecto']['nombre'] : null,
                'barrios' => $analysis['barrios']['count'],
                'cuadras' => $analysis['cuadras']['count'],
                'terrenos' => $analysis['terrenos']['count'],
                'errores' => count($analysis['errors'])
            ]);

            return response()->json([
                'success' => true,
                'analysis' => $analysis,
            ], 200);
        } catch (\Exception $e) {
            Log::error('❌ Error en análisis de GeoJSON', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

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
                Log::info('📋 Obteniendo proyectos disponibles', ['count' => $entities->count()]);
                break;

            case 'barrios':
                if ($request->idproyecto) {
                    $entities = Barrio::where('idproyecto', $request->idproyecto)
                        ->select('id', 'nombre')
                        ->get();
                    Log::info('📋 Obteniendo barrios disponibles', [
                        'idproyecto' => $request->idproyecto,
                        'count' => $entities->count()
                    ]);
                }
                break;

            case 'cuadras':
                if ($request->idbarrio) {
                    $entities = Cuadra::where('idbarrio', $request->idbarrio)
                        ->select('id', 'nombre')
                        ->get();
                    Log::info('📋 Obteniendo cuadras disponibles', [
                        'idbarrio' => $request->idbarrio,
                        'count' => $entities->count()
                    ]);
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
            Log::info('🔍 Detectando conflictos', [
                'idproyecto' => $request->mapping['idproyecto'] ?? null,
                'total_features' => count($request->geojson['features'])
            ]);

            $conflicts = $this->findConflicts($request->geojson['features'], $request->mapping);

            $totalConflicts =
                ($conflicts['proyecto'] ? 1 : 0) +
                count($conflicts['barrios']) +
                count($conflicts['cuadras']) +
                count($conflicts['terrenos']);

            Log::info('🔍 Conflictos detectados', [
                'total' => $totalConflicts,
                'proyecto' => $conflicts['proyecto'] ? 1 : 0,
                'barrios' => count($conflicts['barrios']),
                'cuadras' => count($conflicts['cuadras']),
                'terrenos' => count($conflicts['terrenos'])
            ]);

            return response()->json([
                'success' => true,
                'conflicts' => $conflicts,
            ], 200);
        } catch (\Exception $e) {
            Log::error('❌ Error al detectar conflictos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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
            Log::info('🚀 Iniciando importación', [
                'total_features' => count($request->geojson['features']),
                'idproyecto' => $request->mapping['idproyecto'] ?? null,
                'resolution' => $request->conflictResolution,
                'user_id' => auth()->id() ?? 'guest'
            ]);

            DB::beginTransaction();

            $result = $this->processImport(
                $request->geojson['features'],
                $request->mapping,
                $request->conflictResolution,
                $request->conflictDecisions ?? []
            );

            DB::commit();

            Log::info('✅ Importación completada exitosamente', [
                'summary' => $result
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Importación completada exitosamente',
                'summary' => $result,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ Error durante la importación', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'mapping' => $request->mapping
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error durante la importación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ========== MÉTODOS PRIVADOS ==========


    private function analyzeFeatures($features)
    {
        $analysis = [
            'total_features' => count($features),
            'proyecto' => null,
            'barrios' => ['count' => 0, 'items' => []],
            'cuadras' => ['count' => 0, 'items' => []],
            'terrenos' => ['count' => 0, 'items' => []],
            'import_type' => 'unknown',
            'errors' => [],
            'valid' => true,
        ];

        foreach ($features as $index => $feature) {
            $props = $feature['properties'];

            // CRÍTICO: Normalizar ANTES de clasificar
            $normalizedProps = $this->normalizeProperties($props);
            $classified = $this->classifyFeature($normalizedProps, $index);

            if ($classified['type'] === 'error') {
                $analysis['errors'][] = $classified['message'];
                $analysis['valid'] = false;

                Log::warning('⚠️ Feature con error detectado', [
                    'index' => $index,
                    'mensaje' => $classified['message']
                ]);
            } elseif ($classified['type'] === 'proyecto') {
                $analysis['proyecto'] = [
                    'nombre' => $classified['data']['nombre'],
                    'index' => $index
                ];

                Log::debug('📁 Proyecto detectado', [
                    'nombre' => $classified['data']['nombre']
                ]);
            } elseif ($classified['type'] === 'barrio') {
                $analysis['barrios']['count']++;
                $analysis['barrios']['items'][] = [
                    'nombre' => $classified['data']['nombre'],
                    'index' => $index
                ];

                Log::debug('🏘️ Barrio detectado', [
                    'nombre' => $classified['data']['nombre']
                ]);
            } elseif ($classified['type'] === 'cuadra') {
                $analysis['cuadras']['count']++;
                $analysis['cuadras']['items'][] = [
                    'nombre' => $classified['data']['nombre'],
                    'barrio' => $classified['data']['barrio_ref'],
                    'index' => $index
                ];

                Log::debug('🏗️ Cuadra detectada', [
                    'nombre' => $classified['data']['nombre'],
                    'barrio' => $classified['data']['barrio_ref']
                ]);
            } elseif ($classified['type'] === 'terreno') {
                $analysis['terrenos']['count']++;
                $analysis['terrenos']['items'][] = [
                    'numero' => $classified['data']['numero'],
                    'cuadra' => $classified['data']['cuadra_ref'],
                    'index' => $index
                ];

                Log::debug('🏠 Terreno detectado', [
                    'numero' => $classified['data']['numero'],
                    'cuadra' => $classified['data']['cuadra_ref']
                ]);
            }
        }

        // Determinar tipo de importación
        $analysis['import_type'] = $this->determineImportType($analysis);

        Log::info('📊 Resumen de análisis', [
            'tipo' => $analysis['import_type'],
            'válido' => $analysis['valid'],
            'proyecto' => $analysis['proyecto'] ? 'Sí' : 'No',
            'barrios' => $analysis['barrios']['count'],
            'cuadras' => $analysis['cuadras']['count'],
            'terrenos' => $analysis['terrenos']['count'],
            'errores' => count($analysis['errors'])
        ]);

        return $analysis;
    }

    private function normalizeProperties($props)
    {
        $normalized = [];

        foreach ($props as $key => $value) {
            // 1. NORMALIZAR LA CLAVE
            // Quitar TODOS los espacios y convertir a mayúsculas
            $keyClean = strtoupper(trim(preg_replace('/\s+/', '', $key)));

            // Detectar patrón UV seguido de números (con cualquier cantidad de dígitos)
            if (preg_match('/^UV(\d+)$/i', $keyClean, $matches)) {
                $uvNumber = str_pad($matches[1], 3, '0', STR_PAD_LEFT);
                $normalizedKey = 'UV ' . $uvNumber; // CON ESPACIO
            }
            // Detectar patrón MZ seguido de números (con cualquier cantidad de dígitos)
            elseif (preg_match('/^MZ(\d+)$/i', $keyClean, $matches)) {
                $mzNumber = str_pad($matches[1], 3, '0', STR_PAD_LEFT);
                $normalizedKey = 'MZ ' . $mzNumber; // CON ESPACIO
            } else {
                $normalizedKey = $keyClean;
            }

            // 2. NORMALIZAR EL VALOR
            $normalizedValue = is_string($value) ? strtoupper(trim($value)) : $value;

            // Si el valor parece ser una manzana, estandarizarla
            if (is_string($normalizedValue)) {
                $valueClean = preg_replace('/\s+/', '', $normalizedValue);
                if (preg_match('/^MZ(\d+)$/i', $valueClean, $matches)) {
                    $mzNumber = str_pad($matches[1], 3, '0', STR_PAD_LEFT);
                    $normalizedValue = 'MZ ' . $mzNumber; // CON ESPACIO
                }
                // Si el valor es solo un número (terreno), hacer padding a 3 dígitos
                elseif (preg_match('/^\d+$/', $valueClean)) {
                    $normalizedValue = str_pad($valueClean, 3, '0', STR_PAD_LEFT);
                }
            }

            $normalized[$normalizedKey] = $normalizedValue;
        }

        Log::debug('🔧 Propiedades normalizadas', [
            'original' => array_keys($props),
            'normalizado' => $normalized
        ]);

        return $normalized;
    }


    /**
     * Clasificar feature según sus propiedades normalizadas
     */
    private function classifyFeature($props, $index)
    {
        Log::debug('🔍 Clasificando feature', [
            'index' => $index,
            'props' => $props
        ]);

        // PATRÓN 1: PROYECTO
        foreach ($props as $key => $value) {
            if ($key === 'PROYECTO' && !empty($value)) {
                Log::debug('✅ Proyecto detectado', ['nombre' => $value]);
                return [
                    'type' => 'proyecto',
                    'data' => [
                        'nombre' => $value,
                        'index' => $index,
                    ]
                ];
            }
        }

        // PATRÓN 2: MZ XXX -> número (TERRENO)
        // Ejemplo: "MZ 001" => "005"
        foreach ($props as $key => $value) {
            if (preg_match('/^MZ\s\d{3}$/i', $key)) {
                Log::debug('✅ Terreno detectado', [
                    'cuadra' => $key,
                    'numero' => $value
                ]);
                return [
                    'type' => 'terreno',
                    'data' => [
                        'numero' => $value,
                        'cuadra_ref' => $key,
                        'index' => $index,
                    ]
                ];
            }
        }

        // PATRÓN 3: UV XXX -> MZ XXX (CUADRA)
        // Ejemplo: "UV 002" => "MZ 001"
        foreach ($props as $key => $value) {
            if (preg_match('/^UV\s\d{3}$/i', $key) && preg_match('/^MZ\s\d{3}$/i', $value)) {
                Log::debug('✅ Cuadra detectada', [
                    'barrio' => $key,
                    'cuadra' => $value
                ]);
                return [
                    'type' => 'cuadra',
                    'data' => [
                        'nombre' => $value,
                        'barrio_ref' => $key,
                        'index' => $index,
                    ]
                ];
            }
        }

        // PATRÓN 4: Solo UV XXX (BARRIO)
        // Ejemplo: "UV 002" => "ALGO QUE NO ES MZ"
        foreach ($props as $key => $value) {
            if (preg_match('/^UV\s\d{3}$/i', $key) && !preg_match('/^MZ\s\d{3}$/i', $value)) {
                Log::debug('✅ Barrio detectado', [
                    'nombre' => $key
                ]);
                return [
                    'type' => 'barrio',
                    'data' => [
                        'nombre' => $key,
                        'index' => $index,
                    ]
                ];
            }
        }

        // PATRÓN 5: Fallback formato antiguo
        Log::debug('⚠️ Usando clasificación legacy');
        return $this->classifyFeatureLegacy($props, $index);
    }

    /**
     * Clasificación por formato antiguo (compatibilidad)
     */
    private function classifyFeatureLegacy($props, $index)
    {
        Log::debug('🔄 Clasificación legacy', [
            'props' => array_keys($props)
        ]);

        $barrioKey = null;
        $cuadraKey = null;
        $numeroKey = null;

        foreach ($props as $key => $value) {
            $keyUpper = strtoupper(trim($key));
            $valueStr = is_string($value) ? trim($value) : '';

            // Buscar "CUADRA" con valor MZ normalizado (con espacio)
            if ($keyUpper === 'CUADRA' && preg_match('/^MZ\s\d{3}$/i', $valueStr)) {
                $cuadraKey = $key;
            }

            // Buscar "BARRIO" con valor UV normalizado (con espacio)
            if ($keyUpper === 'BARRIO' && preg_match('/^UV\s\d{3}$/i', $valueStr)) {
                $barrioKey = $key;
            }

            // Buscar número de terreno
            if (in_array($keyUpper, ['NUMERO', 'LOTE', 'TERRENO', 'LT', 'NUM'])) {
                $numeroKey = $key;
            }
        }

        // TERRENO detectado
        if ($numeroKey && isset($props[$numeroKey])) {
            Log::debug('✅ Terreno detectado (legacy)', [
                'numero' => $props[$numeroKey]
            ]);
            return [
                'type' => 'terreno',
                'data' => [
                    'numero' => $props[$numeroKey],
                    'cuadra_ref' => $cuadraKey ? $props[$cuadraKey] : null,
                    'index' => $index,
                ]
            ];
        }

        // CUADRA detectada
        if ($cuadraKey) {
            Log::debug('✅ Cuadra detectada (legacy)', [
                'cuadra' => $props[$cuadraKey]
            ]);
            return [
                'type' => 'cuadra',
                'data' => [
                    'nombre' => $props[$cuadraKey],
                    'barrio_ref' => $barrioKey ? $props[$barrioKey] : null,
                    'index' => $index,
                ]
            ];
        }

        // BARRIO detectado
        if ($barrioKey && isset($props[$barrioKey])) {
            Log::debug('✅ Barrio detectado (legacy)', [
                'barrio' => $props[$barrioKey]
            ]);
            return [
                'type' => 'barrio',
                'data' => [
                    'nombre' => $props[$barrioKey],
                    'index' => $index,
                ]
            ];
        }

        // ERROR: No se reconoció ningún patrón
        Log::error('❌ No se detectó patrón válido', [
            'index' => $index,
            'props' => $props
        ]);

        return [
            'type' => 'error',
            'message' => "Feature #{$index}: No se detectó patrón válido. Propiedades disponibles: " .
                implode(', ', array_map(function ($k, $v) {
                    return "$k => $v";
                }, array_keys($props), $props))
        ];
    }
    /**
     * Determinar el tipo de importación según lo detectado
     */
    private function determineImportType($analysis)
    {
        if ($analysis['proyecto']) {
            return 'proyecto_completo';
        }

        if (
            $analysis['barrios']['count'] > 0 &&
            $analysis['cuadras']['count'] > 0 &&
            $analysis['terrenos']['count'] > 0
        ) {
            return 'mixto';
        }

        if ($analysis['barrios']['count'] > 0) {
            return 'barrios';
        }

        if ($analysis['cuadras']['count'] > 0) {
            return 'cuadras';
        }

        if ($analysis['terrenos']['count'] > 0) {
            return 'terrenos';
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

        Log::info('📦 Procesando features', [
            'total' => count($features),
            'categoria_default' => $categoriaDefault->id
        ]);

        // Primer paso: procesar proyecto si existe
        foreach ($features as $index => $feature) {
            $props = $feature['properties'];
            $normalizedProps = $this->normalizeProperties($props);
            $classified = $this->classifyFeature($normalizedProps, $index);

            if ($classified['type'] === 'proyecto') {
                $hasConflict = $this->hasConflict($index, $feature, $mapping);

                $shouldSkip = false;
                if ($hasConflict && $conflictResolution === 'skip_all') {
                    $shouldSkip = true;
                }

                if ($shouldSkip) {
                    $summary['proyecto']['omitido'] = true;
                    Log::info('⏭️ Proyecto omitido por conflicto', [
                        'nombre' => $classified['data']['nombre']
                    ]);
                } else {
                    $result = $this->processProyecto($feature, $mapping, $classified['data']);
                    if ($result === 'creado') {
                        $summary['proyecto']['creado'] = true;
                        $mapping['idproyecto'] = Proyecto::where('nombre', $classified['data']['nombre'])->first()->id;
                        Log::info('✅ Proyecto creado', [
                            'nombre' => $classified['data']['nombre'],
                            'id' => $mapping['idproyecto']
                        ]);
                    } else {
                        $summary['proyecto']['actualizado'] = true;
                        Log::info('🔄 Proyecto actualizado', [
                            'nombre' => $classified['data']['nombre']
                        ]);
                    }
                }
            }
        }

        // Preparar arrays para bulk insert (optimización)
        $terrenosBulk = [];

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
                    Log::debug('⏭️ Barrio omitido', ['nombre' => $classified['data']['nombre']]);
                } elseif ($classified['type'] === 'cuadra') {
                    $summary['cuadras']['omitidas']++;
                    Log::debug('⏭️ Cuadra omitida', ['nombre' => $classified['data']['nombre']]);
                } elseif ($classified['type'] === 'terreno') {
                    $summary['terrenos']['omitidos']++;
                    Log::debug('⏭️ Terreno omitido', ['numero' => $classified['data']['numero']]);
                }
                continue;
            }

            if ($classified['type'] === 'barrio') {
                $result = $this->processBarrio($feature, $mapping, $classified['data']);
                $summary['barrios'][$result]++;
                Log::debug('🏘️ Barrio procesado', [
                    'nombre' => $classified['data']['nombre'],
                    'resultado' => $result
                ]);
            } elseif ($classified['type'] === 'cuadra') {
                $result = $this->processCuadra($feature, $mapping, $classified['data']);
                $summary['cuadras'][$result]++;
                Log::debug('🏗️ Cuadra procesada', [
                    'nombre' => $classified['data']['nombre'],
                    'resultado' => $result
                ]);
            } elseif ($classified['type'] === 'terreno') {
                $result = $this->processTerreno($feature, $mapping, $categoriaDefault, $classified['data']);
                $summary['terrenos'][$result]++;
                Log::debug('🏠 Terreno procesado', [
                    'numero' => $classified['data']['numero'],
                    'resultado' => $result
                ]);
            }
        }

        Log::info('📊 Resumen final de importación', $summary);

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

        // Solo actualiza proyectos existentes, NO crea nuevos
        if (!$existing) {
            throw new \Exception("Proyecto '{$nombre}' no encontrado en la base de datos. Solo se pueden actualizar proyectos existentes.");
        }

        // Si existe y no tiene polígono, actualizar
        if ($existing->poligono === null) {
            $poligono = $this->createPolygon($feature['geometry']['coordinates']);
            $existing->update(['poligono' => $poligono]);
            Log::info('🔄 Polígono de proyecto actualizado', [
                'proyecto' => $nombre,
                'id' => $existing->id
            ]);
            return 'actualizado';
        }

        // Si existe y ya tiene polígono, dejar igual
        Log::debug('⏭️ Proyecto ya tiene polígono', ['proyecto' => $nombre]);
        return 'actualizado';
    }

    /**
     * Procesar barrio: crear o actualizar
     */
    private function processBarrio($feature, $mapping, $classifiedData)
    {
        $nombre = $classifiedData['nombre']; // Ya viene normalizado como UV001, UV002, etc.
        $idProyecto = $mapping['idproyecto'];

        // Buscar barrio existente (comparación estricta con nombre normalizado)
        $existing = Barrio::where('idproyecto', $idProyecto)
            ->where('nombre', $nombre)
            ->first();

        $poligono = $this->createPolygon($feature['geometry']['coordinates']);

        if ($existing) {
            if ($existing->poligono === null) {
                $existing->update(['poligono' => $poligono]);
                Log::info('🔄 Polígono de barrio actualizado', [
                    'barrio' => $nombre,
                    'id' => $existing->id
                ]);
            } else {
                Log::debug('⏭️ Barrio ya tiene polígono', ['barrio' => $nombre]);
            }
            return 'actualizados';
        }

        $barrio = Barrio::create([
            'idproyecto' => $idProyecto,
            'nombre' => $nombre,
            'poligono' => $poligono,
        ]);

        Log::info('✅ Barrio creado', [
            'barrio' => $nombre,
            'id' => $barrio->id,
            'idproyecto' => $idProyecto
        ]);

        return 'creados';
    }

    /**
     * Procesar cuadra: crear o actualizar
     */
    private function processCuadra($feature, $mapping, $classifiedData)
    {
        $nombre = $classifiedData['nombre'];         // Ya normalizado: MZ001, MZ002, etc.
        $barrioRef = $classifiedData['barrio_ref']; // Ya normalizado: UV001, UV002, etc.

        $barrioId = $this->findBarrioIdByName($barrioRef, $mapping);

        if (!$barrioId) {
            throw new \Exception("No se encontró el barrio '{$barrioRef}' para la cuadra '{$nombre}'");
        }

        // Buscar cuadra existente (comparación estricta con nombre normalizado)
        $existing = Cuadra::where('idbarrio', $barrioId)
            ->where('nombre', $nombre)
            ->first();

        $poligono = $this->createPolygon($feature['geometry']['coordinates']);

        if ($existing) {
            if ($existing->poligono === null) {
                $existing->update(['poligono' => $poligono]);
                Log::info('🔄 Polígono de cuadra actualizado', [
                    'cuadra' => $nombre,
                    'id' => $existing->id
                ]);
            } else {
                Log::debug('⏭️ Cuadra ya tiene polígono', ['cuadra' => $nombre]);
            }
            return 'actualizadas';
        }

        $cuadra = Cuadra::create([
            'idbarrio' => $barrioId,
            'nombre' => $nombre,
            'poligono' => $poligono,
        ]);

        Log::info('✅ Cuadra creada', [
            'cuadra' => $nombre,
            'id' => $cuadra->id,
            'idbarrio' => $barrioId
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
                Log::info('🔄 Polígono de terreno actualizado', [
                    'terreno' => $numero,
                    'id' => $existing->id
                ]);
            } else {
                Log::debug('⏭️ Terreno ya tiene polígono', ['terreno' => $numero]);
            }
            return 'actualizados';
        }

        $ubicacion = "{$cuadra->barrio->nombre} {$cuadra->nombre} LT {$numero}";

        // ========== PROPIEDADES ADICIONALES (COMENTADO PARA USO FUTURO) ==========
        // Descomenta estas líneas cuando quieras usar propiedades adicionales del GeoJSON
        // $props = $feature['properties'];
        // $superficie = $props['superficie'] ?? '0';
        // $precio = $props['precio'] ?? 0;
        // $categoria_nombre = $props['categoria'] ?? null;
        // 
        // // Buscar categoría por nombre si viene en el GeoJSON
        // if ($categoria_nombre) {
        //     $categoriaCustom = CategoriaTerreno::where('nombre', $categoria_nombre)->first();
        //     $categoria = $categoriaCustom ?? $categoria;
        // }
        // ========================================================================

        $terreno = Terreno::create([
            'idproyecto' => $mapping['idproyecto'],
            'idcategoria' => $categoria->id,
            'idcuadra' => $cuadraId,
            'numero_terreno' => $numero,
            'ubicacion' => $ubicacion,
            'superficie' => '0', // Cambiar a $superficie cuando uses props adicionales
            'cuota_inicial' => 0,
            'cuota_mensual' => 0,
            'precio_venta' => 0,  // Cambiar a $precio cuando uses props adicionales
            'estado' => 0,
            'condicion' => true,
            'poligono' => $poligono,
        ]);

        Log::info('✅ Terreno creado', [
            'terreno' => $numero,
            'id' => $terreno->id,
            'idcuadra' => $cuadraId,
            'ubicacion' => $ubicacion
        ]);

        return 'creados';
    }

    /**
     * Buscar ID de barrio por nombre (con cache para optimización)
     */
    /**
     * Buscar ID de barrio por nombre (con normalización y cache)
     */
    private function findBarrioIdByName($barrioName, $mapping)
    {
        // Si viene directamente en el mapping, usar ese
        if (isset($mapping['idbarrio'])) {
            return $mapping['idbarrio'];
        }

        if (!$barrioName || !isset($mapping['idproyecto'])) {
            return null;
        }

        // Normalizar la referencia (ya debería venir normalizada, pero por si acaso)
        $normalizedName = $this->normalizeRef($barrioName);

        // Revisar cache primero
        $cacheKey = "{$mapping['idproyecto']}:{$normalizedName}";
        if (isset($this->barrioCache[$cacheKey])) {
            Log::debug('💾 Barrio obtenido del cache', ['key' => $cacheKey]);
            return $this->barrioCache[$cacheKey];
        }

        // Buscar en la base de datos (comparación estricta)
        $barrio = Barrio::where('idproyecto', $mapping['idproyecto'])
            ->where('nombre', $normalizedName)
            ->first();

        // Guardar en cache
        $this->barrioCache[$cacheKey] = $barrio ? $barrio->id : null;

        if ($barrio) {
            Log::debug('🔍 Barrio encontrado en BD', [
                'nombre_buscado' => $barrioName,
                'nombre_normalizado' => $normalizedName,
                'id' => $barrio->id
            ]);
        } else {
            Log::warning('⚠️ Barrio NO encontrado en BD', [
                'nombre_buscado' => $barrioName,
                'nombre_normalizado' => $normalizedName,
                'proyecto' => $mapping['idproyecto']
            ]);
        }

        return $barrio ? $barrio->id : null;
    }
    /**
     * Buscar ID de cuadra por nombre (con cache para optimización)
     */
    /**
     * Buscar ID de cuadra por nombre (con normalización y cache)
     */
    private function findCuadraIdByName($cuadraName, $mapping)
    {
        // Si viene directamente en el mapping, usar ese
        if (isset($mapping['idcuadra'])) {
            return $mapping['idcuadra'];
        }

        // Si hay mapeo manual de cuadras
        if (isset($mapping['cuadra_map']) && is_array($mapping['cuadra_map'])) {
            $normalized = $this->normalizeRef($cuadraName);
            foreach ($mapping['cuadra_map'] as $key => $id) {
                if ($this->normalizeRef($key) === $normalized) {
                    Log::debug('📋 Cuadra obtenida del mapping manual', [
                        'key' => $key,
                        'id' => $id
                    ]);
                    return $id;
                }
            }
        }

        if (!$cuadraName || !isset($mapping['idproyecto'])) {
            return null;
        }

        // Normalizar la referencia (ya debería venir normalizada, pero por si acaso)
        $normalizedName = $this->normalizeRef($cuadraName);

        // Revisar cache primero
        $barrioSuffix = isset($mapping['idbarrio']) ? ":{$mapping['idbarrio']}" : '';
        $cacheKey = "{$mapping['idproyecto']}{$barrioSuffix}:{$normalizedName}";

        if (isset($this->cuadraCache[$cacheKey])) {
            Log::debug('💾 Cuadra obtenida del cache', ['key' => $cacheKey]);
            return $this->cuadraCache[$cacheKey];
        }

        // Buscar en la base de datos (comparación estricta)
        $query = Cuadra::whereHas('barrio', function ($q) use ($mapping) {
            $q->where('idproyecto', $mapping['idproyecto']);
            if (isset($mapping['idbarrio'])) {
                $q->where('id', $mapping['idbarrio']);
            }
        });

        $cuadra = $query->where('nombre', $normalizedName)->first();

        // Guardar en cache
        $this->cuadraCache[$cacheKey] = $cuadra ? $cuadra->id : null;

        if ($cuadra) {
            Log::debug('🔍 Cuadra encontrada en BD', [
                'nombre_buscado' => $cuadraName,
                'nombre_normalizado' => $normalizedName,
                'id' => $cuadra->id
            ]);
        } else {
            Log::warning('⚠️ Cuadra NO encontrada en BD', [
                'nombre_buscado' => $cuadraName,
                'nombre_normalizado' => $normalizedName,
                'proyecto' => $mapping['idproyecto']
            ]);
        }

        return $cuadra ? $cuadra->id : null;
    }
    /**
     * Normalizar referencia para comparación y búsqueda en BD
     * MANTIENE EL ESPACIO para coincidir con el formato de la BD
     * Ejemplos:
     * - "MZ01" -> "MZ 001"
     * - "MZ 1" -> "MZ 001"
     * - "MZ001" -> "MZ 001"
     * - "UV5" -> "UV 005"
     * - "UV005" -> "UV 005"
     */
    private function normalizeRef($ref)
    {
        if (empty($ref)) {
            return '';
        }

        // Convertir a mayúsculas y quitar espacios
        $normalized = strtoupper(preg_replace('/\s+/', '', trim($ref)));

        // Si tiene formato UV o MZ seguido de números, normalizar CON ESPACIO
        if (preg_match('/^(UV|MZ)(\d+)$/i', $normalized, $matches)) {
            $prefix = $matches[1];
            $number = $matches[2];
            // Hacer padding a 3 dígitos CON ESPACIO
            return $prefix . ' ' . str_pad($number, 3, '0', STR_PAD_LEFT);
        }

        return $normalized;
    }
    /**
     * Crear polígono a partir de coordenadas GeoJSON (mejora 3)
     * Soporta Polygon y MultiPolygon
     */
    private function createPolygon($coordinates)
    {
        // Si es MultiPolygon, tomar el primer polígono
        // MultiPolygon tiene estructura: [[[lng, lat], ...], [[lng, lat], ...]]
        // Polygon tiene estructura: [[lng, lat], ...]
        if (isset($coordinates[0][0][0]) && is_array($coordinates[0][0][0])) {
            Log::debug('🗺️ MultiPolygon detectado, usando primer polígono');
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
}
