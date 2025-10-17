<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class Terreno extends Model
{
    use HasSpatial;

    protected $fillable = [
        'idproyecto',
        'idcategoria',
        'idcuadra',
        'numero_terreno', 
        'ubicacion',
        'categoria',
        'superficie',
        'cuota_inicial',
        'cuota_mensual',
        'precio_venta',
        'estado',
        'condicion',
        'poligono',
        'observaciones',
    ];

    protected $casts = [
        'cuota_inicial' => 'decimal:2',
        'cuota_mensual' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'condicion' => 'boolean',
        'poligono' => Polygon::class, // Convierte automáticamente a objeto Polygon
    ];

    // Relación con proyecto
    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'idproyecto');
    }

    // Relación con categoría
    public function categorias_terrenos()
    {
        return $this->belongsTo(CategoriaTerreno::class, 'idcategoria');
    }

    // Relación con cuadra
    public function cuadra()
    {
        return $this->belongsTo(Cuadra::class, 'idcuadra');
    }

    // Accessor para obtener el polígono como GeoJSON
    public function getPoligonoGeojsonAttribute()
    {
        if (!$this->poligono) {
            return null;
        }

        return json_decode($this->poligono->toJson());
    }
    public function barrio()
    {
        return $this->belongsTo(Barrio::class, 'idbarrio');
    }
}