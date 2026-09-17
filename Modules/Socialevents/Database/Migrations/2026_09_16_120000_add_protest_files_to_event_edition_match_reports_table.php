<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Evidencias del reclamo (imagenes / PDF) adjuntas al registrar la resolucion.
     * Estructura: [{name, path}] en JSON.
     */
    public function up(): void
    {
        Schema::table('event_edition_match_reports', function (Blueprint $table) {
            $table->json('protest_files')->nullable()->after('protest_details')
                ->comment('Evidencias del reclamo: [{name, path}]');
        });
    }

    public function down(): void
    {
        Schema::table('event_edition_match_reports', function (Blueprint $table) {
            $table->dropColumn('protest_files');
        });
    }
};
