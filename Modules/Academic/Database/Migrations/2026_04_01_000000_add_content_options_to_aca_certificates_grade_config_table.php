<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aca_certificates_grade_config', function (Blueprint $table) {
            $table->boolean('back_show_exam_grade')->default(false)->after('back_rectangle_color');
            $table->boolean('back_show_themes')->default(true)->after('back_show_exam_grade');
            $table->string('back_exam_fontfamily', 50)->nullable()->after('back_show_themes');
            $table->integer('back_exam_font_size')->nullable()->after('back_exam_fontfamily');
            $table->string('back_exam_color', 20)->default('#000000')->after('back_exam_font_size');
        });
    }

    public function down(): void
    {
        Schema::table('aca_certificates_grade_config', function (Blueprint $table) {
            $table->dropColumn([
                'back_show_exam_grade',
                'back_show_themes',
                'back_exam_fontfamily',
                'back_exam_font_size',
                'back_exam_color',
            ]);
        });
    }
};
