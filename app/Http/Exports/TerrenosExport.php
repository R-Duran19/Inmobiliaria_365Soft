<?php

namespace App\Exports;


use App\Models\Terreno;
use Maatwebsite\Excel\Concerns\FromCollection;

class TerrenosExport implements FromCollection
{
    public function collection()
    {
        return Terreno::all();
    }
}