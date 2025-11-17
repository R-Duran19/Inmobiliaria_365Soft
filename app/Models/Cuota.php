<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuota extends Model
{
    protected $fillable = [
        'idventa',
        'nro_cuota',
        'fecha_a_pagar',
        'valor_cuota', 
        'saldo',
        'fecha_pago',
        'estado',
        'mora',
    ];

    protected $casts = [
        'fecha_a_pagar' => 'date',
        'fecha_pago' => 'datetime',
        'valor_cuota' => 'decimal:2',
        'saldo' => 'decimal:2',
        'mora' => 'decimal:2',
        'estado' => 'boolean',
    ];


    public function venta()
    {
        return $this->belongsTo(Venta::class, 'idventa');
    }
}
