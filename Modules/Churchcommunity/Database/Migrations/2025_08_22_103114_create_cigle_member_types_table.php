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
        Schema::create('cigle_member_types', function (Blueprint $table) {
            $table->id();
            $table->string('description')->comment('nombre o descripcion del tipo de miembro');
            $table->string('short')->nullable()->comment('nombre corto o siglas');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cigle_member_types');
    }
};
