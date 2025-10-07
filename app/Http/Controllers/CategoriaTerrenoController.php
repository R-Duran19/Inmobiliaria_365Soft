<?php
namespace App\Http\Controllers;

use App\Models\CategoriaTerreno;
use App\Models\Proyecto;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Illuminate\Support\Facades\Log;

class CategoriaTerrenoController extends Controller
{
    public function index()
    {
        // Obtener todas las categorías con su proyecto relacionado
        $categorias = CategoriaTerreno::with('proyecto')->get();
        return response()->json([
            'success' => true,
            'data' => $categorias
        ]);
    }

    public function store(Request $request)
    {
        // Validación de los datos
        $request->validate([
            'nombre' => 'required|string|max:255',
            'idproyecto' => 'required|exists:proyectos,id',
            'estado' => 'sometimes|boolean',
            'color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
        ]);

        // Crear la nueva categoría
        $categoria = CategoriaTerreno::create([
            'nombre' => $request->nombre,
            'idproyecto' => $request->idproyecto,
            'estado' => $request->estado ?? true,
            'color' => $request->color,
        ]);

        // Retornar respuesta JSON
        return response()->json([
            'success' => true,
            'message' => 'Categoría creada correctamente',
            'data' => $categoria
        ]);
    }

    public function update(Request $request, $id)
    {
        // Buscar la categoría por id
        $categoria = CategoriaTerreno::findOrFail($id);

        // Validación de los datos
        $request->validate([
            'nombre' => 'required|string|max:255',
            'idproyecto' => 'required|exists:proyectos,id',
            'estado' => 'sometimes|boolean',
            'color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
        ]);

        // Actualizar los campos
        $categoria->update([
            'nombre' => $request->nombre,
            'idproyecto' => $request->idproyecto,
            'estado' => $request->estado ?? $categoria->estado,
            'color' => $request->color,
        ]);

        // Retornar respuesta JSON
        return response()->json([
            'success' => true,
            'message' => 'Categoría actualizada correctamente',
            'data' => $categoria
        ]);
    }

    public function destroy($id)
    {
        $categoria = CategoriaTerreno::findOrFail($id);
        $categoria->delete();
        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada correctamente',
        ]);
    }

    public function desactivar($id)
    {
        // Buscar la categoría por id
        $categoria = CategoriaTerreno::findOrFail($id);
        // Cambiar estado a false
        $categoria->estado = false;
        $categoria->save();
        // Retornar respuesta JSON
        return response()->json([
            'success' => true,
            'message' => 'Categoría desactivada correctamente',
            'data' => $categoria
        ]);
    }

    public function activar($id)
    {
        // Buscar la categoría por id
        $categoria = CategoriaTerreno::findOrFail($id);

        // Cambiar estado a true
        $categoria->estado = true;
        $categoria->save();

        // Retornar respuesta JSON
        return response()->json([
            'success' => true,
            'message' => 'Categoría activada correctamente',
            'data' => $categoria
        ]);
    }

    public function proyectos()
    {
        $proyectos = Proyecto::where('estado', 1)->get(['id', 'nombre'])->toArray();
        return response()->json([
            'success' => true,
            'data' => $proyectos
        ]);
    }

    public function porProyecto($id)
    {
        $categorias = CategoriaTerreno::with('proyecto')
            ->where('idproyecto', $id)
            ->get();

        if ($categorias->isEmpty()) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Sin Categorias'
            ]);
        }

        return response()->json([
            'success' => true,
            'categorias' => $categorias
        ]);
    }

    public function descargarPlantilla()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Nombre de la Categoría');      
        $sheet->setCellValue('B1', 'Nombre del Proyecto (debe existir en la tabla proyectos)');
        $sheet->setCellValue('C1', 'Estado (Activo/Inactivo)');
        $sheet->setCellValue('D1', 'Color (hexadecimal, ej: #FF0000)');

        $headerStyle = [
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9D9D9']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:D1')->applyFromArray($headerStyle);
        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(55);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(40);

        $writer = new Xlsx($spreadsheet);
        $filename = 'plantilla_categorias.xlsx';
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
        $categorias = [];
        $errores = [];

        foreach ($sheet->getRowIterator(2) as $rowIndex => $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $cells = [];
            foreach ($cellIterator as $cell) {
                $cells[] = $cell->getValue();
            }

            // Buscar el proyecto por nombre
            $nombreProyecto = $cells[1] ?? null;
            $proyecto = Proyecto::where('nombre', $nombreProyecto)->first();
            if (!$proyecto) {
                return response()->json([
                    'success' => false,
                    'message' => "Error: El proyecto '{$nombreProyecto}' no existe.",
                ]);
            }

            $estado = strtolower($cells[2] ?? '') === 'activo' ? true : false;

            // Validar el formato del color hexadecimal
            $color = $cells[3] ?? '#000000';
            if (!preg_match('/^#[a-fA-F0-9]{6}$/', $color)) {
                $errores[] = "Fila " . ($rowIndex + 1) . ": El formato del color '{$color}' no es válido. Debe ser un código hexadecimal de 6 dígitos (ej: #FF0000). Se reemplazó por #000000.";
                $color = '#000000'; // Valor por defecto
            }

            $categorias[] = [
                'nombre' => $cells[0] ?? 'Sin nombre',
                'idproyecto' => $proyecto->id,
                'estado' => $estado,
                'color' => $color,
            ];
        }

        foreach ($categorias as $categoria) {
            CategoriaTerreno::create($categoria);
        }

        return response()->json([
            'success' => true,
            'message' => 'Categorías importadas correctamente.',
            'errores' => $errores, 
        ]);
    }

    private function hexToArgb($hexColor)
    {
        $hexColor = ltrim($hexColor, '#');
        return 'FF' . $hexColor;
    }

    public function exportar()
    {
        $categorias = CategoriaTerreno::with('proyecto')->get();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Nombre de la Categoría');
        $sheet->setCellValue('B1', 'Nombre del Proyecto');
        $sheet->setCellValue('C1', 'Estado');
        $sheet->setCellValue('D1', 'Color');

        $headerStyle = [
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9D9D9']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:D1')->applyFromArray($headerStyle);

        $row = 2;
        foreach ($categorias as $categoria) {
            $sheet->setCellValue('A' . $row, $categoria->nombre);
            $sheet->setCellValue('B' . $row, $categoria->proyecto->nombre);
            $sheet->setCellValue('C' . $row, $categoria->estado ? 'Activo' : 'Inactivo');
            $sheet->setCellValue('D' . $row, $categoria->color);

            $sheet->getStyle('D' . $row)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => $this->hexToArgb($categoria->color)]
                ]
            ]);

            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(35);
        $sheet->getColumnDimension('D')->setWidth(20);

        $writer = new Xlsx($spreadsheet);
        $filename = 'categorias_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
