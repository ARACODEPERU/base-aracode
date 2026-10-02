<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aca_school_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('aca_schools')->cascadeOnDelete();
            $table->unsignedBigInteger('person_id');
            $table->string('student_code', 40)->nullable()->comment('Codigo interno del alumno en el colegio');
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'person_id']);
            $table->foreign('person_id')->references('id')->on('people')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('aca_school_students', function (Blueprint $table) {
            $table->dropForeign(['person_id']);
        });
        Schema::dropIfExists('aca_school_students');
    }
};
