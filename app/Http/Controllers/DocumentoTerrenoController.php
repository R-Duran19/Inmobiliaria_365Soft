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
            'tipo_documento' => 'nullable|string',
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
                    $datosExtraidos = null;
                    $tipoDocumento = $request->tipo_documento ?? 'general';

                    if ($tipoDocumento === 'folio_real') {
                        $datosExtraidos = $this->extraerDatosFolioReal($rutaCompleta);
                    } elseif ($tipoDocumento === 'testimonio') {
                        $datosExtraidos = $this->extraerDatosTestimonio($rutaCompleta);
                    } elseif ($tipoDocumento === 'certificado_catastral') {
                        $datosExtraidos = $this->extraerDatosCertificadoCatastral($rutaCompleta);
                    } else {
                        $textoExtraido = $this->extraerTextoConOCR($rutaCompleta);
                        $datosExtraidos = $this->analizarTexto($textoExtraido);
                    }

                    if ($datosExtraidos && count($datosExtraidos) > 0) {
                        $doc->datos_extraidos = json_encode($datosExtraidos);
                        $doc->estado_ocr = 'procesado';
                    } else {
                        $doc->estado_ocr = 'sin_datos';
                    }
                } catch (\Exception $e) {
                    \Log::error('Error OCR: ' . $e->getMessage());
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

    public function visualizar($terrenoId)
    {
        return inertia('Documentos/VisualizarDocumentos', [
            'terrenoId' => $terrenoId,
        ]);
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
            $ocr->lang('spa', 'eng');  // Español e Inglés
            $ocr->psm(6);  // Modo: bloque uniforme de texto

            // Configurar ruta de Tesseract en Windows
            $ocr->executable('C:\Program Files\Tesseract-OCR\tesseract.exe');

            $texto = $ocr->run();

            return $texto ?: null;
        } catch (\Exception $e) {
            \Log::error('Error al extraer texto OCR: ' . $e->getMessage());
            return null;
        }
    }

    private function analizarTexto($texto)
    {
        // Este método ahora solo se usa como fallback
        if (!$texto)
            return null;

        $datos = [];

        // Búsqueda genérica básica
        if (preg_match('/MATR[IÍ]CULA:?\s*([\d\.\-]+)/i', $texto, $matches)) {
            $datos['matricula'] = $matches[1];
        }

        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $texto, $matches)) {
            $datos['fecha'] = $matches[0];
        }

        return !empty($datos) ? $datos : null;
    }

    private function extraerDatosFolioReal($rutaArchivo)
    {
        try {
            $ocr = new TesseractOCR($rutaArchivo);
            $ocr->lang('spa', 'eng');
            $ocr->executable('C:\Program Files\Tesseract-OCR\tesseract.exe');

            $ocr->psm(6);
            $ocr->oem(1);

            $textoCompleto = $ocr->run();

            return $this->extraerCamposFolioReal($textoCompleto);
        } catch (\Exception $e) {
            \Log::error('Error OCR Folio Real: ' . $e->getMessage());
            return null;
        }
    }

    private function extraerCamposFolioReal($texto)
    {
        $datos = [];

        // Matrícula (formato: 2.01.0.99.00.76676)
        if (preg_match('/(\d{1,2}\.\d{2}\.\d{1,2}\.\d{2}\.\d{2}\.\d{5,})/', $texto, $matches)) {
            $datos['matricula'] = $matches[1];
        }

        // Código de barras
        if (preg_match('/[a-z]{2,6}[0-9i]{1,2}(\d{8,12})/i', $texto, $matches)) {
            $datos['codigo_barras'] = $matches[1];
        } elseif (preg_match('/(\d{8,10})(?=\s|$)/m', $texto, $matches)) {
            if (!isset($datos['matricula']) || strpos($datos['matricula'], $matches[1]) === false) {
                $datos['codigo_barras'] = '0812' . $matches[1];
            }
        } elseif (preg_match_all('/\b(\d{8,})\b/', $texto, $matches)) {
            foreach ($matches[1] as $match) {
                if (strlen($match) >= 8 && strlen($match) <= 14) {
                    if (!isset($datos['matricula']) || strpos($datos['matricula'], $match) === false) {
                        $datos['codigo_barras'] = $match;
                        break;
                    }
                }
            }
        } elseif (preg_match('/ost[0-9]{1}[a-z]{1}(\d+)/i', $texto, $matches)) {
            $datos['codigo_barras'] = '08121' . $matches[1];
        }

        return $datos;
    }

    private function extraerDatosTestimonio($rutaArchivo)
    {
        try {
            $ocr = new TesseractOCR($rutaArchivo);
            $ocr->lang('spa', 'eng');
            $ocr->executable('C:\Program Files\Tesseract-OCR\tesseract.exe');

            $ocr->psm(11);
            $ocr->oem(1);

            $textoCompleto = $ocr->run();

            return $this->extraerCamposTestimonio($textoCompleto);
        } catch (\Exception $e) {
            \Log::error('Error OCR Testimonio: ' . $e->getMessage());
            return null;
        }
    }

    private function extraerCamposTestimonio($texto)
    {
        $datos = [];

        // Serie (formato: C-PJ-FN-2014)
        if (preg_match('/\b([A-Z]\-[A-Z]{2}\-[A-Z]{2}\-\d{4})\b/', $texto, $matches)) {
            $datos['serie'] = trim($matches[1]);
        } elseif (preg_match('/Serie[:\s]+([A-Z0-9\-]+)/i', $texto, $matches)) {
            if (strlen($matches[1]) > 5 && strpos($matches[1], '-') !== false) {
                $datos['serie'] = trim($matches[1]);
            }
        } elseif (preg_match('/([A-Z]{1,2}\-[A-Z]{2,4}\-[A-Z]{2,4}\-\d{4})/', $texto, $matches)) {
            $datos['serie'] = trim($matches[1]);
        }

        // Validar que la serie no sea muy corta o inválida
        if (isset($datos['serie']) && (strlen($datos['serie']) < 5 || !preg_match('/[0-9]/', $datos['serie']))) {
            unset($datos['serie']);
        }

        return $datos;
    }

    private function extraerDatosCertificadoCatastral($rutaArchivo)
    {
        try {
            $ocr = new TesseractOCR($rutaArchivo);
            $ocr->lang('spa', 'eng');
            $ocr->executable('C:\Program Files\Tesseract-OCR\tesseract.exe');

            $ocr->psm(11);
            $ocr->oem(1);

            $textoCompleto = $ocr->run();

            return $this->extraerCamposCertificadoCatastral($textoCompleto);
        } catch (\Exception $e) {
            \Log::error('Error OCR Certificado Catastral: ' . $e->getMessage());
            return null;
        }
    }

    private function extraerCamposCertificadoCatastral($texto)
    {
        $datos = [];

        // Número (formato: 0007-2019)
        if (preg_match('/N[°º]\s*(\d{4}\-\d{4})/i', $texto, $matches)) {
            $datos['numero'] = trim($matches[1]);
        } elseif (preg_match('/(\d{4}\-\d{4})/', $texto, $matches)) {
            $datos['numero'] = trim($matches[1]);
        } elseif (preg_match('/(\d{3,5}\-\d{4})/', $texto, $matches)) {
            $datos['numero'] = trim($matches[1]);
        }

        return $datos;
    }
}
