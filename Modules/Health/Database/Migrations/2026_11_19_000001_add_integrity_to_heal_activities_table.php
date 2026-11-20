<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refuerza la trazabilidad de heal_activities: contexto de la petición,
 * huellas del antes/después y cadena de hashes para detectar alteraciones.
 *
 * Las filas anteriores quedan con los campos nuevos en null: el verificador
 * las trata como "previas a la cadena" y no las marca como error.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('heal_activities', function (Blueprint $table) {
            $table->uuid('request_id')->nullable();
            $table->string('route', 255)->nullable();
            $table->string('http_method', 10)->nullable();
            $table->string('category', 30)->default('clinical');
            $table->char('before_hash', 64)->nullable();
            $table->char('after_hash', 64)->nullable();
            $table->char('previous_hash', 64)->nullable();
            $table->char('entry_hash', 64)->nullable();

            $table->index(['patient_id', 'created_at']);
            $table->index(['subject_type', 'subject_id', 'created_at'], 'heal_activities_subject_date_index');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::table('heal_activities', function (Blueprint $table) {
            $table->dropIndex(['patient_id', 'created_at']);
            $table->dropIndex('heal_activities_subject_date_index');
            $table->dropIndex(['category']);
            $table->dropColumn([
                'request_id',
                'route',
                'http_method',
                'category',
                'before_hash',
                'after_hash',
                'previous_hash',
                'entry_hash',
            ]);
        });
    }
};
