<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proyecto extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'fecha_lanzamiento',
        'numero_lotes',
        'ubicacion',
        'fotografia',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
        'fecha_lanzamiento' => 'date',
    ];

    protected $attributes = [
        'estado' => true, 
    ];
    public function terrenos()
    {
        return $this->hasMany(Terreno::class, 'idproyecto');
    }

    /**
     * Terrenos disponibles
     */
    public function terrenosDisponibles()
    {
        return $this->hasMany(Terreno::class, 'idproyecto')
            ->where('estado', 0)
            ->where('condicion', true);
    }

    /**
     * Terrenos vendidos
     */
    public function terrenosVendidos()
    {
        return $this->hasMany(Terreno::class, 'idproyecto')
            ->where('estado', 1);
    }
}
