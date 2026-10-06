<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Estado de la sincronizacion con Google Calendar (una fila por calendario).
     *
     * Guarda lo que no se puede reconstruir: el `sync_token` del feed
     * incremental (la marca desde donde Google entrega los cambios) y el canal
     * de notificaciones push, que hay que renovar antes de que expire.
     *
     * El `sync_token` es opaco y puede ser largo por eso es longText: cuando
     * Google responde 410 GONE hay que descartarlo y hacer una lectura completa.
     */
    public function up(): void
    {
        Schema::create('heal_google_calendar_states', function (Blueprint $table) {
            $table->id();
            $table->string('calendar_id', 190)->default('primary');
            $table->string('channel_id', 190)->nullable();
            $table->string('resource_id', 190)->nullable();
            $table->string('resource_uri', 255)->nullable();
            $table->timestamp('channel_expires_at')->nullable();
            $table->longText('sync_token')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('last_full_sync_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique('calendar_id', 'heal_gcal_state_calendar_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heal_google_calendar_states');
    }
};
