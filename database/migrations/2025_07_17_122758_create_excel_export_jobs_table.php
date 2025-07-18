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
        Schema::create('excel_export_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Relación con la tabla users
            $table->string('report_type'); // Ej: 'ventas', 'productos', 'clientes'
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->string('file_name')->nullable(); // Nombre del archivo generado
            $table->string('file_path')->nullable(); // Ruta interna del archivo en storage
            $table->string('download_url')->nullable(); // URL pública para descarga
            $table->text('error_message')->nullable(); // Mensaje de error si falla
            $table->integer('progress')->default(0); // Progreso del 0 al 100
            $table->json('filters')->nullable(); // Filtros usados para la exportación (JSON)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('excel_export_jobs');
    }
};
