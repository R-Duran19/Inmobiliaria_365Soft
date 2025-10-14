<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class Barrio extends Model
{
    use HasSpatial;

    protected $fillable = [
        'idproyecto',
        'nombre',
        'poligono',
    ];

    protected $casts = [
        'poligono' => Polygon::class,
    ];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'idproyecto');
    }

    public function cuadras()
    {
        return $this->hasMany(Cuadra::class, 'idbarrio');
    }
}