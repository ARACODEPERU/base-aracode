<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_exec_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained('integrations')->onDelete('cascade');
            $table->foreignId('endpoint_id')->nullable()->constrained('integration_endpoints')->onDelete('set null');
            $table->timestamp('executed_at')->comment('Fecha y hora de ejecución');
            $table->enum('status', ['success', 'failed', 'partial'])->default('success')->comment('Estado: success, failed, partial');
            $table->json('request_payload')->nullable()->comment('Cuerpo de la petición en formato JSON');
            $table->json('response_body')->nullable()->comment('Respuesta de la API en formato JSON');
            $table->integer('response_status_code')->nullable()->comment('Código de estado HTTP (200, 400, 500, etc.)');
            $table->text('error_message')->nullable()->comment('Mensaje de error si la ejecución falló');
            $table->integer('execution_time_ms')->default(0)->comment('Tiempo de ejecución en milisegundos');
            $table->timestamps();

            $table->index('integration_id');
            $table->index('executed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_exec_logs');
    }
};