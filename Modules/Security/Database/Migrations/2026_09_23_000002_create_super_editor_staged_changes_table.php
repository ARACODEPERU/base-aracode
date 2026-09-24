<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Borrador de cambios del Modo Super Editor.
 *
 * El panel "Configurar permisos de acceso" no escribe sobre spatie: deja el
 * cambio declarado aquí (rol + permiso + si se concede o se quita). Al salir
 * con la contraseña, el borrador se aplica en una sola transacción y recién
 * entonces cambia role_has_permissions. Guardamos también el estado real del
 * momento (allowed_before) para poder descartar y para que la auditoría sea
 * legible sin depender de tablas externas.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('super_editor_staged_changes')) {
            return;
        }

        Schema::create('super_editor_staged_changes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->string('permission_name');
            $table->unsignedBigInteger('role_id');
            $table->string('role_name')->nullable();
            $table->boolean('allowed')->default(true);
            $table->boolean('allowed_before')->default(false);
            $table->string('element_label', 160)->nullable();
            $table->string('element_kind', 60)->nullable();
            $table->string('source_url', 500)->nullable();
            $table->timestamps();

            // Un rol no puede tener dos intenciones distintas para el mismo permiso
            // dentro de la misma sesión de edición.
            $table->unique(['session_id', 'permission_name', 'role_id'], 'super_editor_staged_unique');
            $table->index('session_id', 'super_editor_staged_session_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('super_editor_staged_changes');
    }
};
