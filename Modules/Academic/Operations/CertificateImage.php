<?php

namespace Modules\Academic\Operations;

use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaCertificateParameter;
use Modules\Academic\Entities\AcaCertificate;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaStudent;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Response;
use App\Helpers\Invoice\QrCodeGenerator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CertificateImage
{
    public $certificates_param;

    public function generate($certificate_id, $student_id = null, $course_id = null)
    {
        if ($student_id == null && $course_id == null) {
            //si no llega datos de curso ni alumno es porque quiere generar vista previa, por ende debe generar certificado sin datos
            $this->certificates_param = AcaCertificateParameter::find($certificate_id);

            $img = Image::make(public_path('storage' . DIRECTORY_SEPARATOR . $this->certificates_param->certificate_img));

            $fecha = date('d-m-Y');

            if ($this->certificates_param->position_date_x && $this->certificates_param->position_date_y && $this->certificates_param->fontfamily_date) {
                if ($this->certificates_param->visible_date) {
                    $img->text("Lima, " . $fecha, $this->certificates_param->position_date_x, $this->certificates_param->position_date_y, function ($font) {
                        $font->file(public_path('fonts' . DIRECTORY_SEPARATOR . $this->certificates_param->fontfamily_date));
                        $font->size($this->certificates_param->font_size_date);
                        $font->color($this->certificates_param->color_date);
                        $font->align($this->certificates_param->font_align_date);
                        $font->valign($this->certificates_param->font_vertical_align_date);
                        $font->angle(0);
                    });
                }
            }
            //nombre estudiante
            if ($this->certificates_param->fontfamily_names && $this->certificates_param->font_size_names) {
                if ($this->certificates_param->visible_names) {
                    $img->text("Nombres del Estudiante o alumno", $this->certificates_param->position_names_x, $this->certificates_param->position_names_y, function ($font) {
                        $font->file(public_path('fonts' . DIRECTORY_SEPARATOR . $this->certificates_param->fontfamily_names));
                        $font->size($this->certificates_param->font_size_names);
                        $font->color($this->certificates_param->color_names);
                        $font->align($this->certificates_param->font_align_names);
                        $font->valign($this->certificates_param->font_vertical_align_names);
                        $font->angle(0);
                    });
                }
            }
            //titulo del curso
            if ($this->certificates_param->fontfamily_title && $this->certificates_param->font_align_title && $this->certificates_param->font_vertical_align_title && $this->certificates_param->position_title_y) {
                $max_width = $this->certificates_param->max_width_title;
                if ($this->certificates_param->visible_title) {
                    $img->text($this->wrapText("Título del Curso 3025 - II", $max_width), $this->certificates_param->position_title_x, $this->certificates_param->position_title_y, function ($font) {
                        $font->file(public_path('fonts' . DIRECTORY_SEPARATOR . $this->certificates_param->fontfamily_title));
                        $font->size($this->certificates_param->font_size_title);
                        $font->color($this->certificates_param->color_title);
                        $font->align($this->certificates_param->font_align_title);
                        $font->valign($this->certificates_param->font_vertical_align_title);
                        $font->angle(0);
                    });
                }
            }
            // //descripcion del certificado

            // if ($this->certificates_param->position_description_x && $this->certificates_param->position_description_y) {
            //     $max_width = $this->certificates_param->max_width_description;

            //     $img->text($this->wrapText("Descripción del curso, donde aparece horas académicas, fecha e información de lo llevado a cabo en el curso o diplomado y más, en este ejemplo se puso texto de más porque es necesario ver como quedará...",
            //     $max_width, $this->certificates_param->interspace_description), $this->certificates_param->position_description_x, $this->certificates_param->position_description_y, function ($font) {
            //         $font->file(public_path('fonts' . DIRECTORY_SEPARATOR . $this->certificates_param->fontfamily_description));
            //         $font->size($this->certificates_param->font_size_description);
            //         $font->color('#0d0603');
            //         $font->align($this->certificates_param->font_align_description);
            //         $font->valign($this->certificates_param->font_vertical_align_description);
            //         $font->angle(0);
            //     });
            // }

            if ($this->certificates_param->position_description_x && $this->certificates_param->position_description_y) {
                if ($this->certificates_param->visible_description) {
                    // Descripción del certificado
                    $max_width = $this->certificates_param->max_width_description * 10; // Ancho máximo en píxeles
                    $text = "Curso de Desarrollo Web Avanzado con Laravel y Vue.js - 120 horas académicas. Temas tratados: Fundamentos de Laravel, APIs RESTful, integración de Vue.js, autenticación con JWT, optimización de bases de datos, despliegue en la nube y buenas prácticas de desarrollo. Fecha: Del 15 de marzo al 30 de mayo de 2023. Instructor: Juan Pérez.";
                    $interlineado_px = $this->certificates_param->interspace_description; // Interlineado en píxeles

                    // Obtener el ancho de un solo carácter (aproximado)
                    $fontSize = $this->certificates_param->font_size_description;
                    $charWidth = $this->estimateCharWidth($fontSize); // Función para estimar el ancho de un carácter

                    // Dividir el texto en líneas según el ancho máximo en píxeles
                    $lines = $this->splitTextByPixelWidth($text, $max_width, $charWidth);

                    // Posición inicial Y para la primera línea
                    $currentY = $this->certificates_param->position_description_y;
                    //dd($fontSize, $charWidth, $lines, $currentY);

                    // Dibujar cada línea en la imagen
                    foreach ($lines as $line) {
                        $img->text($line, $this->certificates_param->position_description_x, $currentY, function ($font) {
                            $font->file(public_path('fonts' . DIRECTORY_SEPARATOR . $this->certificates_param->fontfamily_description));
                            $font->size($this->certificates_param->font_size_description);
                            $font->color($this->certificates_param->color_description);
                            $font->align($this->certificates_param->font_align_description);
                            $font->valign($this->certificates_param->font_vertical_align_description);
                            $font->angle(0);
                        });

                        // Aumentar la posición Y para la siguiente línea, sumando el interlineado
                        $currentY += $interlineado_px;
                    }
                }
            }
            // //QR GENERATOR
            $certificate = AcaCertificate::where('course_id', $course_id)
                ->where('student_id', $student_id)
                ->first();

            $certificate_id = $certificate ? $certificate->id : "1"; // Si $certificate es null, asigna 1 por defecto
            $generator = new QrCodeGenerator(300);
            $dir = public_path() . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'tmp_qr';
            $cadenaqr = route('aca_image_download', ['id' => $certificate_id]);

            $qr_path = $generator->generateQR($cadenaqr, $dir, Str::random(10) . '.png', 8, 2);

            $qr = Image::make($qr_path);

            if ($this->certificates_param->size_qr) {
                if ($this->certificates_param->visible_image_qr) {
                    $qr->fit($this->certificates_param->size_qr, $this->certificates_param->size_qr); //ajustar tamaño del qr
                    $img->insert($qr, $this->certificates_param->font_align_qr, $this->certificates_param->position_qr_x, $this->certificates_param->position_qr_y); //insertar qr en la imagen
                }
            }

            // Ejemplo de Redimensionar la imagen manteniendo la proporción para avatares y similares
            // Establecer el ancho máximo y la altura máxima deseados
            $maxWidth = 1550;
            $maxHeight = 1550;
            $img->resize($maxWidth, $maxHeight, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });



            // Obtener el contenido binario de la imagen
            $imageContent = $img->encode('png');

            //ELIMINAR el EL ARCHIVO QR generado
            if (File::exists($qr_path)) File::delete($qr_path);

            //Retornar la respuesta
            return $imageContent;
        } else {
            $register = AcaCapRegistration::where('student_id', $student_id)
                ->where('course_id', $course_id)
                ->first();

            if (!$register) {
                $register = AcaCapRegistration::first();
                $student_id = $register->student_id;
                $course_id = $register->course_id;
            }

            if ($register) {
                if ($register->certificate_date != null) {

                    $student = AcaStudent::with('person')->find($student_id);

                    $this->certificates_param = AcaCertificateParameter::find($certificate_id);

                    $course = AcaCourse::find($course_id);

                    //dd(public_path('storage' . DIRECTORY_SEPARATOR . $this->certificates_param->certificate_img));
                    //dd($this->certificates_param);
                    // create Image from file
                    $img = Image::make(public_path('storage' . DIRECTORY_SEPARATOR . $this->certificates_param->certificate_img));

                    $fecha = $register->certificate_date; //Esta fecha debe obtenerse del registro de la matricula del estudiante al curso respectivo donde se obtiene la fecha de entrega del certificado si es null entonces no tiene certificado

                    if ($register->certificate_date) {
                        $fecha = Carbon::parse($register->certificate_date)->format('d-m-Y');
                    } else {
                        $fecha = 'Sin certificado';
                    }
                    //las fuentes deben estar en la carpeta public/fonts y en la base de datos debe registrarse el nombre de la fuente y su extensión
                    //recomiendo usar fuentes de google fonts porque son gratis y puedes descargarlas
                    //dd(public_path('fonts' . DIRECTORY_SEPARATOR . $this->certificates_param->fontfamily_date));
                    if ($this->certificates_param->position_date_x && $this->certificates_param->position_date_y && $this->certificates_param->fontfamily_date) {
                        $img->text("Lima, " . $fecha, $this->certificates_param->position_date_x, $this->certificates_param->position_date_y, function ($font) {
                            $font->file(public_path('fonts' . DIRECTORY_SEPARATOR . $this->certificates_param->fontfamily_date));
                            $font->size($this->certificates_param->font_size_date);
                            $font->color($this->certificates_param->color_date);
                            $font->align($this->certificates_param->font_align_date);
                            $font->valign($this->certificates_param->font_vertical_align_date);
                            $font->angle(0);
                        });
                    }
                    //nombre estudiante
                    if ($this->certificates_param->fontfamily_names && $student->person->full_name && $this->certificates_param->font_size_names) {
                        $img->text($student->person->full_name, $this->certificates_param->position_names_x, $this->certificates_param->position_names_y, function ($font) {
                            $font->file(public_path('fonts' . DIRECTORY_SEPARATOR . $this->certificates_param->fontfamily_names));
                            $font->size($this->certificates_param->font_size_names);
                            $font->color($this->certificates_param->color_names);
                            $font->align($this->certificates_param->font_align_names);
                            $font->valign($this->certificates_param->font_vertical_align_names);
                            $font->angle(0);
                        });
                    }
                    //titulo del curso
                    if ($this->certificates_param->fontfamily_title && $this->certificates_param->font_align_title && $this->certificates_param->font_vertical_align_title && $this->certificates_param->position_title_y) {
                        $max_width = $this->certificates_param->max_width_title;
                        $img->text($this->wrapText($course->description, $max_width), $this->certificates_param->position_title_x, $this->certificates_param->position_title_y, function ($font) {
                            $font->file(public_path('fonts' . DIRECTORY_SEPARATOR . $this->certificates_param->fontfamily_title));
                            $font->size($this->certificates_param->font_size_title);
                            $font->color($this->certificates_param->color_title);
                            $font->align($this->certificates_param->font_align_title);
                            $font->valign($this->certificates_param->font_vertical_align_title);
                            $font->angle(0);
                        });
                    }
                    // //descripcion del certificado

                    // if ($course->certificate_description && $this->certificates_param->position_description_x && $this->certificates_param->position_description_y) {
                    //     $max_width = $this->certificates_param->max_width_description;

                    //     $img->text($this->wrapText($course->certificate_description, $max_width, $this->certificates_param->interspace_description), $this->certificates_param->position_description_x, $this->certificates_param->position_description_y, function ($font) {
                    //         $font->file(public_path('fonts' . DIRECTORY_SEPARATOR . $this->certificates_param->fontfamily_description));
                    //         $font->size($this->certificates_param->font_size_description);
                    //         $font->color('#0d0603');
                    //         $font->align($this->certificates_param->font_align_description);
                    //         $font->valign($this->certificates_param->font_vertical_align_description);
                    //         $font->angle(0);
                    //     });
                    // }

                    if ($this->certificates_param->position_description_x && $this->certificates_param->position_description_y) {
                        // Descripción del certificado
                        $max_width = $this->certificates_param->max_width_description * 10; // Ancho máximo en píxeles
                        $text = $course->certificate_description;
                        $interlineado_px = $this->certificates_param->interspace_description; // Interlineado en píxeles

                        // Obtener el ancho de un solo carácter (aproximado)
                        $fontSize = $this->certificates_param->font_size_description;
                        $charWidth = $this->estimateCharWidth($fontSize); // Función para estimar el ancho de un carácter

                        // Dividir el texto en líneas según el ancho máximo en píxeles
                        $lines = $this->splitTextByPixelWidth($text, $max_width, $charWidth);

                        // Posición inicial Y para la primera línea
                        $currentY = $this->certificates_param->position_description_y;
                        //dd($fontSize, $charWidth, $lines, $currentY);

                        // Dibujar cada línea en la imagen
                        foreach ($lines as $line) {
                            $img->text($line, $this->certificates_param->position_description_x, $currentY, function ($font) {
                                $font->file(public_path('fonts' . DIRECTORY_SEPARATOR . $this->certificates_param->fontfamily_description));
                                $font->size($this->certificates_param->font_size_description);
                                $font->color($this->certificates_param->color_description);
                                $font->align($this->certificates_param->font_align_description);
                                $font->valign($this->certificates_param->font_vertical_align_description);
                                $font->angle(0);
                            });

                            // Aumentar la posición Y para la siguiente línea, sumando el interlineado
                            $currentY += $interlineado_px;
                        }
                    }
                    // //QR GENERATOR
                    $certificate = AcaCertificate::where('course_id', $course_id)
                        ->where('student_id', $student_id)
                        ->first();

                    $certificate_id = $certificate ? $certificate->id : "1"; // Si $certificate es null, asigna 1 por defecto
                    $generator = new QrCodeGenerator(300);
                    $dir = public_path() . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'tmp_qr';
                    $cadenaqr = route('aca_image_download', ['id' => $certificate_id]);


                    $qr_path = $generator->generateQR($cadenaqr, $dir, Str::random(10) . '.png', 8, 2);

                    $qr = Image::make($qr_path);
                    //$qr = Image::make('https://borealtech.com/wp-content/uploads/2018/10/codigo-qr-1024x1024-1.jpg');
                    if ($this->certificates_param->size_qr) {
                        $qr->fit($this->certificates_param->size_qr, $this->certificates_param->size_qr); //ajustar tamaño del qr
                        $img->insert($qr, $this->certificates_param->font_align_qr, $this->certificates_param->position_qr_x, $this->certificates_param->position_qr_y); //insertar qr en la imagen
                    }

                    // Ejemplo de Redimensionar la imagen manteniendo la proporción para avatares y similares
                    // Establecer el ancho máximo y la altura máxima deseados
                    $maxWidth = 1550;
                    $maxHeight = 1550;
                    $img->resize($maxWidth, $maxHeight, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });



                    // Obtener el contenido binario de la imagen
                    $imageContent = $img->encode('png');

                    //ELIMINAR el EL ARCHIVO QR generado
                    if (File::exists($qr_path)) File::delete($qr_path);

                    //Retornar la respuesta
                    return $imageContent;
                } else {
                    echo 'El estudiante fue registrado en el ' . $register->Course->description . 'pero no se le ha entregado el certificado aún';
                }
            } else {
                echo "No se encontraron registros";
            }
        }
    }

    /**
     * Divide el texto en líneas según un ancho máximo en píxeles.
     *
     * @param string $text Texto a dividir.
     * @param int $maxWidthPx Ancho máximo en píxeles.
     * @param float $charWidth Ancho aproximado de un carácter en píxeles.
     * @return array Líneas de texto.
     */
    public function splitTextByPixelWidth($text, $maxWidthPx, $charWidth)
    {
        $words = explode(' ', $text); // Dividir el texto en palabras
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            // Calcular el ancho de la línea actual más la nueva palabra
            $lineWidth = strlen($currentLine . ' ' . $word) * $charWidth;

            // Si la línea supera el ancho máximo, guardar la línea actual y empezar una nueva
            if ($lineWidth > $maxWidthPx) {
                $lines[] = trim($currentLine);
                $currentLine = $word;
            } else {
                $currentLine .= ' ' . $word;
            }
        }

        // Agregar la última línea
        if (!empty($currentLine)) {
            $lines[] = trim($currentLine);
        }

        return $lines;
    }

    /**
     * Estima el ancho de un carácter en píxeles según el tamaño de la fuente.
     *
     * @param int $fontSize Tamaño de la fuente.
     * @return float Ancho aproximado de un carácter en píxeles.
     */
    public function estimateCharWidth($fontSize)
    {
        // Esta es una estimación basada en la relación entre el tamaño de la fuente y el ancho de un carácter.
        // Puedes ajustar este valor según la fuente que estés utilizando.
        return $fontSize * 0.6; // Por ejemplo, 0.6 es un factor de escala común para fuentes proporcionales.
    }

    public function wrapText($text, $maxWidth, $lineSpacing = 2.3)
    {
        // Envolver el texto
        //dd($text);
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
