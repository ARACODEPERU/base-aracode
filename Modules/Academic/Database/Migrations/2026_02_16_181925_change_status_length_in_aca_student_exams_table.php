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
        Schema::table('aca_student_exams', function (Blueprint $table) {
            // Aumentamos el tamaño a 50 para estar seguros
            $table->string('status', 50)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aca_student_exams', function (Blueprint $table) {
            // Volver al tamaño original si fuera necesario
            $table->string('status', 10)->change();
        });
    }
};
