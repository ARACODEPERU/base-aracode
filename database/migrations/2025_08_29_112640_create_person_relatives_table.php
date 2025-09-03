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
        Schema::create('person_relatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_one_id')->constrained('people')->onDelete('cascade');
            $table->foreignId('person_two_id')->constrained('people')->onDelete('cascade');
            $table->string('relationship')->nullable()->comment('nombre del parentesco que tienen');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('person_relatives');
    }
};
