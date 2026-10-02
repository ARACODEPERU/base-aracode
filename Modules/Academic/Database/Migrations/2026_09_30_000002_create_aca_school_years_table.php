<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aca_school_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('aca_schools')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->string('status', 20)->default('announced')->comment('announced|active|finished');
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'year']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_school_years');
    }
};
