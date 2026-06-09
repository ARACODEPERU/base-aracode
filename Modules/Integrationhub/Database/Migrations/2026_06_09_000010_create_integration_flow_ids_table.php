<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_flow_ids', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()->comment('Identificador único del tipo de flujo (ej: birthday_greeting)');
            $table->string('flow_id', 255)->comment('ID del flujo o plantilla en la API externa');
            $table->string('label', 255)->nullable()->comment('Nombre descriptivo visible en la UI');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_flow_ids');
    }
};
