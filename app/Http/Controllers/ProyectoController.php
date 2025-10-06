<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Illuminate\Support\Facades\Log;

class ProyectoController extends Controller
{
    public function index(Request $request)
    {
        $proyectos = Proyecto::query()
            ->when($request->search, function ($query, $search) {
                $query->where('nombre', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Proyectos', [
            'proyectos' => $proyectos,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_lanzamiento' => 'nullable|date',
            'numero_lotes' => 'nullable|integer',
            'ubicacion' => 'nullable|string',
            'fotografia' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->all();

        if ($request->hasFile('fotografia')) {

            $directory = public_path('fotos_proyectos');
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            $file = $request->file('fotografia');
            $filename = time() . '_' . $file->getClientOriginalName();
            
            $file->move($directory, $filename);
            
            $data['fotografia'] = 'fotos_proyectos/' . $filename;
        }

        Proyecto::create($data);
        return redirect()->route('proyectos')->with('success', 'Proyecto creado correctamente.');
    }

    public function update(Request $request, Proyecto $proyecto)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_lanzamiento' => 'nullable|date',
            'numero_lotes' => 'nullable|integer',
            'ubicacion' => 'nullable|string',
            'fotografia' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->only(['nombre', 'descripcion', 'fecha_lanzamiento', 'numero_lotes', 'ubicacion']);

        if ($request->hasFile('fotografia')) {
            if ($proyecto->fotografia) {
                $oldFile = public_path($proyecto->fotografia);
                if (File::exists($oldFile)) {
                    File::delete($oldFile);
                }
            }

            $directory = public_path('fotos_proyectos');
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            $file = $request->file('fotografia');
            $filename = time() . '_' . $file->getClientOriginalName();
            
            $file->move($directory, $filename);
            
            $data['fotografia'] = 'fotos_proyectos/' . $filename;
        } else {
            unset($data['fotografia']);
        }

        $proyecto->update($data);
        return redirect()->back()->with('success', 'Proyecto actualizado correctamente');
    }

    public function destroy(Proyecto $proyecto)
    {
        if ($proyecto->fotografia) {
            $file = public_path($proyecto->fotografia);
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        $proyecto->delete();

        return response()->json([
            'success' => true,
            'message' => 'Proyecto eliminado correctamente',
        ]);
    }

    public function desactivar(Proyecto $proyecto)
    {
        $proyecto->estado = false;
        $proyecto->save();

        return response()->json([
            'success' => true,
            'message' => 'Proyecto desactivado correctamente',
            'data' => $proyecto
        ]);
    }

    public function activar(Proyecto $proyecto)
    {
        $proyecto->estado = true;
        $proyecto->save();

        return response()->json([
            'success' => true,
            'message' => 'Proyecto activado correctamente',
            'data' => $proyecto
        ]);
    }

    public function descargarPlantilla()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Nombre del Proyecto');
        $sheet->setCellValue('B1', 'Descripción');
        $sheet->setCellValue('C1', 'Fecha de Lanzamiento');
        $sheet->setCellValue('D1', 'Número de Lotes');
        $sheet->setCellValue('E1', 'Ubicación');
        $sheet->setCellValue('F1', 'Estado (1=Activo, 0=Inactivo)');

        $headerStyle = [
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFD9D9D9'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'], 
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ];

        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(35);

        $writer = new Xlsx($spreadsheet);
        $filename = 'plantilla_proyectos.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|mimes:xlsx',
        ]);

        $file = $request->file('archivo');
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();

        $proyectos = [];
        foreach ($sheet->getRowIterator(2) as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $cells = [];
            foreach ($cellIterator as $cell) {
                $cells[] = $cell->getValue();
            }

            $fechaLanzamiento = null;
            if (!empty($cells[2])) {
                if (is_numeric($cells[2])) {
                    $fechaLanzamiento = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($cells[2])->format('Y-m-d');
                } else {
                    $fechaLanzamiento = $cells[2];
                }
            }

            $proyectos[] = [
                'nombre' => $cells[0] ?? 'Sin nombre',
                'descripcion' => $cells[1] ?? null,
                'fecha_lanzamiento' => $fechaLanzamiento,
                'numero_lotes' => $cells[3] ?? null,
                'ubicacion' => $cells[4] ?? null,
                'estado' => !empty($cells[5]) ? true : false,
            ];
        }

        foreach ($proyectos as $proyecto) {
            Proyecto::create($proyecto);
        }

        return response()->json([
            'success' => true,
            'message' => 'Proyectos importados correctamente.',
        ]);
    }

    public function exportar()
    {
        // Implementar lógica de exportación
        return response()->json(['message' => 'Funcionalidad de exportación en desarrollo.']);
    }

    public function apiIndex()
    {
        return Proyecto::select('id', 'nombre')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}