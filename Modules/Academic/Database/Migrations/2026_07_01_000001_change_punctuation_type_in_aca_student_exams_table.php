<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cambiar el campo punctuation de VARCHAR(2) a DECIMAL(5,2)
        // para permitir decimales y evitar truncamiento de valores > 99
        DB::statement('ALTER TABLE aca_student_exams CHANGE punctuation punctuation DECIMAL(5,2) NULL DEFAULT NULL');
    }

    public function down(): void
    {
        // Revertir a VARCHAR(2) - los valores decimales se truncarán
        DB::statement('ALTER TABLE aca_student_exams CHANGE punctuation punctuation VARCHAR(2) NULL DEFAULT NULL');
    }
};
