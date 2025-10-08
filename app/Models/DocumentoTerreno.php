<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoTerreno extends Model
{
    protected $table = 'documentos_terreno';

    protected $fillable = [
        'idterreno',
        'nombre_documento',
        'texto_extraido',
        'datos_extraidos',
        'estado_ocr',
    ];

    protected $casts = [
        'datos_extraidos' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function terreno()
    {
        return $this->belongsTo(Terreno::class, 'idterreno');
    }
}