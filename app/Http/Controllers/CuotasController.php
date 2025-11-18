<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cuota;

class CuotasController extends Controller
{
 /**
  * Store a newly created cuota(s) in storage.
  *
  * @param  \Illuminate\Http\Request  $request
  * @return \Illuminate\Http\JsonResponse
  */
 public function postCuotas(Request $request)
{
        $cuotasData = $request->json()->all();
        
        if (is_array($cuotasData) && count($cuotasData) > 0 && isset($cuotasData[0])) {
        
        $request->validate([
            '*.idventa' => 'required|integer|exists:ventas,id',
            '*.nro_cuota' => 'required|integer|min:1',
            '*.fecha_a_pagar' => 'required|date',
            '*.valor_cuota' => 'required|numeric|min:0',
            '*.saldo' => 'nullable|numeric|min:0', 
            '*.fecha_pago' => 'nullable|date', 
            '*.estado' => 'required|integer|min:0',
            '*.mora' => 'nullable|numeric|min:0',
        ]);
                    
                    
                    $now = now();
                    $dataToInsert = collect($cuotasData)->map(function ($item) use ($now) {
                        return array_merge($item, [
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    })->all();


        Cuota::insert($dataToInsert);

        return response()->json([
            'message' => 'Cuotas creadas con éxito',
            'count' => count($cuotasData)
        ], 201);
        } else {
        
        $request->validate([
            'idventa' => 'required|integer|exists:ventas,id',
            'nro_cuota' => 'required|integer|min:1',
            'fecha_a_pagar' => 'required|date',
            'valor_cuota' => 'required|numeric|min:0',
            'saldo' => 'nullable|numeric|min:0', 
            'fecha_pago' => 'nullable|date', 
            'estado' => 'required|integer|min:0',
            'mora' => 'nullable|numeric|min:0',
        ]);

        
        $cuota = Cuota::create($cuotasData);
        return response()->json([
            'message' => 'Cuota creada con éxito',
            'cuotas' => $cuota
        ], 201);
        }
    }
}