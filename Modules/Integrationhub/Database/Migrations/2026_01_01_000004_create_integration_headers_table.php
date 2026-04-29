<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_headers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained('integrations')->onDelete('cascade');
            $table->string('header_name', 50)->comment('Nombre del header HTTP (Content-Type, Authorization, etc.)');
            $table->string('header_value', 255)->nullable()->comment('Valor del header');
            $table->boolean('is_enabled')->default(true)->comment('Si el header está habilitado (1=si, 0=no)');
            $table->integer('sort_order')->default(0)->comment('Orden de prioridad');
            $table->timestamps();

            $table->index('integration_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_headers');
    }
};