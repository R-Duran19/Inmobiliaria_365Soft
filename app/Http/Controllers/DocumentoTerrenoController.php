<?php

namespace App\Http\Controllers;

use App\Models\DocumentoTerreno;
use Illuminate\Http\Request;

class DocumentoTerrenoController extends Controller
{
    public function index($idt_terreno)
    {
        return DocumentoTerreno::where('idt_terreno', $idt_terreno)->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'idt_terreno' => 'required|exists:terrenos,id',
            'nombre_documento' => 'required|string',
        ]);

        return DocumentoTerreno::create($request->all());
    }
}
