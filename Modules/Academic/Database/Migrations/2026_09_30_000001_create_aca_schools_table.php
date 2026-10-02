<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aca_schools', function (Blueprint $table) {
            $table->id();
            $table->string('name', 300);
            $table->string('modular_code', 20)->nullable()->comment('Codigo modular MINEDU');
            $table->string('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('logo')->nullable();
            $table->boolean('is_default')->default(false)->comment('Colegio por defecto para el modo mono-colegio');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_schools');
    }
};
