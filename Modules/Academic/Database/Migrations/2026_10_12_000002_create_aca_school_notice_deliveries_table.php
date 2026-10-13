<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registro de cada aviso de asistencia enviado (o intentado) al apoderado.
     *
     * La clave unica (asistencia de porteria, evento y destinatario) es la
     * garantia de no duplicar: cuando la porteria registra la entrada o la
     * salida solo se encola el aviso si esa combinacion no existe todavia,
     * aunque el worker aun no haya terminado. Un alumno que pasa varias veces
     * por la puerta en el mismo dia no vuelve a avisar por el mismo evento.
     *
     * Estados: pending (encolada), processing (el worker la esta enviando),
     * sent (enviada), skipped (no se puede enviar: canal apagado o sin datos) y
     * failed (rechazo de SMSGate o reintentos agotados).
     */
    public function up(): void
    {
        Schema::create('aca_school_notice_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gate_attendance_id')->constrained('aca_school_gate_attendances')->cascadeOnDelete();
            $table->unsignedBigInteger('student_id')->index();
            // Persona apoderada destinataria (puede ser null si el aviso sale al
            // telefono de la matricula).
            $table->unsignedBigInteger('guardian_person_id')->nullable();
            // Identidad del destinatario dentro de la asistencia y el evento:
            // apoderado id o el telefono de la matricula. Es la parte que hace
            // unica a la entrega.
            $table->string('recipient_key', 64);
            $table->string('event', 8)->comment('in|out: evento de porteria que origino el aviso');
            $table->string('phone', 30);
            $table->string('status', 20)->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['gate_attendance_id', 'event', 'recipient_key'], 'aca_school_notice_deliveries_unique');
            // Limite diario: contar los avisos del alumno en el dia.
            $table->index(['student_id', 'created_at'], 'aca_school_notice_deliveries_student_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_school_notice_deliveries');
    }
};
