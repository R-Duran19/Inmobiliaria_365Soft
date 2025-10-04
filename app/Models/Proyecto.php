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
}
