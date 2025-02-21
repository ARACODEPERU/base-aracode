<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('aca_certificates_parameters', function (Blueprint $table) {
            $table->string('color_date', 20)->default('#0d0603')->nullable()
                ->after('font_size_date')
                ->comment('color para la fecha');
            $table->boolean('visible_date')->default(true)
                ->after('color_date')
                ->comment('para visualizar o no la fecha');
            ///nombre estudiante
            $table->string('color_names', 20)->default('#0d0603')->nullable()
                ->after('font_size_names')
                ->comment('color de letra para nombre de estudiante');
            $table->boolean('visible_names')->default(true)
                ->after('color_names')
                ->comment('para visualizar nombre estudiante');
            ////titulo o nombre del curso
            $table->string('color_title', 20)->default('#0d0603')->nullable()
                ->after('max_width_title')
                ->comment('color de letra para el titulo nombre de curso');
            $table->boolean('visible_title')->default(true)
                ->after('color_title')
                ->comment('para visualizar el nombre del curso');
            ////descripcion contenido o detalle del certificado
            $table->string('color_description', 20)->default('#0d0603')->nullable()
                ->after('font_size_description')
                ->comment('color de letra para la descripcion');
            $table->boolean('visible_description')->default(true)
                ->after('color_description')
                ->comment('para visualizar la descripcion');
            ////imagen QR
            $table->boolean('visible_image_qr')->default(true)
                ->after('font_align_qr')
                ->comment('para visualizar la imagen qr');
            ///// imagen terminada del certificado
            $table->string('certificate_img_finished')->nullable()
                ->after('state')
                ->comment('para guardar la imagen del certificado terminado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aca_certificates_parameters', function (Blueprint $table) {
            $table->dropColumn('certificate_img_finished');
            $table->dropColumn('visible_image_qr');
            $table->dropColumn('visible_description');
            $table->dropColumn('color_description');
            $table->dropColumn('visible_title');
            $table->dropColumn('color_title');
            $table->dropColumn('visible_names');
            $table->dropColumn('color_names');
            $table->dropColumn('visible_date');
            $table->dropColumn('color_date');
        });
    }
};
