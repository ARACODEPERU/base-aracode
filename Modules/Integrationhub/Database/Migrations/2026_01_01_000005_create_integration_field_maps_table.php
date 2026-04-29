<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_field_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('endpoint_id')->constrained('integration_endpoints')->onDelete('cascade');
            $table->string('field_key', 100)->comment('Clave en el body destino (ej: customer_name)');
            $table->string('field_value', 255)->comment('Valor o referencia ({{name}} o valor estático)');
            $table->enum('field_type', ['static', 'table_field', 'query', 'computed', 'custom'])->default('table_field')->comment('Tipo de campo: static, table_field, query, computed, custom');
            $table->enum('source_type', ['static', 'database', 'query', 'function'])->default('database')->comment('Origen del valor: static, database, query, function');
            $table->string('source_table', 100)->nullable()->comment('Tabla de origen en la BD');
            $table->string('source_field', 100)->nullable()->comment('Campo de origen en la tabla');
            $table->string('default_value', 255)->nullable()->comment('Valor por defecto si el campo está vacío');
            $table->boolean('is_enabled')->default(true)->comment('Si el mapeo está habilitado (1=si, 0=no)');
            $table->json('transform_rule')->nullable()->comment('Reglas de transformación JSON (uppercase, date_format, etc.)');
            $table->integer('sort_order')->default(0)->comment('Orden de procesamiento del mapeo');
            $table->timestamps();

            $table->index('endpoint_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_field_maps');
    }
};