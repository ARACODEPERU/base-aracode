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
        Schema::create('event_edition_accordances', function (Blueprint $table) {
            $table->id();
            // Datos del cierre
            $table->foreignId('edition_id')->constrained('event_editions')->onDelete('cascade');
            $table->string('minutes_subject', 300)->nullable();
            $table->enum('minutes_type', ['delegados','extraordinaria','ordinaria','comite'])->nullable()->comment('codigo de acta');
            $table->string('minutes_code', 15)->nullable()->comment('codigo de acta');
            $table->dateTime('meeting_date')->nullable();
            $table->json('participants')->nullable(); // id, nombre delegados
            $table->binary('minutes_body')->nullable(); // Ej: "El partido se suspendió al min 80"
            $table->foreignId('user_by')->constrained('users'); // Usuario que cerró el acta
            $table->string('minutes_file_name')->nullable()->comment('nombre real archivo subido del acata firmada');
            $table->string('minutes_file_path')->nullable()->comment('ruta archivo subido del acata firmada');
            $table->enum('status', ['none', 'pending', 'accepted'])->default('none');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_edition_accordances');
    }
};
