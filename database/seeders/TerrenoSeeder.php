<?php

namespace Database\Seeders;

use App\Models\Terreno;
use Illuminate\Database\Seeder;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Polygon;

class TerrenoSeeder extends Seeder
{
    public function run(): void
    {
        // Terreno 1 - Polígono grande principal
        Terreno::create([
            'idproyecto' => 1,
            'ubicacion' => 'Zona Norte',
            'categoria' => 'Premium',
            'superficie' => '500 m²',
            'cuota_inicial' => 10000.00,
            'cuota_mensual' => 500.00,
            'precio_venta' => 50000.00,
            'estado' => 1,
            'condicion' => true,
            'poligono' => new Polygon([
                new LineString([
                    new Point(-66.31740442630671, -17.38694906145689),
                    new Point(-66.31733479061435, -17.38693694974542),
                    new Point(-66.3171907146749, -17.38776190523498),
                    new Point(-66.31681212582876, -17.387763001406356),
                    new Point(-66.3168358178476, -17.387943876459218),
                    new Point(-66.3163856694846, -17.38787604833506),
                    new Point(-66.31631459342687, -17.38887085829728),
                    new Point(-66.31610136525477, -17.38887085829728),
                    new Point(-66.31593552112098, -17.389933490236885),
                    new Point(-66.31351893517093, -17.38900651378289),
                    new Point(-66.31356631920924, -17.386564699666295),
                    new Point(-66.31337678305601, -17.3836254357539),
                    new Point(-66.31349524315146, -17.380324360005318),
                    new Point(-66.31527214458589, -17.3803695806227),
                    new Point(-66.31508260843266, -17.382698427343414),
                    new Point(-66.31678843380934, -17.382788867395163),
                    new Point(-66.31631459342687, -17.386858623461094),
                    new Point(-66.31740442630671, -17.38694906145689),
                ])
            ])
        ]);

        // Terreno 2 - Polígono pequeño superior
        Terreno::create([
            'idproyecto' => 1,
            'ubicacion' => 'Zona Norte',
            'categoria' => 'Estándar',
            'superficie' => '150 m²',
            'cuota_inicial' => 8000.00,
            'cuota_mensual' => 400.00,
            'precio_venta' => 40000.00,
            'estado' => 1,
            'condicion' => true,
            'poligono' => new Polygon([
                new LineString([
                    new Point(-66.31826261171622, -17.389030348110552),
                    new Point(-66.31903428925669, -17.389260476089788),
                    new Point(-66.31893093958617, -17.38971415611509),
                    new Point(-66.31810414222127, -17.389503753925098),
                    new Point(-66.31826261171622, -17.389030348110552),
                ])
            ])
        ]);

        Terreno::create([
            'idproyecto' => 1,
            'ubicacion' => 'Zona Sur',
            'categoria' => 'Económico',
            'superficie' => '80 m²',
            'cuota_inicial' => 5000.00,
            'cuota_mensual' => 300.00,
            'precio_venta' => 30000.00,
            'estado' => 1,
            'condicion' => true,
            'poligono' => new Polygon([
                new LineString([
                    new Point(-66.31493701282875, -17.391272941182336),
                    new Point(-66.31493407318007, -17.391525414863622),
                    new Point(-66.3149046766946, -17.391564688516624),
                    new Point(-66.3144490311689, -17.391575909557872),
                    new Point(-66.3143461434697, -17.39147772541692),
                    new Point(-66.31434908311776, -17.391343072795422),
                    new Point(-66.31441963468343, -17.391236472733098),
                    new Point(-66.31487528020912, -17.391236472733098),
                    new Point(-66.31493701282875, -17.391272941182336),
                ])
            ])
        ]);
    }
}