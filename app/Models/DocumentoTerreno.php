<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoTerreno extends Model
{
    protected $table = 'documentos_terreno';
    protected $fillable = ['idterreno', 'nombre_documento'];

    public function terreno()
    {
        return $this->belongsTo(Terreno::class, 'idterreno');
    }
}
