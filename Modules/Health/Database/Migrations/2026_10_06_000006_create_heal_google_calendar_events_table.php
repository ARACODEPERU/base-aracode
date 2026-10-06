<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mapeo entre las citas de la Agenda y los eventos de Google Calendar, y a
     * la vez buzon de los eventos que llegan de Google.
     *
     * Cada fila guarda el `etag` y el `pushed_hash` del ultimo contenido escrito
     * por el sistema: son las dos firmas que usa la sincronizacion para
     * reconocer su propio eco y no volver a escribir lo mismo (evita el bucle
     * Laravel -> Google -> Laravel).
     *
     * Estados (`sync_state`):
     *   - linked: el evento y la cita estan enlazados.
     *   - pending_review: el evento llego de Google y no se pudo resolver el
     *     paciente o el doctor; se muestra en la bandeja "Por revisar".
     *   - deleted: el evento se borro (lapida que reconoce un eco tardio).
     *   - ignored: el usuario decidio no traer ese evento al sistema.
     *   - error: el ultimo intento fallo (el motivo queda en error_message).
     */
    public function up(): void
    {
        Schema::create('heal_google_calendar_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->string('calendar_id', 190)->default('primary');
            $table->string('google_event_id', 190)->nullable();
            $table->string('etag', 190)->nullable();
            $table->timestamp('google_updated_at')->nullable();
            $table->string('pushed_hash', 64)->nullable();
            $table->string('origin', 20)->default('health');
            $table->string('sync_state', 30)->default('linked');
            $table->string('summary', 255)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('location', 255)->nullable();
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            // Un evento de Google pertenece a un solo calendario; una cita tiene
            // como maximo un evento enlazado.
            $table->unique(['calendar_id', 'google_event_id'], 'heal_gcal_event_unique');
            $table->unique('appointment_id', 'heal_gcal_appointment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heal_google_calendar_events');
    }
};
