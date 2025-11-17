<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $fillable = [
        'idusuario',
        'idcliente',
        'idterreno',
        'precio_lista', 
        'descuento',
        'precio_venta',
        'cuota_inicial',
        'total_plan_pago',
    ];
    protected $casts = [
        'precio_lista' => 'decimal:2',
        'descuento' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'cuota_inicial' => 'decimal:2',
        'total_plan_pago' => 'decimal:2',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idusuario');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'idcliente');
    }

    public function terreno()
    {
        return $this->belongsTo(Terreno::class, 'idterreno');
    }
}
