<?php

namespace App\Http\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class TerrenosExport
{
    protected $terrenos;

    public function __construct($terrenos)
    {
        $this->terrenos = $terrenos;
    }

    public function export()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Encabezados
        $sheet->setCellValue('A1', 'Nombre Proyecto');
        $sheet->setCellValue('B1', 'Ubicación');
        $sheet->setCellValue('C1', 'Categoría');
        $sheet->setCellValue('D1', 'Superficie');
        $sheet->setCellValue('E1', 'Cuota Inicial');
        $sheet->setCellValue('F1', 'Cuota Mensual');
        $sheet->setCellValue('G1', 'Precio Venta');
        $sheet->setCellValue('H1', 'Estado');
        $sheet->setCellValue('I1', 'Condición');

        // Estilo encabezado
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9D9D9']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Filas de datos
        $row = 2;
        foreach ($this->terrenos as $t) {
            $sheet->setCellValue('A' . $row, optional($t->proyecto)->nombre ?? 'Sin proyecto');
            $sheet->setCellValue('B' . $row, $t->ubicacion ?? 'N/A');
            $sheet->setCellValue('C' . $row, $t->categoria ?? 'N/A');
            $sheet->setCellValue('D' . $row, $t->superficie ?? 'N/A');
            $sheet->setCellValue('E' . $row, $t->cuota_inicial ?? 'N/A');
            $sheet->setCellValue('F' . $row, $t->cuota_mensual ?? 'N/A');
            $sheet->setCellValue('G' . $row, $t->precio_venta ?? 'N/A');
            $sheet->setCellValue('H' . $row, $t->estado ?? 'N/A');
            $sheet->setCellValue('I' . $row, ($t->condicion ?? false) ? 'Activo' : 'Inactivo');
            $row++;
        }

        // Ancho de columnas
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setWidth(20);
        }

        return $spreadsheet;
    }
}
