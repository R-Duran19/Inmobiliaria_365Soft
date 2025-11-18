<?php

namespace App\Http\Controllers;

use App\Models\Terreno;
use App\Models\Venta;
use Inertia\Inertia;
use Illuminate\Http\Request;


class VentasController extends Controller
{
    public function show($id)
    {        
        $terreno = Terreno::select('id', 'idproyecto', 'cuota_inicial', 'cuota_mensual', 'precio_venta', 'ubicacion')
            ->with([
                'proyecto:id,nombre', 
            ])
            ->where('id', $id)
            ->firstOrFail();

        
        return Inertia::render('Ventas', [
            'terreno' => $terreno
        ]);
    }

public function postVenta(Request $request)
{
    $data = $request->validate([
        'idusuario'       => 'required|exists:users,id',
        'idcliente'       => 'required|exists:clientes,id',
        'idterreno'       => 'required|exists:terrenos,id',

        'precio_lista'    => 'required|numeric',
        'descuento'       => 'required|numeric',
        'precio_venta'    => 'required|numeric',
        'cuota_inicial'   => 'required|numeric',
        'total_plan_pago' => 'required|numeric',
    ]);

    // AQUÍ ESTABA EL ERROR
    $venta = Venta::create($data);

    return response()->json([
        'success' => true,
        'venta' => $venta
    ], 201);
}


    
}
