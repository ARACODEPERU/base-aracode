<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aca_school_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('aca_schools')->cascadeOnDelete();
            $table->foreignId('level_id')->constrained('aca_school_levels')->cascadeOnDelete();
            $table->string('name', 100)->comment('Ej: 3 años, 1°, 2° ...');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index(['school_id', 'level_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_school_grades');
    }
};
