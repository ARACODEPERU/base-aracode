<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1️⃣ Crear tabla de fuentes de llegada
        Schema::create('aca_arrival_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        // 2️⃣ Insertar valores iniciales
        DB::table('aca_arrival_sources')->insert([
            [
                'name' => 'Landing Page',
                'description' => 'Registro desde página de aterrizaje',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Publicidad Facebook',
                'description' => 'Llegó por campaña en redes sociales',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Recomendación',
                'description' => 'Referido por otro estudiante o contacto',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Evento o Feria',
                'description' => 'Captado en un evento educativo o feria',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Otro',
                'description' => 'Otro medio no especificado',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 3️⃣ Agregar campo a la tabla aca_students
        Schema::table('aca_students', function (Blueprint $table) {
            $table->unsignedBigInteger('arrival_source_id')
                  ->nullable()
                  ->after('new_student');
            $table->string('arrival_source_information', 300)->nullable()->comment('para guardar algun detalle');
            $table->foreign('arrival_source_id')
                  ->references('id')
                  ->on('aca_arrival_sources')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar la relación y el campo
        Schema::table('aca_students', function (Blueprint $table) {
            $table->dropForeign(['arrival_source_id']);
            $table->dropColumn('arrival_source_id');
        });

        // Eliminar la tabla de fuentes
        Schema::dropIfExists('aca_arrival_sources');
    }
};
