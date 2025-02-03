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
        Schema::create('aca_certificates_parameters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->foreign('course_id')->references('id')->on('aca_courses');
            $table->text('certificate_img');
            $table->text('fontfamily_date');
            $table->text('font_align_date');
            $table->text('font_vertical_align_date');
            $table->integer('position_date_x');
            $table->integer('position_date_y');
            $table->integer('font_size_date');
            $table->text('fontfamily_names');
            $table->text('font_align_names');
            $table->text('font_vertical_align_names');
            $table->integer('position_names_x');
            $table->integer('position_names_y');
            $table->integer('font_size_names');
            $table->text('fontfamily_title');
            $table->text('font_align_title');
            $table->text('font_vertical_align_title');
            $table->integer('position_title_x');
            $table->integer('position_title_y');
            $table->integer('font_size_title');
            $table->integer('max_width_title');
            $table->text('position_qr_x');
            $table->text('position_qr_y');
            $table->integer('size_qr'); //tamaño 200 a más solo lo recomiendo.
            $table->text('font_align_qr');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aca_certificates_parameters');
    }
};
