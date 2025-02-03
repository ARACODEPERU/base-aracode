<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('aca_certificates_parameters', function (Blueprint $table) {
            // Cambiar el tipo de dato de certificate_img de text a string
            $table->string('certificate_img')->comment('Ruta o URL de la imagen del certificado')->change();

            // Agregar comentarios a las columnas
            Schema::table('aca_certificates_parameters', function (Blueprint $table) {
                $table->string('name_certificate')->nullable();
                $table->boolean('state')->default(true);
                // Cambiar el tipo de dato de certificate_img de text a string y hacerla nulleable
                $table->string('certificate_img')->nullable()->comment('Ruta o URL de la imagen del certificado')->change();

                // Hacer todas las columnas nulleables y agregar comentarios
                $table->unsignedBigInteger('course_id')->nullable()->comment('ID del curso asociado al certificado')->change();
                $table->string('fontfamily_date')->nullable()->comment('Fuente utilizada para la fecha /fonts/fuente.ttf')->change();
                $table->string('font_align_date')->nullable()->comment('Alineación horizontal de fecha left, center, right')->change();
                $table->string('font_vertical_align_date')->nullable()->comment('top, center, bottom')->change();
                $table->integer('position_date_x')->nullable()->comment('Posición en píxeles respecto al eje X para la fecha')->change();
                $table->integer('position_date_y')->nullable()->comment('Posición en píxeles respecto al eje Y para la fecha')->change();
                $table->integer('font_size_date')->nullable()->comment('Tamaño de fuente para la fecha ejem. 22, 23 ,etc')->change();
                $table->string('fontfamily_names')->nullable()->comment('Fuente utilizada para los nombres en el certificado /fonts/fuente.ttf')->change();
                $table->string('font_align_names')->nullable()->comment('Alineación horizontal left, center, right')->change();
                $table->string('font_vertical_align_names')->nullable()->comment('Alineación vertical del texto de los nombres')->change();
                $table->integer('position_names_x')->nullable()->comment('Posición en píxeles respecto al eje X para los nombres')->change();
                $table->integer('position_names_y')->nullable()->comment('Posición en píxeles respecto al eje Y para los nombres')->change();
                $table->integer('font_size_names')->nullable()->comment('Tamaño de fuente para los nombres ejem. 22, 23 ,etc')->change();
                $table->string('fontfamily_title')->nullable()->comment('Fuente utilizada para el título en el certificado')->change();
                $table->string('font_align_title')->nullable()->comment('left, center, right')->change();
                $table->string('font_vertical_align_title')->nullable()->comment('Alineación vertical del texto del título')->change();
                $table->integer('position_title_x')->nullable()->comment('Posición en píxeles respecto al eje X para el título')->change();
                $table->integer('position_title_y')->nullable()->comment('Posición en píxeles respecto al eje Y para el título')->change();
                $table->integer('font_size_title')->nullable()->comment('Tamaño de fuente ejem. 22, 23 ,etc')->change();
                $table->integer('max_width_title')->nullable()->comment('Ancho máximo en píxeles para el título 900')->change();
                $table->integer('position_qr_x')->nullable()->comment('Posición en píxeles respecto al eje X para el código QR')->change();
                $table->integer('position_qr_y')->nullable()->comment('Posición en píxeles respecto al eje Y para el código QR')->change();
                $table->integer('size_qr')->nullable()->comment('Tamaño en píxeles del código QR')->change();
                $table->string('font_align_qr')->nullable()->comment('top-right, top-left, bottom-left, bottom-right')->change();
                $table->string('fontfamily_description')->nullable()->comment('Fuente utilizada para la descripción en el certificado')->change();
                $table->string('font_align_description')->nullable()->comment('Alineación horizontal left, center, right')->change();
                $table->string('font_vertical_align_description')->nullable()->comment('Alineación vertical top, center, bottom')->change();
                $table->integer('position_description_x')->nullable()->comment('Posición en píxeles respecto al eje X para la descripción')->change();
                $table->integer('position_description_y')->nullable()->comment('Posición en píxeles respecto al eje Y para la descripción')->change();
                $table->integer('font_size_description')->nullable()->comment('Tamaño de fuente ejem. 22, 23 ,etc')->change();
                $table->integer('max_width_description')->nullable()->comment('Ancho máximo en píxeles 800 por ejemplo')->change();
                $table->double('interspace_description', 4, 1)->nullable()->comment('Espaciado entre líneas ejem. 2.5')->change();
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('aca_certificates_parameters', function (Blueprint $table) {
            // Revertir el cambio de certificate_img a text
            $table->text('certificate_img')->comment('')->change();

            // Eliminar comentarios (opcional, no es necesario revertir los comentarios)
            $columns = [
                'course_id',
                'fontfamily_date',
                'font_align_date',
                'font_vertical_align_date',
                'position_date_x',
                'position_date_y',
                'font_size_date',
                'fontfamily_names',
                'font_align_names',
                'font_vertical_align_names',
                'position_names_x',
                'position_names_y',
                'font_size_names',
                'fontfamily_title',
                'font_align_title',
                'font_vertical_align_title',
                'position_title_x',
                'position_title_y',
                'font_size_title',
                'max_width_title',
                'position_qr_x',
                'position_qr_y',
                'size_qr',
                'font_align_qr',
                'fontfamily_description',
                'font_align_description',
                'font_vertical_align_description',
                'position_description_x',
                'position_description_y',
                'font_size_description',
                'max_width_description',
                'interspace_description',
            ];

            foreach ($columns as $column) {
                $table->{$column}()->comment('')->change();
            }
        });
    }
};
