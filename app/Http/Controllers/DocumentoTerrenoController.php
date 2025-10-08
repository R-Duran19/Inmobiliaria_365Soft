<?php

namespace App\Http\Controllers;

use App\Models\DocumentoTerreno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use thiagoalessio\TesseractOCR\TesseractOCR;

class DocumentoTerrenoController extends Controller
{
    public function index($idt_terreno)
    {
        return DocumentoTerreno::where('idt_terreno', $idt_terreno)->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'archivos.*' => 'required|file|max:10240',
            'terreno_id' => 'required|exists:terrenos,id',
        ]);

        $paths = [];
        $directory = public_path('documentos_clientes/' . $request->terreno_id);

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        foreach ($request->file('archivos') as $archivo) {
            $filename = time() . '_' . $archivo->getClientOriginalName();
            $rutaCompleta = $directory . '/' . $filename;

            $archivo->move($directory, $filename);

            $doc = new DocumentoTerreno();
            $doc->idterreno = $request->terreno_id;
            $doc->nombre_documento = $filename;
            $doc->estado_ocr = 'pendiente';
            $doc->created_at = now();
            $doc->updated_at = null;

            // 🔍 Extraer texto con OCR si es imagen
            if ($this->esImagen($filename)) {
                try {
                    $textoExtraido = $this->extraerTextoConOCR($rutaCompleta);
                    
                    if ($textoExtraido) {
                        $datosEstructurados = $this->analizarTexto($textoExtraido);
                        
                        $doc->texto_extraido = $textoExtraido;
                        $doc->datos_extraidos = $datosEstructurados;
                        $doc->estado_ocr = 'procesado';
                    } else {
                        $doc->estado_ocr = 'error';
                    }
                } catch (\Exception $e) {
                    \Log::error("Error OCR: " . $e->getMessage());
                    $doc->estado_ocr = 'error';
                }
            }

            $doc->save();

            $paths[] = 'documentos_clientes/' . $request->terreno_id . '/' . $filename;
        }

        return response()->json([
            'message' => 'Archivos subidos y procesados correctamente',
            'paths' => $paths,
        ]);
    }

    public function destroy($id)
    { 
        $documento = DocumentoTerreno::find($id);
        
        if (!$documento) {
            return response()->json(['message' => 'Documento no encontrado.'], 404);
        }
        
        $ruta_archivo = public_path('documentos_clientes/' . $documento->idterreno . '/' . $documento->nombre_documento);
        
        if (File::exists($ruta_archivo)) {
            File::delete($ruta_archivo);
        } else {
            \Log::warning("Archivo físico no encontrado para el registro ID: {$id}");
        }
        
        $documento->delete();

        return response()->json(['message' => 'Documento y registro eliminados correctamente.'], 200);
    }

    public function listByTerreno($terrenoId)
    {
        $documentos = DocumentoTerreno::where('idterreno', $terrenoId)->get();
        return response()->json($documentos);
    }

    // ============ MÉTODOS PRIVADOS PARA OCR ============

    private function esImagen($filename)
{
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'tif', 'webp']);
}

    private function extraerTextoConOCR($rutaArchivo)
    {
        try {
            $ocr = new TesseractOCR($rutaArchivo);
            $ocr->lang('spa', 'eng'); // Español e Inglés
            $ocr->psm(6); // Modo: bloque uniforme de texto
            
            // Configurar ruta de Tesseract en Windows
            $ocr->executable('C:\\Program Files\\Tesseract-OCR\\tesseract.exe');
            
            $texto = $ocr->run();
            
            return $texto ?: null;
        } catch (\Exception $e) {
            \Log::error("Error al extraer texto OCR: " . $e->getMessage());
            return null;
        }
    }

    private function analizarTexto($texto)
    {
        if (!$texto) return null;

        $datos = [];

        // Buscar número de documento/testimonio
        if (preg_match('/N[UÚ]MERO?:?\s*(\d+(?:\/\d+)?)/i', $texto, $matches)) {
            $datos['numero_documento'] = $matches[1];
        }

        // Buscar fechas (formato: DD/MM/YYYY o DD-MM-YYYY)
        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $texto, $matches)) {
            $datos['fecha'] = $matches[0];
        }

        // Buscar código catastral
        if (preg_match('/C[ÓO]DIGO\s+(?:CATASTRAL|ÚNICO\s+CATASTRAL):?\s*([\d\-\.]+)/i', $texto, $matches)) {
            $datos['codigo_catastral'] = trim($matches[1]);
        }

        // Buscar matrículas
        if (preg_match('/MATR[IÍ]CULA:?\s*([\d\.\-]+)/i', $texto, $matches)) {
            $datos['matricula'] = $matches[1];
        }

        // Buscar área/superficie (varios formatos)
        if (preg_match('/(?:SUPERFICIE|[ÁA]REA|AREA\s+DE\s+TERRENO):?\s*([\d\.,]+)\s*m[²2]?/i', $texto, $matches)) {
            $datos['superficie'] = str_replace(',', '', $matches[1]);
        }

        // Buscar ubicación
        if (preg_match('/UBICACI[ÓO]N:?\s*([^\n]+)/i', $texto, $matches)) {
            $datos['ubicacion'] = trim($matches[1]);
        }

        // Buscar propietario/contribuyente
        if (preg_match('/(?:PROPIETARIO|CONTRIBUYENTE):?\s*([^\n]+)/i', $texto, $matches)) {
            $datos['propietario'] = trim($matches[1]);
        }

        // Detectar tipo de documento
        if (stripos($texto, 'TESTIMONIO') !== false) {
            $datos['tipo_documento'] = 'Testimonio Notarial';
        } elseif (stripos($texto, 'PLANO') !== false || stripos($texto, 'CATASTRAL') !== false) {
            $datos['tipo_documento'] = 'Plano Catastral';
        } elseif (stripos($texto, 'COMPROBANTE') !== false || stripos($texto, 'PAGO') !== false) {
            $datos['tipo_documento'] = 'Comprobante de Pago';
        } elseif (stripos($texto, 'CERTIFICADO') !== false) {
            $datos['tipo_documento'] = 'Certificado';
        } elseif (stripos($texto, 'REGISTRO') !== false) {
            $datos['tipo_documento'] = 'Registro de Propiedad';
        }

        return !empty($datos) ? $datos : null;
    }
}