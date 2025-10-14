<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class Proyecto extends Model
{
    use HasFactory, HasSpatial;

    protected $fillable = [
        'nombre',
        'descripcion',
        'fecha_lanzamiento',
        'numero_lotes',
        'ubicacion',
        'fotografia',
        'estado',
        'poligono',
    ];

    protected $casts = [
        'estado' => 'boolean',
        'fecha_lanzamiento' => 'date',
        'poligono' => Polygon::class,
    ];

    protected $attributes = [
        'estado' => true, 
    ];
    public function terrenos()
    {
        return $this->hasMany(Terreno::class, 'idproyecto');
    }

    public function terrenosDisponibles()
    {
        return $this->hasMany(Terreno::class, 'idproyecto')
            ->where('estado', 0)
            ->where('condicion', true);
    }

    public function terrenosVendidos()
    {
        return $this->hasMany(Terreno::class, 'idproyecto')
            ->where('estado', 1);
    }
    public function barrios()
{
    return $this->hasMany(Barrio::class, 'idproyecto');
}
}