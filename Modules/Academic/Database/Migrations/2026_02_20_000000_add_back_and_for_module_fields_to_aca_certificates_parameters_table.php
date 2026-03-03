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
            // Campo para identificar si es certificado para módulos
            $table->boolean('for_module')->default(false)->nullable()
                ->after('certificate_img_finished')
                ->comment('Indica si el certificado es para módulos');

            // Imagen generada del reverso
            $table->string('back_certificate_img_finished')->nullable()
                ->after('for_module')
                ->comment('Imagen generada del reverso del certificado');

            // Imagen del reverso
            $table->string('back_certificate_img')->nullable()
                ->after('back_certificate_img_finished')
                ->comment('Imagen del reverso del certificado');

            // Descripción manual del reverso
            $table->text('back_description')->nullable()
                ->after('back_certificate_img')
                ->comment('Descripción manual para el reverso');

            // Contenido a mostrar en el reverso
            $table->boolean('back_content_show_manual')->default(true)->nullable()
                ->after('back_description')
                ->comment('Mostrar descripción manual en reverso');
            $table->boolean('back_content_show_course')->default(false)->nullable()
                ->after('back_content_show_manual')
                ->comment('Mostrar contenido del curso en reverso');
            $table->boolean('back_content_show_module')->default(false)->nullable()
                ->after('back_content_show_course')
                ->comment('Mostrar contenido del módulo en reverso');

            // Configuración de fecha para reverso
            $table->string('back_fontfamily_date')->nullable()->after('back_content_show_module');
            $table->string('back_font_align_date')->nullable()->after('back_fontfamily_date');
            $table->string('back_font_vertical_align_date')->nullable()->after('back_font_align_date');
            $table->string('back_position_date_x')->nullable()->after('back_font_vertical_align_date');
            $table->string('back_position_date_y')->nullable()->after('back_position_date_x');
            $table->string('back_font_size_date')->nullable()->after('back_position_date_y');
            $table->string('back_color_date', 20)->nullable()->after('back_font_size_date');
            $table->boolean('back_visible_date')->default(true)->nullable()->after('back_color_date');

            // Configuración de nombres para reverso
            $table->string('back_fontfamily_names')->nullable()->after('back_visible_date');
            $table->string('back_font_align_names')->nullable()->after('back_fontfamily_names');
            $table->string('back_font_vertical_align_names')->nullable()->after('back_font_align_names');
            $table->string('back_position_names_x')->nullable()->after('back_font_vertical_align_names');
            $table->string('back_position_names_y')->nullable()->after('back_position_names_x');
            $table->string('back_font_size_names')->nullable()->after('back_position_names_y');
            $table->string('back_color_names', 20)->nullable()->after('back_font_size_names');
            $table->boolean('back_visible_names')->default(true)->nullable()->after('back_color_names');

            // Configuración de título para reverso
            $table->string('back_fontfamily_title')->nullable()->after('back_visible_names');
            $table->string('back_font_align_title')->nullable()->after('back_fontfamily_title');
            $table->string('back_font_vertical_align_title')->nullable()->after('back_font_align_title');
            $table->string('back_position_title_x')->nullable()->after('back_font_vertical_align_title');
            $table->string('back_position_title_y')->nullable()->after('back_position_title_x');
            $table->string('back_font_size_title')->nullable()->after('back_position_title_y');
            $table->string('back_max_width_title')->nullable()->after('back_font_size_title');
            $table->string('back_color_title', 20)->nullable()->after('back_max_width_title');
            $table->boolean('back_visible_title')->default(true)->nullable()->after('back_color_title');

            // Configuración de descripción para reverso
            $table->string('back_fontfamily_description')->nullable()->after('back_visible_title');
            $table->string('back_font_align_description')->nullable()->after('back_fontfamily_description');
            $table->string('back_font_vertical_align_description')->nullable()->after('back_font_align_description');
            $table->string('back_position_description_x')->nullable()->after('back_font_vertical_align_description');
            $table->string('back_position_description_y')->nullable()->after('back_position_description_x');
            $table->string('back_font_size_description')->nullable()->after('back_position_description_y');
            $table->string('back_max_width_description')->nullable()->after('back_font_size_description');
            $table->string('back_color_description', 20)->nullable()->after('back_max_width_description');
            $table->boolean('back_visible_description')->default(true)->nullable()->after('back_color_description');

            // Contenido del curso para reverso
            $table->string('back_fontfamily_course')->nullable()->after('back_visible_description');
            $table->string('back_font_align_course')->nullable()->after('back_fontfamily_course');
            $table->string('back_font_vertical_align_course')->nullable()->after('back_font_align_course');
            $table->string('back_position_course_x')->nullable()->after('back_font_vertical_align_course');
            $table->string('back_position_course_y')->nullable()->after('back_position_course_x');
            $table->string('back_font_size_course')->nullable()->after('back_position_course_y');
            $table->string('back_max_width_course')->nullable()->after('back_font_size_course');
            $table->string('back_color_course', 20)->nullable()->after('back_max_width_course');
            $table->boolean('back_visible_course')->default(true)->nullable()->after('back_color_course');

            // Contenido del módulo para reverso
            $table->string('back_fontfamily_module')->nullable()->after('back_visible_course');
            $table->string('back_font_align_module')->nullable()->after('back_fontfamily_module');
            $table->string('back_font_vertical_align_module')->nullable()->after('back_font_align_module');
            $table->string('back_position_module_x')->nullable()->after('back_font_vertical_align_module');
            $table->string('back_position_module_y')->nullable()->after('back_position_module_x');
            $table->string('back_font_size_module')->nullable()->after('back_position_module_y');
            $table->string('back_max_width_module')->nullable()->after('back_font_size_module');
            $table->string('back_color_module', 20)->nullable()->after('back_max_width_module');
            $table->boolean('back_visible_module')->default(true)->nullable()->after('back_color_module');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aca_certificates_parameters', function (Blueprint $table) {
            $table->dropColumn('back_visible_description');
            $table->dropColumn('back_color_description');
            $table->dropColumn('back_max_width_description');
            $table->dropColumn('back_font_size_description');
            $table->dropColumn('back_position_description_y');
            $table->dropColumn('back_position_description_x');
            $table->dropColumn('back_font_vertical_align_description');
            $table->dropColumn('back_font_align_description');
            $table->dropColumn('back_fontfamily_description');
            
            $table->dropColumn('back_visible_title');
            $table->dropColumn('back_color_title');
            $table->dropColumn('back_max_width_title');
            $table->dropColumn('back_font_size_title');
            $table->dropColumn('back_position_title_y');
            $table->dropColumn('back_position_title_x');
            $table->dropColumn('back_font_vertical_align_title');
            $table->dropColumn('back_font_align_title');
            $table->dropColumn('back_fontfamily_title');
            
            $table->dropColumn('back_visible_names');
            $table->dropColumn('back_color_names');
            $table->dropColumn('back_font_size_names');
            $table->dropColumn('back_position_names_y');
            $table->dropColumn('back_position_names_x');
            $table->dropColumn('back_font_vertical_align_names');
            $table->dropColumn('back_font_align_names');
            $table->dropColumn('back_fontfamily_names');
            
            $table->dropColumn('back_visible_date');
            $table->dropColumn('back_color_date');
            $table->dropColumn('back_font_size_date');
            $table->dropColumn('back_position_date_y');
            $table->dropColumn('back_position_date_x');
            $table->dropColumn('back_font_vertical_align_date');
            $table->dropColumn('back_font_align_date');
            $table->dropColumn('back_fontfamily_date');
            
            $table->dropColumn('back_visible_module');
            $table->dropColumn('back_color_module');
            $table->dropColumn('back_max_width_module');
            $table->dropColumn('back_font_size_module');
            $table->dropColumn('back_position_module_y');
            $table->dropColumn('back_position_module_x');
            $table->dropColumn('back_font_vertical_align_module');
            $table->dropColumn('back_font_align_module');
            $table->dropColumn('back_fontfamily_module');
            
            $table->dropColumn('back_visible_course');
            $table->dropColumn('back_color_course');
            $table->dropColumn('back_max_width_course');
            $table->dropColumn('back_font_size_course');
            $table->dropColumn('back_position_course_y');
            $table->dropColumn('back_position_course_x');
            $table->dropColumn('back_font_vertical_align_course');
            $table->dropColumn('back_font_align_course');
            $table->dropColumn('back_fontfamily_course');
            
            $table->dropColumn('back_content_show_module');
            $table->dropColumn('back_content_show_course');
            $table->dropColumn('back_content_show_manual');
            $table->dropColumn('back_description');
            $table->dropColumn('back_certificate_img');
            $table->dropColumn('back_certificate_img_finished');
            $table->dropColumn('for_module');
        });
    }
};
