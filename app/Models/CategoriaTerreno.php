<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriaTerreno extends Model
{
    use HasFactory;

    protected $table = 'categorias_terrenos';

   protected $fillable = ['nombre', 'idproyecto', 'estado', 'color'];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'idproyecto');
    }
    public function terrenos()
{
    return $this->hasMany(Terreno::class, 'idcategoria', 'id');
}
}