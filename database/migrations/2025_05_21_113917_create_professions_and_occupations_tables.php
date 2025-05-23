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
        Schema::create('professions', function (Blueprint $table) {
            $table->unsignedSmallInteger('id')->primary();
            $table->string('description')->unique();
        });

        Schema::create('occupations', function (Blueprint $table) {
            $table->unsignedSmallInteger('id')->primary();
            $table->string('description')->unique();
        });

        Schema::table('people', function (Blueprint $table) {
            $table->unsignedSmallInteger('profession_id')->nullable()->comment('id de la profecion');
            $table->unsignedSmallInteger('occupation_id')->nullable()->comment('id de la ocupacion o cargo');
        });

        // Insertar profesiones
        DB::table('professions')->insert([
            ['id' => 1, 'description' => 'Ingeniería de sistemas'],
            ['id' => 2, 'description' => 'Ingeniería industrial'],
            ['id' => 3, 'description' => 'Ingeniería civil'],
            ['id' => 4, 'description' => 'Ingeniería electrónica'],
            ['id' => 5, 'description' => 'Ingeniería mecánica'],
            ['id' => 6, 'description' => 'Ingeniería ambiental'],
            ['id' => 7, 'description' => 'Medicina'],
            ['id' => 8, 'description' => 'Enfermería'],
            ['id' => 9, 'description' => 'Odontología'],
            ['id' => 10, 'description' => 'Contabilidad'],
            ['id' => 11, 'description' => 'Derecho'],
            ['id' => 12, 'description' => 'Psicología'],
            ['id' => 13, 'description' => 'Administración de empresas'],
            ['id' => 14, 'description' => 'Economía'],
            ['id' => 15, 'description' => 'Arquitectura'],
            ['id' => 16, 'description' => 'Educación'],
            ['id' => 17, 'description' => 'Comunicación'],
            ['id' => 18, 'description' => 'Periodismo'],
            ['id' => 19, 'description' => 'Diseño gráfico'],
            ['id' => 20, 'description' => 'Trabajo social'],
            ['id' => 21, 'description' => 'Filosofía'],
            ['id' => 22, 'description' => 'Sociología'],
            ['id' => 23, 'description' => 'Relaciones internacionales'],
            ['id' => 24, 'description' => 'Ciencias políticas'],
            ['id' => 25, 'description' => 'Biología'],
            ['id' => 26, 'description' => 'Química'],
            ['id' => 27, 'description' => 'Física'],
            ['id' => 28, 'description' => 'Matemáticas'],
            ['id' => 29, 'description' => 'Farmacia y bioquímica'],
            ['id' => 30, 'description' => 'Turismo y hotelería'],
            ['id' => 31, 'description' => 'Gastronomía'],
            ['id' => 32, 'description' => 'Veterinaria'],
            ['id' => 998, 'description' => 'Sin especificar'],
            ['id' => 999, 'description' => 'Otros'],
        ]);

        DB::table('occupations')->insert([
            ['id' => 1, 'description' => 'Programador'],
            ['id' => 2, 'description' => 'Analista tributario y contable'],
            ['id' => 3, 'description' => 'Gerente general'],
            ['id' => 4, 'description' => 'Asistente administrativo'],
            ['id' => 5, 'description' => 'Auxiliar contable'],
            ['id' => 6, 'description' => 'Secretaria'],
            ['id' => 7, 'description' => 'Diseñador gráfico'],
            ['id' => 8, 'description' => 'Conductor'],
            ['id' => 9, 'description' => 'Operario de producción'],
            ['id' => 10, 'description' => 'Docente'],
            ['id' => 11, 'description' => 'Vendedor'],
            ['id' => 12, 'description' => 'Técnico en computación'],
            ['id' => 13, 'description' => 'Médico'],
            ['id' => 14, 'description' => 'Enfermero'],
            ['id' => 15, 'description' => 'Psicólogo'],
            ['id' => 16, 'description' => 'Abogado'],
            ['id' => 17, 'description' => 'Contador'],
            ['id' => 18, 'description' => 'Albañil'],
            ['id' => 19, 'description' => 'Plomero'],
            ['id' => 20, 'description' => 'Electricista'],
            ['id' => 21, 'description' => 'Jardinero'],
            ['id' => 22, 'description' => 'Cocinero'],
            ['id' => 23, 'description' => 'Mesero'],
            ['id' => 24, 'description' => 'Barista'],
            ['id' => 25, 'description' => 'Estilista'],
            ['id' => 26, 'description' => 'Estudiante'],
            // Nuevas ocupaciones contabilidad, derecho, ingenierías, telecom
            ['id' => 27, 'description' => 'Auditor contable'],
            ['id' => 28, 'description' => 'Perito contable'],
            ['id' => 29, 'description' => 'Consultor financiero'],
            ['id' => 30, 'description' => 'Asesor legal'],
            ['id' => 31, 'description' => 'Abogado corporativo'],
            ['id' => 32, 'description' => 'Asistente legal'],
            ['id' => 33, 'description' => 'Notario'],
            ['id' => 34, 'description' => 'Secretario judicial'],
            ['id' => 35, 'description' => 'Ingeniero de sistemas'],
            ['id' => 36, 'description' => 'Ingeniero de telecomunicaciones'],
            ['id' => 37, 'description' => 'Ingeniero electrónico'],
            ['id' => 38, 'description' => 'Ingeniero industrial'],
            ['id' => 39, 'description' => 'Ingeniero civil'],
            ['id' => 40, 'description' => 'Ingeniero mecánico'],
            ['id' => 41, 'description' => 'Analista de redes'],
            ['id' => 42, 'description' => 'Administrador de servidores'],
            ['id' => 43, 'description' => 'Especialista en ciberseguridad'],
            ['id' => 44, 'description' => 'Analista de datos'],
            ['id' => 45, 'description' => 'Técnico en soporte TI'],
            ['id' => 46, 'description' => 'Diseñador de circuitos electrónicos'],
            ['id' => 998, 'description' => 'Sin especificar'],
            ['id' => 999, 'description' => 'Otros'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('occupations');
        Schema::dropIfExists('professions');
    }
};
