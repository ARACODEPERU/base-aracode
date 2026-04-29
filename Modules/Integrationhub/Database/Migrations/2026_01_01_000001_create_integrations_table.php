<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('Nombre del proveedor/sistema externo');
            $table->string('url_base', 500)->comment('URL base de la API externa (ej: https://api.ejemplo.com)');
            $table->text('description')->nullable()->comment('Descripción opcional de la integración');
            $table->enum('execution_type', ['manual', 'scheduled', 'webhook'])->default('manual')->comment('Tipo de ejecución: manual, programada o webhook');
            $table->boolean('is_active')->default(true)->comment('Estado de la integración (1=activa, 0=inactiva)');
            $table->json('config')->nullable()->comment('Configuración adicional en formato JSON (timeout, retry, etc.)');
            $table->timestamp('last_executed_at')->nullable()->comment('Última fecha de ejecución de la integración');
            $table->timestamps();

            $table->index('name');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};