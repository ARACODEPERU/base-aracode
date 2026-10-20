<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aca_school_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('aca_schools')->cascadeOnDelete();
            $table->foreignId('year_id')->constrained('aca_school_years')->cascadeOnDelete();
            $table->string('organization_type', 20)->comment('bimestral|trimestral|cuatrimestral|semestral|personalizada');
            $table->string('name', 100)->comment('Ej: Bimestre 1, Trimestre I');
            $table->unsignedTinyInteger('number')->comment('Orden 1..N');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('status')->default(true);
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'year_id', 'number']);
            $table->unique(['school_id', 'year_id', 'name']);
            $table->index(['school_id', 'year_id']);
            $table->index('start_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_school_periods');
    }
};
