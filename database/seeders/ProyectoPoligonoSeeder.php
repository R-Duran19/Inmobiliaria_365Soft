<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Proyecto;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Polygon;

class ProyectoPoligonoSeeder extends Seeder
{
    public function run()
    {
        $proyectoId = 1;

        try {
            // IMPORTANTE: Point(LONGITUD, LATITUD) - no al revés
            // Las coordenadas que proporcionaste están invertidas
            // Formato correcto: new Point(lng, lat)
            
            $polygon = new Polygon([
                new LineString([
                    new Point( -17.411405925370147,-63.40457300937503),  // lng, lat
                    new Point(-17.530432836878845,-63.40457300937503),
                    new Point(-17.530432836878845,-63.27000155082018),
                    new Point( -17.411405925370147,-63.27000155082018),
                    new Point(-17.411405925370147, -63.40457300937503),  // Cerrar el polígono
                ])
            ]);

            $proyecto = Proyecto::findOrFail($proyectoId);
            $proyecto->poligono = $polygon;
            $proyecto->save();

            $this->command->info('✅ Polígono del proyecto guardado correctamente');
            $this->command->info('📍 Proyecto ID: ' . $proyectoId);
            $this->command->info('📍 Nombre: ' . $proyecto->nombre);
            
            // Verificar que se guardó correctamente
            $proyecto->refresh();
            if ($proyecto->poligono) {
                $this->command->info('✅ Verificación: Polígono cargado desde BD');
                $geoJson = json_decode($proyecto->poligono->toJson(), true);
                $this->command->info('📐 Coordenadas: ' . json_encode($geoJson['coordinates'][0]));
            }
            
        } catch (\Exception $e) {
            $this->command->error('❌ Error al guardar polígono: ' . $e->getMessage());
            $this->command->error('Línea: ' . $e->getLine());
            $this->command->error('Archivo: ' . $e->getFile());
        }
    }
}