<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tablas del modo de prueba del modulo escolar.
     *
     * - aca_school_test_records: registro de todo lo que creo el llenado de
     *   prueba (una fila por registro, con su entidad y su id). Es la lista
     *   exacta que usa el borrado: sin ella no se puede garantir que solo se
     *   elimine lo que genero el modo prueba.
     * - aca_school_test_mode: estado del modo (una sola fila). locked_at con
     *   fecha significa que el sistema quedo en modo produccion: ya no se puede
     *   llenar ni borrar datos de prueba (bloqueo definitivo desde la interfaz).
     *
     * Idempotente: solo crea las tablas que falten.
     */
    public function up(): void
    {
        if (! Schema::hasTable('aca_school_test_records')) {
            Schema::create('aca_school_test_records', function (Blueprint $table) {
                $table->id();
                // Corrida de llenado (uuid): agrupa todo lo creado de una vez.
                $table->string('run_id', 40);
                $table->unsignedBigInteger('school_id');
                // Entidad logica: year, level, grade, section, journey, area,
                // person, teacher, student, guardian, enrollment, schedule.
                $table->string('entity', 30);
                $table->unsignedBigInteger('record_id');
                // Texto visible del registro (nombre, codigo) para la pantalla.
                $table->string('label', 180)->nullable();
                $table->timestamps();

                $table->index(['school_id', 'entity'], 'aca_school_test_records_school_entity_index');
                $table->index(['entity', 'record_id'], 'aca_school_test_records_entity_record_index');
                $table->index('run_id', 'aca_school_test_records_run_index');
            });
        }

        if (! Schema::hasTable('aca_school_test_mode')) {
            Schema::create('aca_school_test_mode', function (Blueprint $table) {
                $table->id();
                $table->timestamp('locked_at')->nullable();
                $table->unsignedBigInteger('locked_by_user_id')->nullable();
                $table->string('locked_by_name', 180)->nullable();
                $table->timestamp('last_fill_at')->nullable();
                $table->timestamp('last_purge_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_school_test_mode');
        Schema::dropIfExists('aca_school_test_records');
    }
};
