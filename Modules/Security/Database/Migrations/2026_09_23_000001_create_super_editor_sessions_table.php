<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sesiones del Modo Super Editor.
 *
 * Cada vez que un admin entra al modo editor (confirmando su contraseña) se
 * abre una fila aquí. El id se guarda en la sesión de PHP, así que la sesión
 * de edición sobrevive a los reloads de Inertia. Mientras ended_at sea null la
 * sesión está abierta y puede recibir cambios en borrador.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('super_editor_sessions')) {
            return;
        }

        Schema::create('super_editor_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('started_at');
            $table->timestamp('expires_at');
            $table->timestamp('ended_at')->nullable();
            // applied | discarded | expired | logout | superseded
            $table->string('close_reason', 40)->nullable();
            $table->unsignedInteger('changes_applied')->default(0);
            $table->unsignedInteger('changes_discarded')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'ended_at'], 'super_editor_sessions_user_open_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('super_editor_sessions');
    }
};
