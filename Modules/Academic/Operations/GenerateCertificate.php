<?php

namespace Modules\Academic\Operations;

use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaCertificateParameter;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaStudent;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Response;
use App\Helpers\Invoice\QrCodeGenerator;
use Carbon\Carbon;

class GenerateCertificate
{
    public $certificates_param;

    public function testimage($student_id, $course_id)
    {
        $register = AcaCapRegistration::where('student_id', $student_id)->where('course_id', $course_id)->first();

        if ($register) {
            if ($register->certificate_date != null) {

                $student = AcaStudent::with('person')->find($student_id);
                //dd($student->person->full_name);
                $this->certificates_param = AcaCertificateParameter::where('course_id', $course_id)->first();

                // Verificar si se obtuvieron resultados en la consulta si no se obtiene se crea certificado por defecto con ID 1
                if (!$this->certificates_param) $this->certificates_param = AcaCertificateParameter::find(1);
                $this->certificates_param->Course = AcaCourse::find($course_id);


                //dd($this->certificates_param);
                // create Image from file
                $img = Image::make($this->certificates_param->certificate_img);
                $fecha = $register->certificate_date; //Esta fecha debe obtenerse del registro de la matricula del estudiante al curso respectivo donde se obtiene la fecha de entrega del certificado si es null entonces no tiene certificado

                if ($register->certificate_date) {
                    $fecha = Carbon::parse($register->certificate_date)->format('d-m-Y');
                } else {
                    $fecha = 'Sin certificado';
                }

                //las fuentes deben estar en la carpeta public/fonts y en la base de datos debe registrarse el nombre de la fuente y su extensión
                //recomiendo usar fuentes de google fonts porque son gratis y puedes descargarlas

                $img->text($student->person->full_name, $this->certificates_param->position_names_x, $this->certificates_param->position_names_y, function ($font) {
                    $font->file($this->certificates_param->fontfamily_names);
                    $font->size($this->certificates_param->font_size_names);
                    $font->color($this->certificates_param->color_names);
                    $font->align($this->certificates_param->font_align_names);
                    $font->valign($this->certificates_param->font_vertical_align_names);
                    $font->angle(0);
                });



                $img->text("Lima, " . $fecha, $this->certificates_param->position_date_x, $this->certificates_param->position_date_y, function ($font) {
                    $font->file($this->certificates_param->fontfamily_date);
                    $font->size($this->certificates_param->font_size_date);
                    $font->color($this->certificates_param->color_date);
                    $font->align($this->certificates_param->font_align_date);
                    $font->valign($this->certificates_param->font_vertical_align_date);
                    $font->angle(0);
                });
                $max_width = $this->certificates_param->max_width_title;

                $img->text($this->wrapText($this->certificates_param->Course->description, $max_width), $this->certificates_param->position_title_x, $this->certificates_param->position_title_y, function ($font) {
                    $font->file($this->certificates_param->fontfamily_title);
                    $font->size($this->certificates_param->font_size_title);
                    $font->color($this->certificates_param->color_title);
                    $font->align($this->certificates_param->font_align_title);
                    $font->valign($this->certificates_param->font_vertical_align_title);
                    $font->angle(0);
                });

                //descripcion del certificado
                $max_width = $this->certificates_param->max_width_description;
                $img->text($this->wrapText($this->certificates_param->Course->certificate_description, $max_width, $this->certificates_param->interspace_description), $this->certificates_param->position_description_x, $this->certificates_param->position_description_y, function ($font) {
                    $font->file($this->certificates_param->fontfamily_description);
                    $font->size($this->certificates_param->font_size_description);
                    $font->color($this->certificates_param->color_description);
                    $font->align($this->certificates_param->font_align_description);
                    $font->valign($this->certificates_param->font_vertical_align_description);
                    $font->angle(0);
                });

                // //QR GENERATOR
                $generator = new QrCodeGenerator(300);
                $dir = public_path() . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'tmp_qr';
                $cadenaqr = env('APP_URL') . '/test-image/' . $student_id . '/' . $course_id;

                $qr_path = $generator->generateQR($cadenaqr, $dir, Str::random(10) . '.png', 8, 2);
                $qr = Image::make($qr_path);
                //$qr = Image::make('https://borealtech.com/wp-content/uploads/2018/10/codigo-qr-1024x1024-1.jpg');
                $qr->fit($this->certificates_param->size_qr, $this->certificates_param->size_qr); //ajustar tamaño del qr
                $img->insert($qr, $this->certificates_param->font_align_qr, $this->certificates_param->position_qr_x, $this->certificates_param->position_qr_y); //insertar qr en la imagen


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


                // Generar la respuesta HTTP con la imagen
                $response = Response::make($imageContent);

                // Establecer el tipo de contenido de la respuesta como imagen PNG
                $response->header('Content-Type', 'image/png');

                //ELIMINAR el EL ARCHIVO QR generado
                if (File::exists($qr_path)) Fil::delete($qr_path);

                //Retornar la respuesta
                return $response;
            } else {
                echo 'El estudiante fue registrado en el ' . $register->Course->description . ' pero no se le ha entregado el certificado aún';
            }
        } else {
            echo "No se encontraron registros";
        }
    }

    public function wrapText($text, $maxWidth, $lineSpacing = 2.3)
    {
        // Envolver el texto
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
