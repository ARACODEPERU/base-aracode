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
        Schema::create('aca_certificates_grade_config', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('certificate_id');
            $table->string('back_fontfamily_grade', 50)->nullable();
            $table->integer('back_font_size_grade')->nullable();
            $table->string('back_color_grade', 20)->default('#000000');
            $table->integer('back_position_grade_x')->nullable();
            $table->integer('back_position_grade_y')->nullable();
            $table->boolean('back_visible_grade')->default(0);
            $table->integer('back_rectangle_width')->default(100);
            $table->integer('back_rectangle_height')->default(100);
            $table->string('back_rectangle_color', 20)->default('#000000');
            $table->timestamps();

            $table->foreign('certificate_id')
                ->references('id')
                ->on('aca_certificates_parameters')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aca_certificates_grade_config');
    }
};
