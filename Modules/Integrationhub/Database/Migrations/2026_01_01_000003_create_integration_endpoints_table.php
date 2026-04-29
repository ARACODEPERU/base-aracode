<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained('integrations')->onDelete('cascade');
            $table->string('name', 100)->comment('Nombre descriptivo del endpoint');
            $table->string('endpoint_path', 255)->comment('Ruta del endpoint (ej: /api/v1/customers)');
            $table->enum('http_method', ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'])->default('POST')->comment('Método HTTP: GET, POST, PUT, DELETE, PATCH');
            $table->enum('body_type', ['json', 'xml', 'form', 'raw', 'none'])->default('json')->comment('Tipo de cuerpo: json, xml, form, raw, none');
            $table->boolean('is_active')->default(true)->comment('Si el endpoint está activo (1=si, 0=no)');
            $table->integer('sort_order')->default(0)->comment('Orden de visualización en la lista');
            $table->timestamps();

            $table->index('integration_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_endpoints');
    }
};