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
        Schema::create('event_edition_match_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id', 'match_report_match_id_fk')->constrained('event_edition_matches')->onDelete('cascade');
            $table->string('minutes_code', 15)->nullable()->comment('codigo de acta');
            // Resumen de goles para evitar re-calcular siempre
            $table->integer('local_score')->default(0);
            $table->integer('visitor_score')->default(0);
            // Datos del cierre
            $table->json('referees')->nullable(); // Árbitro
            $table->text('observations')->nullable(); // Ej: "El partido se suspendió al min 80"
            $table->dateTime('closed_at');
            $table->foreignId('closed_by')->constrained('users'); // Usuario que cerró el acta
            // Datos del Reclamo
            $table->boolean('has_protest')->default(false); // ¿Hubo reclamo en cancha?
            $table->json('protest_details')->nullable(); // Descripción del reclamo
            // Resolución (Esto lo llena el comité de justicia después)
            $table->enum('protest_status', ['none', 'pending', 'resolved', 'rejected'])->default('none');
            $table->text('resolution_details')->nullable();
            $table->boolean('points_processed')->default(false); // Para saber si ya sumó a la tabla
            $table->string('minutes_file')->nullable()->comment('archivo subido del acata firmada');
            $table->boolean('payment_arvitraje_h')->default(false)->comment('si pago el arvitraje');
            $table->boolean('payment_arvitraje_a')->default(false)->comment('si pago el arvitraje');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_edition_match_reports');
    }
};
