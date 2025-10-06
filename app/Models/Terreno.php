<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Terreno extends Model
{
    use HasFactory;

    protected $table = 'terrenos';

    protected $fillable = [
        'idproyecto',
        'ubicacion',
        'categoria',
        'superficie',
        'cuota_inicial',
        'cuota_mensual',
        'precio_venta',
        'estado',
        'condicion'
    ];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'idproyecto');
    }
}