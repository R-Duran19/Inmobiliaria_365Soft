<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $fillable = [
        'nombre',
        'apellido_materno',
        'apellido_paterno',
        'telefono', 
        'telefono_referencia',
        'direccion',
        'ci',
        'codigo_carnet',
    ];

}
