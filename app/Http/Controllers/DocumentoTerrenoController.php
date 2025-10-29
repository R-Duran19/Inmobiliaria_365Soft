<?php

namespace App\Http\Controllers;

use App\Models\DocumentoTerreno;
use Google\Cloud\Vision\V1\Client\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\AnnotateImageRequest;
use Google\Cloud\Vision\V1\BatchAnnotateImagesRequest;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
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

        $tipoDocumento = $request->tipo_documento ?? 'general';

        $datosCedulaCombinados = [];
        $archivosSubidos = [];

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

            if ($this->esImagen($filename)) {
                try {
                    $datosExtraidos = null;

                    \Log::info('Tipo de documento detectado:', ['tipo' => $tipoDocumento]);

                    if ($tipoDocumento === 'folio_real') {
                        \Log::info('Procesando como FOLIO REAL');
                        $datosExtraidos = $this->extraerDatosFolioReal($rutaCompleta);
                    } elseif ($tipoDocumento === 'testimonio') {
                        \Log::info('Procesando como TESTIMONIO');
                        $datosExtraidos = $this->extraerDatosTestimonio($rutaCompleta);
                    } elseif ($tipoDocumento === 'certificado_catastral') {
                        \Log::info('Procesando como CERTIFICADO CATASTRAL');
                        $datosExtraidos = $this->extraerDatosCertificadoCatastral($rutaCompleta);
                    } elseif ($tipoDocumento === 'cedula_identidad') {
                        \Log::info('Procesando como CÉDULA DE IDENTIDAD');
                        $datosExtraidos = $this->extraerDatosCedulaIdentidad($rutaCompleta);

                        if ($datosExtraidos) {
                            $datosCedulaCombinados = array_merge($datosCedulaCombinados, $datosExtraidos);
                            \Log::info('Datos acumulados de cédula:', ['total_campos' => count($datosCedulaCombinados)]);
                        }
                    } else {
                        \Log::info('Procesando como GENERAL');
                        $textoExtraido = $this->extraerTextoConOCR($rutaCompleta);
                        $datosExtraidos = $this->analizarTexto($textoExtraido);
                    }

                    if ($tipoDocumento !== 'cedula_identidad') {
                        if ($datosExtraidos && count($datosExtraidos) > 0) {
                            $doc->datos_extraidos = json_encode($datosExtraidos);
                            $doc->estado_ocr = 'procesado';
                        } else {
                            $doc->estado_ocr = 'sin_datos';
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Error OCR: ' . $e->getMessage());
                    $doc->estado_ocr = 'error';
                }
            }

            $doc->save();
            $archivosSubidos[] = $doc;

            $paths[] = 'documentos_clientes/' . $request->terreno_id . '/' . $filename;
        }

        if ($tipoDocumento === 'cedula_identidad' && count($datosCedulaCombinados) > 0) {
            \Log::info('🔄 Actualizando documentos de cédula con datos combinados');
            \Log::info('Datos finales combinados:', $datosCedulaCombinados);

            foreach ($archivosSubidos as $documento) {
                $documento->datos_extraidos = json_encode($datosCedulaCombinados);
                $documento->estado_ocr = 'procesado';
                $documento->save();
            }
        }

        return response()->json([
            'message' => 'Archivo(s) subido(s) y procesado(s) correctamente',
            'paths' => $paths,
            'datos_combinados' => $tipoDocumento === 'cedula_identidad' ? $datosCedulaCombinados : null,
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

    private function esImagen($filename)
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'tif', 'webp']);
    }

    public function extraerTextoConOCR($rutaImagen)
    {
        try {
            Log::info('=== PROCESANDO CÉDULA DE IDENTIDAD CON OCR ===');
            Log::info('Usando Google Cloud Vision API para OCR...');

            // Crear cliente
            $vision = new ImageAnnotatorClient([
                'credentials' => base_path('storage/credentials/documentos-475421-6eda096856ed.json'),
            ]);

            // Leer imagen
            $imageContent = file_get_contents($rutaImagen);
            if ($imageContent === false) {
                Log::error('No se pudo leer la imagen en: ' . $rutaImagen);
                $vision->close();
                return null;
            }

            // Crear objeto Image
            $image = new Image();
            $image->setContent($imageContent);

            // Crear Feature para TEXT_DETECTION
            $feature = new Feature();
            $feature->setType(Type::TEXT_DETECTION);

            // Crear AnnotateImageRequest
            $request = new AnnotateImageRequest();
            $request->setImage($image);
            $request->setFeatures([$feature]);

            // Crear BatchAnnotateImagesRequest (ESTO ES LO QUE FALTABA)
            $batchRequest = new BatchAnnotateImagesRequest();
            $batchRequest->setRequests([$request]);

            // Llamar a batchAnnotateImages con el objeto correcto
            $response = $vision->batchAnnotateImages($batchRequest);

            // Obtener respuestas
            $responses = $response->getResponses();

            if (count($responses) === 0) {
                Log::warning('⚠️ No se recibieron respuestas de Vision API');
                $vision->close();
                return '';
            }

            $annotation = $responses[0];

            // Verificar errores
            if ($annotation->hasError()) {
                $error = $annotation->getError();
                Log::error('❌ Error en Vision API: ' . $error->getMessage());
                $vision->close();
                return null;
            }

            // Obtener texto
            $textAnnotations = $annotation->getTextAnnotations();

            $vision->close();

            if (count($textAnnotations) > 0) {
                $texto = $textAnnotations[0]->getDescription();
                Log::info('✅ Texto detectado correctamente.');
                Log::info('Primeros 300 caracteres: ' . substr($texto, 0, 300));
                return $texto;
            }

            Log::warning('⚠️ No se detectó texto en la imagen.');
            return '';
        } catch (\Throwable $e) {
            Log::error('❌ Error en Vision OCR: ' . $e->getMessage());
            Log::error('Archivo: ' . $e->getFile() . ' - Línea: ' . $e->getLine());
            return null;
        }
    }

    private function analizarTexto($texto)
    {
        if (!$texto)
            return null;

        $datos = [];

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
            \Log::info('===== INICIO OCR FOLIO REAL (Google Vision API) =====');
            \Log::info('Ruta del archivo: ' . $rutaArchivo);

            if (!file_exists($rutaArchivo)) {
                \Log::error('El archivo no existe');
                return null;
            }

            $fileInfo = [
                'tamaño' => filesize($rutaArchivo),
                'tipo_mime' => mime_content_type($rutaArchivo),
                'extension' => pathinfo($rutaArchivo, PATHINFO_EXTENSION)
            ];
            \Log::info('Info del archivo: ' . json_encode($fileInfo));

            // Verificar credenciales
            $credentialsPath = storage_path('credentials/documentos-475421-6eda096856ed.json');
            if (!file_exists($credentialsPath)) {
                \Log::error('No se encontró el archivo de credenciales de Google Cloud');
                \Log::error('Buscando en: ' . $credentialsPath);
                return $this->extraerDatosFolioRealConTesseract($rutaArchivo);  // Fallback
            }

            putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $credentialsPath);
            \Log::info('Credenciales configuradas correctamente');

            // Intentar con Google Cloud Vision API
            $textoVision = $this->extraerTextoConGoogleVision($rutaArchivo);

            if ($textoVision && strlen($textoVision) > 50) {
                \Log::info('✓ Google Vision API extrajo texto exitosamente');
                \Log::info('Longitud del texto: ' . strlen($textoVision));
                \Log::info('===== TEXTO EXTRAÍDO POR GOOGLE VISION =====');
                \Log::info($textoVision);
                \Log::info('===== FIN TEXTO =====');

                $datos = $this->extraerCamposFolioReal($textoVision);

                if (!empty($datos)) {
                    \Log::info('✓✓✓ Datos extraídos exitosamente con Google Vision');
                    \Log::info('Datos: ' . json_encode($datos));
                    \Log::info('===== FIN OCR FOLIO REAL =====');
                    return $datos;
                } else {
                    \Log::warning('Google Vision extrajo texto pero no se encontraron los campos');
                }
            } else {
                \Log::warning('Google Vision no extrajo suficiente texto, intentando con Tesseract...');
            }

            // Fallback a Tesseract si Google Vision falla
            \Log::info('Intentando con Tesseract como fallback...');
            $datosTesseract = $this->extraerDatosFolioRealConTesseract($rutaArchivo);

            \Log::info('===== FIN OCR FOLIO REAL =====');
            return $datosTesseract;
        } catch (\Exception $e) {
            \Log::error('Error OCR Folio Real: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());

            // Intentar con Tesseract como último recurso
            try {
                \Log::info('Intentando con Tesseract después del error...');
                return $this->extraerDatosFolioRealConTesseract($rutaArchivo);
            } catch (\Exception $e2) {
                \Log::error('También falló Tesseract: ' . $e2->getMessage());
                return null;
            }
        }
    }

    private function extraerTextoConGoogleVision($rutaArchivo)
    {
        try {
            \Log::info('Iniciando extracción con Google Cloud Vision API...');

            // Crear cliente de Vision API
            $imageAnnotator = new ImageAnnotatorClient([
                'credentials' => base_path('storage/credentials/documentos-475421-6eda096856ed.json'),
            ]);

            // Leer el archivo de imagen
            $imageContent = file_get_contents($rutaArchivo);

            if (!$imageContent) {
                \Log::error('No se pudo leer el contenido del archivo');
                $imageAnnotator->close();
                return null;
            }

            \Log::info('Archivo leído correctamente, enviando a Google Vision...');

            // Crear objeto Image
            $image = new Image();
            $image->setContent($imageContent);

            // Crear Feature para TEXT_DETECTION
            $feature = new Feature();
            $feature->setType(Type::TEXT_DETECTION);

            // Crear AnnotateImageRequest
            $request = new AnnotateImageRequest();
            $request->setImage($image);
            $request->setFeatures([$feature]);

            // Crear BatchAnnotateImagesRequest
            $batchRequest = new BatchAnnotateImagesRequest();
            $batchRequest->setRequests([$request]);

            // Realizar detección de texto
            $response = $imageAnnotator->batchAnnotateImages($batchRequest);
            $responses = $response->getResponses();

            if (count($responses) === 0) {
                \Log::warning('Google Vision no devolvió respuestas');
                $imageAnnotator->close();
                return null;
            }

            $annotation = $responses[0];

            // Verificar si hay error
            if ($annotation->hasError()) {
                $error = $annotation->getError();
                \Log::error('Error de Vision API: ' . $error->getMessage());
                $imageAnnotator->close();
                return null;
            }

            // Obtener texto detectado
            $textAnnotations = $annotation->getTextAnnotations();

            if (count($textAnnotations) === 0) {
                \Log::warning('Google Vision no detectó texto en la imagen');
                $imageAnnotator->close();
                return null;
            }

            // El primer elemento contiene todo el texto detectado
            $textoCompleto = $textAnnotations[0]->getDescription();

            \Log::info('Google Vision detectó ' . count($textAnnotations) . ' elementos de texto');
            \Log::info('Longitud del texto completo: ' . strlen($textoCompleto));

            // Cerrar cliente
            $imageAnnotator->close();

            return $textoCompleto;
        } catch (\Exception $e) {
            \Log::error('Error en Google Cloud Vision API: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }

    private function extraerDatosFolioRealConTesseract($rutaArchivo)
    {
        // Este es tu código anterior de Tesseract como fallback
        try {
            \Log::info('=== Usando Tesseract OCR como fallback ===');

            $ocr = new TesseractOCR($rutaArchivo);
            $ocr->lang('spa', 'eng');
            $ocr->executable('C:\Program Files\Tesseract-OCR\tesseract.exe');
            $ocr->psm(6);
            $ocr->oem(1);

            $textoCompleto = $ocr->run();

            \Log::info('Texto extraído por Tesseract (longitud: ' . strlen($textoCompleto) . ')');

            return $this->extraerCamposFolioReal($textoCompleto);
        } catch (\Exception $e) {
            \Log::error('Error OCR Tesseract: ' . $e->getMessage());
            return null;
        }
    }

    private function extraerCamposFolioReal($texto)
    {
        \Log::info('===== EXTRACCIÓN DE CAMPOS =====');
        $datos = [];

        // Limpiar texto
        $textoLimpio = preg_replace('/\s+/', ' ', $texto);

        \Log::info('Longitud del texto: ' . strlen($texto));
        \Log::info('Texto para análisis (primeros 500 chars): ' . substr($texto, 0, 500));

        // ========== BUSCAR MATRÍCULA ==========
        \Log::info('--- Buscando MATRÍCULA ---');

        $patronesMatricula = [
            // Con etiqueta "MATRÍCULA N°"
            '/MATR[IÍ]CULA\s*N[°º]?\s*[:\s]*(\d{1,2}\.\d{2}\.\d{1,2}\.\d{2}\.\d{2,8})/iu',
            // Formato flexible con espacios o guiones
            '/MATR[IÍ]CULA[:\s]+(\d+[\.\s\-]+\d+[\.\s\-]+\d+[\.\s\-]+\d+[\.\s\-]+\d+)/i',
            // Patrón directo: X.XX.X.XX.XXXXXX (más común)
            '/\b(\d{1,2}\.\d{2}\.\d{1,2}\.\d{2}\.\d{4,8})\b/',
            // Con más flexibilidad en los números
            '/\b(\d{1,2}\.\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{3,10})\b/',
        ];

        foreach ($patronesMatricula as $indice => $patron) {
            if (preg_match($patron, $texto, $matches)) {
                $matricula = $matches[1];

                // Limpiar espacios y caracteres extraños
                $matricula = trim($matricula);
                $matricula = preg_replace('/[\s\-]+/', '', $matricula);

                \Log::info("Candidato encontrado con patrón $indice: '$matricula'");

                // Validar que tenga puntos y números
                $partes = explode('.', $matricula);
                \Log::info('Número de partes: ' . count($partes) . ' - Partes: ' . json_encode($partes));

                // Debe tener al menos 5 partes separadas por punto
                if (count($partes) >= 5 && strlen($matricula) >= 10) {
                    $datos['matricula'] = $matricula;
                    \Log::info('✓✓✓ Matrícula VÁLIDA encontrada: ' . $matricula);
                    break;
                } else {
                    \Log::info('Descartado: no cumple validación (partes: ' . count($partes) . ', longitud: ' . strlen($matricula) . ')');
                }
            }
        }

        if (!isset($datos['matricula'])) {
            \Log::warning('✗ No se encontró matrícula con patrones principales');
            \Log::info('Intentando búsqueda exhaustiva...');

            // Buscar CUALQUIER secuencia que tenga formato X.XX.X.XX.XXXXX
            if (preg_match_all('/(\d{1,2}\.\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{3,10})/', $texto, $matches)) {
                \Log::info('Secuencias numéricas encontradas: ' . json_encode($matches[1]));

                foreach ($matches[1] as $candidato) {
                    $partes = explode('.', $candidato);

                    if (count($partes) >= 5) {
                        $datos['matricula'] = $candidato;
                        \Log::info('✓ Matrícula encontrada (búsqueda exhaustiva): ' . $candidato);
                        break;
                    }
                }
            }
        }

        \Log::info('Datos finales extraídos: ' . json_encode($datos));
        \Log::info('===== FIN EXTRACCIÓN DE CAMPOS =====');

        return $datos;
    }

    private function extraerDatosTestimonio($rutaArchivo)
    {
        try {
            \Log::info('===== INICIO OCR TESTIMONIO (Google Vision API) =====');
            \Log::info('Ruta del archivo: ' . $rutaArchivo);

            if (!file_exists($rutaArchivo)) {
                \Log::error('El archivo no existe');
                return null;
            }

            // Crear cliente de Vision API
            $vision = new ImageAnnotatorClient([
                'credentials' => base_path('storage/credentials/documentos-475421-6eda096856ed.json'),
            ]);

            // Leer imagen
            $imageContent = file_get_contents($rutaArchivo);
            if ($imageContent === false) {
                \Log::error('No se pudo leer la imagen');
                $vision->close();
                return null;
            }

            \Log::info('Archivo leído correctamente, enviando a Google Vision...');

            // Crear objeto Image
            $image = new Image();
            $image->setContent($imageContent);

            // Crear Feature para TEXT_DETECTION
            $feature = new Feature();
            $feature->setType(Type::TEXT_DETECTION);

            // Crear AnnotateImageRequest
            $request = new AnnotateImageRequest();
            $request->setImage($image);
            $request->setFeatures([$feature]);

            // Crear BatchAnnotateImagesRequest
            $batchRequest = new BatchAnnotateImagesRequest();
            $batchRequest->setRequests([$request]);

            // Realizar detección de texto
            $response = $vision->batchAnnotateImages($batchRequest);
            $responses = $response->getResponses();

            if (count($responses) === 0) {
                \Log::warning('Google Vision no devolvió respuestas');
                $vision->close();
                return null;
            }

            $annotation = $responses[0];

            // Verificar si hay error
            if ($annotation->hasError()) {
                $error = $annotation->getError();
                \Log::error('Error de Vision API: ' . $error->getMessage());
                $vision->close();
                return null;
            }

            // Obtener texto detectado
            $textAnnotations = $annotation->getTextAnnotations();

            $vision->close();

            if (count($textAnnotations) === 0) {
                \Log::warning('Google Vision no detectó texto en la imagen');
                return null;
            }

            // El primer elemento contiene todo el texto detectado
            $textoCompleto = $textAnnotations[0]->getDescription();

            \Log::info('✓ Texto extraído por Google Vision');
            \Log::info('Longitud del texto: ' . strlen($textoCompleto));
            \Log::info('Primeros 500 caracteres: ' . substr($textoCompleto, 0, 500));
            \Log::info('===== FIN OCR TESTIMONIO =====');

            return $this->extraerCamposTestimonio($textoCompleto);
        } catch (\Exception $e) {
            \Log::error('Error OCR Testimonio: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }

    private function extraerCamposTestimonio($texto)
    {
        $datos = [];

        // Buscar "TESTIMONIO N°" seguido del número
        if (preg_match('/TESTIMONIO\s*N[°º]?\s*[:\s]*(\d+\/\d{4})/i', $texto, $matches)) {
            $datos['numero_testimonio'] = trim($matches[1]);
            \Log::info('Número de testimonio encontrado: ' . $datos['numero_testimonio']);
        }
        // Patrón alternativo si el formato es diferente
        elseif (preg_match('/TESTIMONIO[:\s]+(\d+\/\d{4})/i', $texto, $matches)) {
            $datos['numero_testimonio'] = trim($matches[1]);
            \Log::info('Número de testimonio encontrado (patrón alternativo): ' . $datos['numero_testimonio']);
        }
        // Buscar solo el patrón número/año en caso de que "TESTIMONIO N°" no esté claro
        elseif (preg_match('/\b(\d{3,5}\/\d{4})\b/', $texto, $matches)) {
            $datos['numero_testimonio'] = trim($matches[1]);
            \Log::info('Número de testimonio encontrado (patrón genérico): ' . $datos['numero_testimonio']);
        }

        // Validar que se encontró el número
        if (!isset($datos['numero_testimonio'])) {
            \Log::warning('No se pudo extraer el número de testimonio');
        }

        return $datos;
    }

    private function extraerDatosCedulaIdentidad($rutaArchivo)
    {
        try {
            \Log::info('=== PROCESANDO CÉDULA DE IDENTIDAD CON OCR ===');

            $textoCompleto = $this->extraerTextoConOCR($rutaArchivo);

            if (!$textoCompleto) {
                \Log::warning('No se pudo extraer texto de la imagen');
                return null;
            }

            \Log::info('Texto extraído (primeros 300 chars):', ['texto' => substr($textoCompleto, 0, 300)]);

            $datos = $this->extraerDatosEstructurados($textoCompleto);

            if ($datos && count($datos) > 0) {
                \Log::info('✓ Datos extraídos:', ['cantidad_campos' => count($datos)]);
                return $datos;
            }

            \Log::warning('No se pudieron extraer datos de la cédula');
            return null;
        } catch (\Exception $e) {
            \Log::error('Error procesando cédula: ' . $e->getMessage());
            return null;
        }
    }

    private function extraerDatosEstructurados($texto)
    {
        $datos = [];

        \Log::info('=== INICIANDO EXTRACCIÓN V18 FECHA EXPIRACION MEJORADA ===');
        \Log::info('Longitud del texto recibido: ' . strlen($texto) . ' caracteres');

        $fechaNacimientoMRZ = null;
        $numeroCI_MRZ = null;

        \Log::info('🔍 Iniciando búsqueda de código MRZ...');

        if (preg_match('/(\d)(\d)(\d)(\d)(\d)(\d)\d*\s*([MF])\s*(\d)(\d)(\d)(\d)(\d)(\d)\d*[\s<]*BOL/ism', $texto, $mrzMatches)) {
            \Log::info('🎉 ¡Código MRZ detectado!');

            $anio = $mrzMatches[1] . $mrzMatches[2];
            $mes = $mrzMatches[3] . $mrzMatches[4];
            $dia = $mrzMatches[5] . $mrzMatches[6];
            $genero = $mrzMatches[7];

            $anioCompleto = (int) $anio > 30 ? '19' . $anio : '20' . $anio;
            $fechaNacimientoMRZ = "$dia/$mes/$anioCompleto";

            \Log::info('📅 Fecha de nacimiento MRZ: ' . $fechaNacimientoMRZ);
        } else {
            \Log::warning('❌ MRZ no detectado');
        }

        if (preg_match('/[I1]<BOL(\d{7,10})/i', $texto, $matches)) {
            $numeroCI_MRZ = $matches[1];
            \Log::info('🆔 Número CI del MRZ: ' . $numeroCI_MRZ);
        }

        if (preg_match('/N[°º][^\d]*(\d{7,8})/i', $texto, $matches)) {
            $datos['numero_cedula'] = $matches[1];
            \Log::info('✓ Número de cédula (formato N°): ' . $datos['numero_cedula']);
        } elseif ($numeroCI_MRZ !== null) {
            $datos['numero_cedula'] = $numeroCI_MRZ;
            \Log::info('✓ Número de cédula (desde MRZ): ' . $numeroCI_MRZ);
        } elseif (preg_match('/No\.[\s_]*(\d{7,8})/i', $texto, $matches)) {
            $datos['numero_cedula'] = $matches[1];
            \Log::info('✓ Número de cédula (formato No.): ' . $datos['numero_cedula']);
        } elseif (preg_match('/\b(\d{7,8})\b/', $texto, $matches)) {
            $posible = $matches[1];
            $contexto = substr($texto, max(0, strpos($texto, $posible) - 50), 100);

            if (!preg_match('/\/|\bde\b/i', $contexto)) {
                $datos['numero_cedula'] = $posible;
                \Log::info('✓ Número de cédula (búsqueda general): ' . $posible);
            } else {
                \Log::info('⚠️ Número descartado (parece fecha): ' . $posible);
            }
        }

        if (!isset($datos['numero_cedula'])) {
            \Log::warning('❌ No se pudo extraer número de cédula válido (se requieren 7-8 dígitos)');
        }

        if (preg_match('/\b[PR]\s+([A-ZÁÉÍÓÚÑ]+)\s+([A-ZÁÉÍÓÚÑ]+)\s+([A-ZÁÉÍÓÚÑ]+)/iu', $texto, $matches)) {
            $datos['primer_nombre'] = $this->corregirNombreOCR(ucwords(strtolower($matches[1])));
            $datos['apellido_paterno'] = $this->corregirNombreOCR(ucwords(strtolower($matches[2])));
            $datos['apellido_materno'] = $this->corregirNombreOCR(ucwords(strtolower($matches[3])));
            \Log::info('✓ Nombres extraídos');
        }

        preg_match_all('/(\d{2})\/(\d{2})\/(\d{4})/', $texto, $todasFechas, PREG_SET_ORDER);

        $fechasValidas = [];
        foreach ($todasFechas as $fecha) {
            $dia = (int) $fecha[1];
            $mes = (int) $fecha[2];
            $anio = (int) $fecha[3];

            if ($dia >= 1 && $dia <= 31 && $mes >= 1 && $mes <= 12 && $anio >= 1900 && $anio <= 2100) {
                $fechasValidas[] = $fecha[0];
            }
        }

        if (count($fechasValidas) >= 3) {
            $datos['fecha_nacimiento'] = $fechasValidas[0];
            $datos['fecha_emision'] = $fechasValidas[1];
            $datos['fecha_expiracion'] = $fechasValidas[2];
        } elseif (count($fechasValidas) == 2) {
            $datos['fecha_emision'] = $fechasValidas[0];
            $datos['fecha_expiracion'] = $fechasValidas[1];
        }

        if (!isset($datos['fecha_nacimiento']) && preg_match('/Nacido\s+el\s+(\d{1,2}\s+de\s+\w+\s+de\s+\d{4})/i', $texto, $matches)) {
            $datos['fecha_nacimiento'] = $matches[1];
        }

        if (!isset($datos['fecha_emision']) && preg_match('/Emitida\s+el\s+(\d{1,2}\s+de\s+\w+\s+de\s+\d{4})/i', $texto, $matches)) {
            $datos['fecha_emision'] = $matches[1];
        }

        if (!isset($datos['fecha_expiracion'])) {
            if (preg_match('/Expira\s+(?:el\s+)?[\-\s]*(\d{1,2})\s*(?:de\s+)?(\w+)\s+(?:de\s+)?(\d{4})/i', $texto, $matches)) {
                $datos['fecha_expiracion'] = $matches[1] . ' de ' . $matches[2] . ' de ' . $matches[3];
                \Log::info('✓ Fecha de expiración extraída: ' . $datos['fecha_expiracion']);
            }
        }

        if (!isset($datos['fecha_nacimiento']) && $fechaNacimientoMRZ !== null) {
            $datos['fecha_nacimiento'] = $fechaNacimientoMRZ;
        }

        if (preg_match('/([A-ZÁÉÍÓÚÑ]+\s*-\s*[A-ZÁÉÍÓÚÑ\s]+\s*-\s*[A-ZÁÉÍÓÚÑ]+)/iu', $texto, $matches)) {
            $datos['lugar_nacimiento'] = trim($matches[1]);
        }

        if (preg_match('/(?:C\/|Domicilio\s+)([^\n]+)/i', $texto, $matches)) {
            $datos['domicilio'] = trim($matches[1]);
        }

        $ocupaciones = ['ESTUDIANTE', 'COMERCIANTE', 'PROFESOR', 'INGENIERO', 'ABOGADO'];
        foreach ($ocupaciones as $ocupacion) {
            if (preg_match('/\b' . preg_quote($ocupacion, '/') . '\b/i', $texto)) {
                $datos['ocupacion'] = ucfirst(strtolower($ocupacion));
                break;
            }
        }

        $estadosCiviles = ['SOLTERO', 'SOLTERA', 'CASADO', 'CASADA'];
        foreach ($estadosCiviles as $estado) {
            if (preg_match('/\b' . preg_quote($estado, '/') . '\b/i', $texto)) {
                $datos['estado_civil'] = ucfirst(strtolower($estado));
                break;
            }
        }

        \Log::info('=== RESUMEN V18 ===');
        \Log::info('Total de campos: ' . count($datos));
        \Log::info('Datos extraídos:', $datos);

        return !empty($datos) ? $datos : null;
    }

    private function corregirNombreOCR($nombre)
    {
        $correcciones = [
            'Onald' => 'Ronald',
            'Arlos' => 'Carlos',
        ];

        foreach ($correcciones as $incorrecto => $correcto) {
            if (strcasecmp($nombre, $incorrecto) === 0) {
                return $correcto;
            }
        }

        return $nombre;
    }
}
