<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Moneda extends Model
{
    protected $table = 'monedas';

    protected $fillable = [
        'nombre',
        'pais',
        'abreviacion',
        'tipo_cambio',
        'activo',
    ];

    protected $casts = [
        'tipo_cambio' => 'decimal:2',
        'activo' => 'boolean',  
    ];

    public $timestamps = true;
}
