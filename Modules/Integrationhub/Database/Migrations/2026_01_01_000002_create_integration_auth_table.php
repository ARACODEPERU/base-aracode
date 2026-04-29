<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_auth', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained('integrations')->onDelete('cascade');
            $table->string('field_name', 50)->comment('Nombre del campo de autenticación (token, api_key, username, etc.)');
            $table->text('field_value')->nullable()->comment('Valor del campo (encriptado para seguridad)');
            $table->enum('field_type', ['text', 'password', 'token', 'api_key', 'oauth'])->default('text')->comment('Tipo de campo: text, password, token, api_key, oauth');
            $table->boolean('is_enabled')->default(true)->comment('Si el campo está habilitado para usar (1=si, 0=no)');
            $table->integer('sort_order')->default(0)->comment('Orden de prioridad en la autenticación');
            $table->timestamps();

            $table->index('integration_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_auth');
    }
};