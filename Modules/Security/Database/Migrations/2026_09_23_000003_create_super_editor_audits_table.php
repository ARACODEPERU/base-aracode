<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bitácora del Modo Super Editor.
 *
 * Cada paso queda registrado: apertura y cierre de la sesión de edición,
 * cambios en borrador, descartes, aplicación final, intentos con contraseña
 * incorrecta y cambios bloqueados por ser permisos protegidos.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('super_editor_audits')) {
            return;
        }

        Schema::create('super_editor_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('permission_name')->nullable();
            $table->string('role_name')->nullable();
            $table->string('element_label', 160)->nullable();
            $table->string('element_kind', 60)->nullable();
            $table->string('source_url', 500)->nullable();
            $table->string('action', 40);
            $table->boolean('allowed_before')->nullable();
            $table->boolean('allowed_after')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'created_at'], 'super_editor_audits_user_index');
            $table->index('permission_name', 'super_editor_audits_permission_index');
            $table->index('action', 'super_editor_audits_action_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('super_editor_audits');
    }
};
