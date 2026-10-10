<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Escala de evaluacion configurable por nivel del colegio
     * (literal AD/A/B/C o vigesimal 0-20). Null = comportamiento por
     * defecto (Inicial literal, Primaria/Secundaria vigesimal).
     * Idempotente y sin eliminacion de tablas (skill no-drop-tables).
     */
    public function up(): void
    {
        if (Schema::hasColumn('aca_school_levels', 'scale')) {
            return;
        }

        Schema::table('aca_school_levels', function (Blueprint $table) {
            $table->string('scale', 20)->nullable()
                ->comment('literal|vigesimal; null usa el default por nivel')
                ->after('status');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('aca_school_levels', 'scale')) {
            Schema::table('aca_school_levels', function (Blueprint $table) {
                $table->dropColumn('scale');
            });
        }
    }
};
