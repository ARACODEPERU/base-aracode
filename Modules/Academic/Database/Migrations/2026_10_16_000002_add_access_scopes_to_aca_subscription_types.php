<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alcance de cada tipo de suscripción: qué categorías de cursos incluye.
     *
     * Antes el portal distinguía los planes por su título (buscando "Premium
     * VIP"), así que renombrar un plan rompía el acceso. Ahora el alcance se
     * guarda por tipo y se edita desde la pantalla de suscripciones.
     */
    public function up(): void
    {
        Schema::table('aca_subscription_types', function (Blueprint $table) {
            $table->boolean('access_all_courses')
                ->default(false)
                ->after('order_number')
                ->comment('Incluye todos los cursos o talleres (excepto especialización y webinars)');

            $table->boolean('access_specialization_programs')
                ->default(false)
                ->after('access_all_courses')
                ->comment('Incluye todos los Programas de Especialización');

            $table->boolean('access_all_webinars')
                ->default(false)
                ->after('access_specialization_programs')
                ->comment('Incluye todos los cursos de tipo Webinar');
        });

        // Antes, cualquier suscripción activa daba acceso a todos los cursos de
        // pago que no fueran de especialización (incluidos los de tipo Webinar),
        // y solo el plan Premium VIP incluía los Programas de Especialización.
        // Se replica ese comportamiento para no alterar el acceso de los alumnos.
        DB::table('aca_subscription_types')->update([
            'access_all_courses' => true,
            'access_all_webinars' => true,
        ]);

        DB::table('aca_subscription_types')
            ->where('title', 'LIKE', '%Premium VIP%')
            ->update(['access_specialization_programs' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aca_subscription_types', function (Blueprint $table) {
            $table->dropColumn(['access_all_courses', 'access_specialization_programs', 'access_all_webinars']);
        });
    }
};
