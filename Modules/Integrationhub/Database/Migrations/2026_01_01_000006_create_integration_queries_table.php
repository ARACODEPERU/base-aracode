<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained('integrations')->onDelete('cascade');
            $table->string('query_name', 100)->comment('Nombre descriptivo de la consulta');
            $table->text('query_sql')->comment('Consulta SQL personalizada');
            $table->enum('query_type', ['select', 'raw_sql'])->default('select')->comment('Tipo de query: select o raw_sql');
            $table->json('parameters')->nullable()->comment('Parámetros dinámicos en formato JSON');
            $table->boolean('is_active')->default(true)->comment('Si la consulta está activa (1=si, 0=no)');
            $table->timestamps();

            $table->index('integration_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_queries');
    }
};