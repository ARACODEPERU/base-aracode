<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Auditoria de la correccion con IA: deja constancia de cuando se guardo
     * un comentario corregido y en que modo (redaccion u ortografia).
     *
     * Idempotente: cada columna se verifica antes de crearse.
     */
    public function up(): void
    {
        if (!Schema::hasTable('cms_testimonies')) {
            return;
        }

        Schema::table('cms_testimonies', function (Blueprint $table) {
            if (!Schema::hasColumn('cms_testimonies', 'ia_corrected_at')) {
                $table->timestamp('ia_corrected_at')->nullable()->after('consent_version')
                    ->comment('Momento en que el admin guardo el comentario corregido con IA');
            }

            if (!Schema::hasColumn('cms_testimonies', 'ia_correction_mode')) {
                $table->string('ia_correction_mode', 20)->nullable()->after('ia_corrected_at')
                    ->comment('writing = redaccion + ortografia | spelling = solo ortografia');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('cms_testimonies')) {
            return;
        }

        Schema::table('cms_testimonies', function (Blueprint $table) {
            foreach (['ia_correction_mode', 'ia_corrected_at'] as $column) {
                if (Schema::hasColumn('cms_testimonies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
