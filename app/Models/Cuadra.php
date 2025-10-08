<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuadra extends Model
{
    protected $fillable = [
        'idbarrio',
        'nombre',
    ];

    public function barrio()
    {
        return $this->belongsTo(Barrio::class, 'idbarrio');
    }
}
