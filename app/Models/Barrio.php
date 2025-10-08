<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barrio extends Model
{
    protected $fillable = [
        'idproyecto',
        'nombre',
    ];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'idproyecto');
    }
}
