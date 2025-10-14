<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class Cuadra extends Model
{
    use HasSpatial;

    protected $fillable = [
        'idbarrio',
        'nombre',
        'poligono',
    ];

    protected $casts = [
        'poligono' => Polygon::class,
    ];

    public function barrio()
    {
        return $this->belongsTo(Barrio::class, 'idbarrio');
    }

    public function terrenos()
    {
        return $this->hasMany(Terreno::class, 'idcuadra');
    }
}