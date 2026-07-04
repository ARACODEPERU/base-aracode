<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aca_student_participations', function (Blueprint $table) {
            $table->unique(['student_id', 'course_id', 'module_id', 'theme_id', 'content_id'], 'uq_student_participation');
        });
    }

    public function down(): void
    {
        Schema::table('aca_student_participations', function (Blueprint $table) {
            $table->dropUnique('uq_student_participation');
        });
    }
};
