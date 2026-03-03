<?php

namespace Modules\Academic\Operations;

use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaCertificateParameter;
use Modules\Academic\Entities\AcaCertificate;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaModule;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Response;
use App\Helpers\Invoice\QrCodeGenerator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CertificateImage
{
    public $certificates_param;
    public $type = 'front'; // 'front' o 'back'
    public $module_id = null; // ID del módulo para datos reales
    public $student_id = null; // ID del estudiante para datos reales
    public $course_id = null; // ID del curso

    /**
     * Genera la imagen del certificado
     *
     * @param int $certificate_id ID del certificado (aca_certificate_parameters)
     * @param string $type Tipo de certificado: 'front' (anverso) o 'back' (reverso)
     * @param int|null $student_id ID del estudiante (para datos reales)
     * @param int|null $course_id ID del curso (para datos reales)
     * @param int|null $module_id ID del módulo (para certificados de módulo)
     * @return string|null Contenido de la imagen en binario
     */
    public function generate($certificate_id, $type = 'front', $student_id = null, $course_id = null, $module_id = null)
    {
        // Inicializar propiedades
        $this->type = $type;
        $this->module_id = $module_id;
        $this->student_id = $student_id;
        $this->course_id = $course_id;

        // Cargar configuración del certificado
        $this->certificates_param = AcaCertificateParameter::find($certificate_id);

        // Texto de ejemplo para vista previa (cuando no hay datos reales)
        $textDefault = "Curso de Desarrollo Web Avanzado con Laravel y Vue.js - 120 horas académicas. Temas tratados: Fundamentos de Laravel, APIs RESTful, integración de Vue.js, autenticación con JWT, optimización de bases de datos, despliegue en la nube y buenas prácticas de desarrollo. Fecha: Del 15 de marzo al 30 de mayo de 2023. Instructor: Juan Pérez.";

        // Seleccionar imagen base según el tipo
        if ($type === 'back') {
            $img = Image::make(public_path('storage' . DIRECTORY_SEPARATOR . $this->certificates_param->back_certificate_img));
            $textDefault = $this->certificates_param->back_description;
        } else {
            $img = Image::make(public_path('storage' . DIRECTORY_SEPARATOR . $this->certificates_param->certificate_img));
        }

        // Obtener fecha: si hay estudiante, usar fecha real del registro
        $fecha = $this->getRealDate();

        // Fecha
        $this->addTextToImage($img,
            "Lima, " . $fecha,
            $this->getField('position_date_x'),
            $this->getField('position_date_y'),
            $this->getField('fontfamily_date'),
            $this->getField('font_size_date'),
            $this->getField('color_date'),
            $this->getField('font_align_date'),
            $this->getField('font_vertical_align_date'),
            $this->getField('visible_date')
        );

        // Nombre del estudiante: si hay student_id, usar nombre real
        $studentName = $this->getRealStudentName();

        // Nombre estudiante
        $this->addTextToImage($img,
            $studentName,
            $this->getField('position_names_x'),
            $this->getField('position_names_y'),
            $this->getField('fontfamily_names'),
            $this->getField('font_size_names'),
            $this->getField('color_names'),
            $this->getField('font_align_names'),
            $this->getField('font_vertical_align_names'),
            $this->getField('visible_names')
        );

        // Título del curso: si hay module_id, usar título real del curso + módulo
        $courseTitle = $this->getRealCourseTitle();
        $maxWidthTitle = $this->getField('max_width_title') ?? 800;

        // Título del curso
        $this->addTextToImage($img,
            $this->wrapText($courseTitle, $maxWidthTitle),
            $this->getField('position_title_x'),
            $this->getField('position_title_y'),
            $this->getField('fontfamily_title'),
            $this->getField('font_size_title'),
            $this->getField('color_title'),
            $this->getField('font_align_title'),
            $this->getField('font_vertical_align_title'),
            $this->getField('visible_title')
        );

        // Descripción
        if ($type === 'back') {
            // Para el reverso, usar generación HTML
            $descriptionText = $this->certificates_param->back_description ?? '';

            if ($descriptionText && $this->getField('visible_description')) {
                $htmlGenerator = new CertificateGeneratorHtml();

                // Dimensiones
                $descContentWidth = (int) ($this->getField('max_width_description') ?? 800);
                $descContentHeight = $this->certificates_param->back_certificate_img_height ?? 1550;

                // Configuración de la vista
                $viewData = [
                    'text' => $descriptionText,
                    'canvasWidth' => $descContentWidth,
                    'canvasHeight' => $descContentHeight,
                    'posX' => 10,
                    'posY' => 10,
                    'maxWidth' => $descContentWidth,
                    'fontFamily' => $this->getField('fontfamily_description') ?? 'arial.ttf',
                    'fontSize' => (int) ($this->getField('font_size_description') ?? 14),
                    'color' => $this->getField('color_description') ?? '#000000',
                    'lineHeight' => (int) ($this->getField('font_size_description') ?? 14) + ((int) ($this->getField('interspace_description') ?? 4)),
                    'textAlign' => $this->getField('text_align_description') ?? 'left',
                ];

                $htmlPath = $htmlGenerator->generateFromView('text-description-back', $viewData, $descContentWidth, $descContentHeight);

                if ($htmlPath && File::exists($htmlPath)) {
                    $htmlImage = Image::make($htmlPath);
                    $img->insert($htmlImage, 'top-left',
                        (int) ($this->getField('position_description_x') ?? 50),
                        (int) ($this->getField('position_description_y') ?? 300)
                    );
                    File::delete($htmlPath);
                }
            }
        } else {
            // Para el anverso, usar generación HTML
            $descriptionText = $this->certificates_param->description ?? $textDefault;

            if ($descriptionText && $this->getField('visible_description')) {
                $htmlGenerator = new CertificateGeneratorHtml();

                // Dimensiones
                $descContentWidth = (int) ($this->getField('max_width_description') ?? 800);
                $descContentHeight = $this->certificates_param->certificate_img_height ?? 1550;

                // Configuración de la vista
                $viewData = [
                    'text' => $descriptionText,
                    'canvasWidth' => $descContentWidth,
                    'canvasHeight' => $descContentHeight,
                    'posX' => 10,
                    'posY' => 10,
                    'maxWidth' => $descContentWidth,
                    'fontFamily' => $this->getField('fontfamily_description') ?? 'arial.ttf',
                    'fontSize' => (int) ($this->getField('font_size_description') ?? 14),
                    'color' => $this->getField('color_description') ?? '#000000',
                    'lineHeight' => (int) ($this->getField('font_size_description') ?? 14) + ((int) ($this->getField('interspace_description') ?? 4)),
                    'textAlign' => $this->getField('text_align_description') ?? 'left',
                ];

                $htmlPath = $htmlGenerator->generateFromView('text-description-front', $viewData, $descContentWidth, $descContentHeight);

                if ($htmlPath && File::exists($htmlPath)) {
                    $htmlImage = Image::make($htmlPath);
                    $img->insert($htmlImage, 'top-left',
                        (int) ($this->getField('position_description_x') ?? 50),
                        (int) ($this->getField('position_description_y') ?? 300)
                    );
                    File::delete($htmlPath);
                }
            }
        }

        // Generador de contenido HTML
        $htmlGenerator = new CertificateGeneratorHtml();

        // Solo procesamos contenido para el reverso
        if ($type === 'back') {
            // Obtener dimensiones de la imagen del reverso
            $canvasWidth = $this->certificates_param->back_certificate_img_width ?? 1550;
            $canvasHeight = $this->certificates_param->back_certificate_img_height ?? 1550;

            // Usar un tamaño más pequeño para el contenido HTML
            $contentWidth = 800;
            $contentHeight = 600;

            // Determinar si es certificado por módulo
            $isForModule = $this->certificates_param->for_module ?? false;

            // 1. INSERTAR CONTENIDO DEL CURSO (SI ES VISIBLE Y NO ES PARA MÓDULOS)
            if ($this->getField('visible_course') && !$isForModule) {

                $contentType = $this->getField('content_type') ?? 'list';
                $viewName = $contentType === 'table' ? 'content-table' : 'content-list';

                // Datos: si hay module_id, usar datos reales del curso
                if ($this->module_id) {
                    $course = AcaCourse::find($this->course_id);
                    $contentData = $htmlGenerator->prepareCourseContent($course, false);
                } elseif ($this->certificates_param->course_id) {
                    // Datos del certificado (configuración)
                    $course = AcaCourse::find($this->certificates_param->course_id);
                    $contentData = $htmlGenerator->prepareCourseContent($course, false);
                } else {
                    // Datos de ejemplo para preview
                    $contentData = $htmlGenerator->getExampleData($contentType);
                }

                // Configuración de la vista
                $viewData = array_merge($contentData, [
                    'canvasWidth' => $contentWidth,
                    'canvasHeight' => $contentHeight,
                    'posX' => 10,
                    'posY' => 10,
                    'maxWidth' => (int) ($this->getField('max_width_course') ?? 750),
                    'fontFamily' => $this->getField('fontfamily_course') ?? 'arial.ttf',
                    'fontSize' => (int) ($this->getField('font_size_course') ?? 14),
                    'color' => $this->getField('color_course') ?? '#000000',
                    'lineHeight' => (int) ($this->getField('font_size_course') ?? 14) + 4,
                    'showCourseContent' => true,
                    'showModuleContent' => false,
                    'moduleName' => '',
                ]);

                $htmlPath = $htmlGenerator->generateFromView($viewName, $viewData, $contentWidth, $contentHeight);

                if ($htmlPath && File::exists($htmlPath)) {
                    $htmlImage = Image::make($htmlPath);

                    $img->insert($htmlImage, 'top-left',
                        (int) ($this->getField('position_course_x') ?? 50),
                        (int) ($this->getField('position_course_y') ?? 300)
                    );
                    File::delete($htmlPath);
                }
            }

            // 2. INSERTAR CONTENIDO DEL MÓDULO (SI ES VISIBLE Y ES PARA MÓDULOS)
            if ($this->getField('visible_module') && $isForModule) {
                $contentTypeModule = $this->getField('content_type_module') ?? 'list';
                $viewName = $contentTypeModule === 'table' ? 'content-table' : 'content-list';

                // Determinar el nombre del módulo y los datos a usar
                $moduleName = 'Módulo 1: Nombre del Módulo'; // Default para preview

                // Si hay module_id específico, usar datos reales de ese módulo
                if ($this->module_id) {
                    $course = AcaCourse::find($this->course_id);
                    // Usar el módulo específico
                    $contentData = $htmlGenerator->prepareCourseContent($course, true, $this->module_id);

                    // Obtener el nombre del módulo específico
                    $module = AcaModule::find($this->module_id);
                    $moduleName = $module ? ($module->description ?? 'Módulo') : 'Módulo';
                } elseif ($this->certificates_param->course_id) {
                    // Preview: usar primer módulo del curso
                    $course = AcaCourse::find($this->certificates_param->course_id);
                    $contentData = $htmlGenerator->prepareCourseContent($course, true, null);

                    if ($course->modules()->count() > 0) {
                        $firstModule = $course->modules()->first();
                        $moduleName = $firstModule->description ?? 'Módulo';
                    }
                } else {
                    // Datos de ejemplo
                    $contentData = $htmlGenerator->getExampleData($contentTypeModule);
                }

                // Configuración de la vista
                $viewData = array_merge($contentData, [
                    'canvasWidth' => $contentWidth,
                    'canvasHeight' => $contentHeight,
                    'posX' => 10,
                    'posY' => 10,
                    'maxWidth' => (int) ($this->getField('max_width_module') ?? 750),
                    'fontFamily' => $this->getField('fontfamily_module') ?? 'arial.ttf',
                    'fontSize' => (int) ($this->getField('font_size_module') ?? 14),
                    'color' => $this->getField('color_module') ?? '#000000',
                    'lineHeight' => (int) ($this->getField('font_size_module') ?? 14) + 4,
                    'showCourseContent' => false,
                    'showModuleContent' => true,
                    'moduleName' => $moduleName,
                ]);

                $htmlPath = $htmlGenerator->generateFromView($viewName, $viewData, $contentWidth, $contentHeight);

                if ($htmlPath && File::exists($htmlPath)) {
                    $htmlImage = Image::make($htmlPath);
                    $img->insert($htmlImage, 'top-left',
                        (int) ($this->getField('position_module_x') ?? 50),
                        (int) ($this->getField('position_module_y') ?? 300)
                    );
                    File::delete($htmlPath);
                }
            }
        }



        // QR solo para anverso
        if ($type === 'front') {
            $certificate = AcaCertificate::where('course_id', $course_id)
                ->where('student_id', $student_id)
                ->first();

            $certificate_id = $certificate ? $certificate->id : "1";
            $generator = new QrCodeGenerator(300);
            $dir = public_path() . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'tmp_qr';
            $cadenaqr = route('aca_image_download', ['id' => $certificate_id]);

            $qr_path = $generator->generateQR($cadenaqr, $dir, Str::random(10) . '.png', 8, 2);
            $qr = Image::make($qr_path);

            if ($this->getField('size_qr')) {
                if ($this->getField('visible_image_qr')) {
                    $qr->fit($this->getField('size_qr'), $this->getField('size_qr'));
                    $img->insert($qr, $this->getField('font_align_qr'), $this->getField('position_qr_x'), $this->getField('position_qr_y'));
                }
            }

            if (File::exists($qr_path)){
                File::delete($qr_path);
            }

        }
        // Redimensionar imagen
        $maxWidth = 1550;
        $maxHeight = 1550;
        $img->resize($maxWidth, $maxHeight, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $imageContent = $img->encode('png');

        return $imageContent;
    }

    /**
     * Obtiene el valor de un campo según el tipo (front/back)
     */
    private function getField($field)
    {

        if ($this->type === 'back') {
            $backField = 'back_' . $field;
            return $this->certificates_param->{$backField} ?? null;
        }

        return $this->certificates_param->{$field} ?? null;
    }

    /**
     * Obtiene la fecha real del certificado
     * Si hay student_id, obtiene la fecha del registro del estudiante
     * Si no, usa la fecha actual
     *
     * @return string
     */
    private function getRealDate()
    {
        // Si no hay estudiante, usar fecha actual
        if (!$this->student_id || !$this->course_id) {
            return date('d-m-Y');
        }

        // Buscar el registro del estudiante en el curso
        $register = AcaCapRegistration::where('student_id', $this->student_id)
            ->where('course_id', $this->course_id)
            ->first();

        if ($register && $register->certificate_date) {
            return Carbon::parse($register->certificate_date)->format('d-m-Y');
        }

        return date('d-m-Y');
    }

    /**
     * Obtiene el nombre real del estudiante
     * Si hay student_id, obtiene el nombre de la persona asociada
     * Si no, usa el texto de ejemplo
     *
     * @return string
     */
    private function getRealStudentName()
    {
        // Si no hay estudiante, usar texto de ejemplo
        if (!$this->student_id) {
            return "Nombres del Estudiante o alumnos";
        }

        // Buscar el estudiante con su persona
        $student = AcaStudent::with('person')->find($this->student_id);

        if ($student && $student->person) {
            return $student->person->full_name;
        }

        return "Nombres del Estudiante o alumnos";
    }

    /**
     * Obtiene el título real del curso
     * Si hay module_id, muestra: "Curso: [nombre del curso]" + "Módulo: [nombre del módulo]"
     * Si solo hay course_id, muestra el nombre del curso
     * Si no hay datos reales, usa el texto de ejemplo
     *
     * @return string
     */
    private function getRealCourseTitle()
    {
        // Texto de ejemplo por defecto
        $defaultTitle = "Título del Curso 3025 - II";

        // Si hay módulo específico, mostrar curso + módulo
        if ($this->module_id) {
            $module = AcaModule::with('course')->find($this->module_id);

            if ($module && $module->course) {
                $courseName = $module->course->description ?? 'Curso';
                $moduleName = $module->description ?? 'Módulo';

                return "Curso: {$courseName} - Módulo: {$moduleName}";
            }
        }

        // Si hay curso pero no módulo
        if ($this->course_id) {
            $course = AcaCourse::find($this->course_id);
            if ($course) {
                return $course->description ?? $defaultTitle;
            }
        }

        return $defaultTitle;
    }

    /**
     * Agrega texto a la imagen con configuración de fuente
     */
    private function addTextToImage($img, $text, $posX, $posY, $fontFamily, $fontSize, $color, $align, $valign, $visible)
    {
        if ($posX && $posY && $fontFamily) {
            if ($visible) {

                $img->text($text, $posX, $posY, function ($font) use ($fontFamily, $fontSize, $color, $align, $valign) {
                    $font->file(public_path('fonts' . DIRECTORY_SEPARATOR . $fontFamily));
                    $font->size($fontSize);
                    $font->color($color);
                    $font->align($align);
                    $font->valign($valign);
                    $font->angle(0);
                });
            }
        }
    }

    /**
     * Agrega descripción multilínea a la imagen
     */
    private function addDescriptionToImage($img, $text, $posX, $posY, $fontFamily, $fontSize, $color, $align, $valign, $visible, $maxWidth = null, $interspace = null)
    {
        if ($posX && $posY && $visible) {
            $maxWidthPx = $maxWidth ?? 800;
            $fontSize = $fontSize ?? 12;
            $interlineado_px = $interspace ?? ($fontSize * 0.2);
            $xColor = $color ?? '#0d0603';
            $fontPath = public_path('fonts' . DIRECTORY_SEPARATOR . $fontFamily);

            // Mejoramos la estimación para que el wrap sea más ajustado
            $charWidth = $this->estimateCharWidth($fontSize);
            $lines = $this->splitTextByPixelWidth($text, $maxWidthPx, $charWidth);

            $currentY = $posY;
            $numLines = count($lines);

            foreach ($lines as $index => $line) {
                $lineText = trim($line);
                $words = explode(' ', $lineText);
                $numWords = count($words);

                // Calculamos el ancho real de las palabras juntas para medir la "densidad"
                $wordsOnlyWidth = 0;
                foreach($words as $word) {
                    $wordsOnlyWidth += strlen($word) * $charWidth;
                }

                $isLastLine = ($index === $numLines - 1);

                // CRITERIO DE JUSTIFICACIÓN:
                // 1. No es la última línea.
                // 2. Tiene más de 2 palabras.
                // 3. El contenido ocupa al menos el 65% del ancho (evita espacios gigantes).
                if (!$isLastLine && $numWords > 2 && ($wordsOnlyWidth > $maxWidthPx * 0.65)) {

                    $totalSpaceToDistribute = $maxWidthPx - $wordsOnlyWidth;
                    $spacing = $totalSpaceToDistribute / ($numWords - 1);

                    $currentX = $posX;

                    // Si el alineamiento general es centrado, ajustamos el inicio del bloque justificado
                    if ($align === 'center') {
                        $currentX = $posX - ($maxWidthPx / 2);
                    }

                    foreach ($words as $word) {
                        $img->text($word, (int) $currentX, (int) $currentY, function ($font) use ($fontPath, $fontSize, $xColor, $valign) {
                            $font->file($fontPath);
                            $font->size($fontSize);
                            $font->color($xColor);
                            $font->align('left');
                            $font->valign($valign);
                        });
                        $currentX += (strlen($word) * $charWidth) + $spacing;
                    }
                } else {
                    // ALINEACIÓN NORMAL PARA ÚLTIMA LÍNEA O LÍNEAS POBRES
                    // Esto soluciona que la última línea se vaya a la izquierda
                    $img->text($lineText, (int)$posX, (int)$currentY, function ($font) use ($fontPath, $fontSize, $xColor, $align, $valign) {
                        $font->file($fontPath);
                        $font->size($fontSize);
                        $font->color($xColor);
                        $font->align($align); // Usa 'center' si así está en BD
                        $font->valign($valign);
                    });
                }

                $currentY += ($fontSize + $interlineado_px);
            }
        }
    }
    /**
     * Divide el texto en líneas según un ancho máximo en píxeles.
     */
    public function splitTextByPixelWidth($text, $maxWidthPx, $charWidth)
    {
        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $lineWidth = strlen($currentLine . ' ' . $word) * $charWidth;
            if ($lineWidth > $maxWidthPx) {
                $lines[] = trim($currentLine);
                $currentLine = $word;
            } else {
                $currentLine .= ' ' . $word;
            }
        }

        if (!empty($currentLine)) {
            $lines[] = trim($currentLine);
        }

        return $lines;
    }

    /**
     * Estima el ancho de un carácter en píxeles según el tamaño de la fuente.
     */
    public function estimateCharWidth($fontSize)
    {
        // Para fuentes manuscritas/cursivas, un factor de 0.7 o 0.8 es más seguro
        // para evitar que el texto roce los bordes.
        return $fontSize * 0.75;
    }

    public function wrapText($text, $maxWidth, $lineSpacing = 2.3)
    {

        $wrappedText = wordwrap($text, $maxWidth, PHP_EOL, true);
        // Dividir el texto envuelto en líneas
        $lines = explode(PHP_EOL, $wrappedText);

        // Calcular la longitud máxima de las líneas envueltas
        $maxLineLength = max(array_map('strlen', $lines));

        // Centrar horizontalmente las líneas
        $centeredLines = array_map(function ($line) use ($maxLineLength) {
            $spacesToAdd = max(0, ($maxLineLength - strlen($line)) / 2);
            $centeredLine = str_repeat(' ', $spacesToAdd) . $line;
            return $centeredLine;
        }, $lines);

        // Agregar espacio entre líneas
        $spacing = str_repeat(PHP_EOL, $lineSpacing); // Crear el espacio entre líneas
        $centeredText = implode($spacing, $centeredLines); // Unir las líneas con el espacio

        return $centeredText;
    }

}
