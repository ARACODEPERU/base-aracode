<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rastro de los cursos creados por agentes de IA: el origen (mcp) y la huella
 * de la clave de idempotencia, para que un reintento del agente no duplique
 * el curso.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('aca_courses', 'created_via')) {
            Schema::table('aca_courses', function (Blueprint $table) {
                $table->string('created_via', 20)->nullable()->after('status')
                    ->comment('Origen del registro: web, mcp...');
            });
        }

        if (!Schema::hasColumn('aca_courses', 'idempotency_key')) {
            Schema::table('aca_courses', function (Blueprint $table) {
                $table->string('idempotency_key', 64)->nullable()->after('created_via')
                    ->comment('Huella sha256 de usuario + clave de idempotencia del agente');
                $table->unique('idempotency_key', 'aca_courses_idempotency_key_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('aca_courses', 'idempotency_key')) {
            Schema::table('aca_courses', function (Blueprint $table) {
                $table->dropUnique('aca_courses_idempotency_key_unique');
                $table->dropColumn('idempotency_key');
            });
        }

        if (Schema::hasColumn('aca_courses', 'created_via')) {
            Schema::table('aca_courses', function (Blueprint $table) {
                $table->dropColumn('created_via');
            });
        }
    }
};
