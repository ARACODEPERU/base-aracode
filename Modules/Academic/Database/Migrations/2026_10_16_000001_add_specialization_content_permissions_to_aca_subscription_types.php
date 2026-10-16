<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permite configurar por tipo de suscripción si el alumno puede ver los
     * enlaces de Zoom/Meet y el contenido tipo Webinar de los Programas de
     * Especialización.
     */
    public function up(): void
    {
        Schema::table('aca_subscription_types', function (Blueprint $table) {
            $table->boolean('can_view_specialization_zooms')
                ->default(false)
                ->after('order_number')
                ->comment('Ver enlaces Zoom/Meet en Programas de Especialización');

            $table->boolean('can_view_specialization_webinars')
                ->default(false)
                ->after('can_view_specialization_zooms')
                ->comment('Ver el contenido tipo Webinar en Programas de Especialización');
        });

        // Los planes Premium VIP existentes ya veían los webinars de los
        // programas de especialización (pero no los Zoom/Meet): se replica ese
        // comportamiento para no alterar el acceso de los alumnos suscritos.
        DB::table('aca_subscription_types')
            ->where('title', 'LIKE', '%Premium VIP%')
            ->update(['can_view_specialization_webinars' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aca_subscription_types', function (Blueprint $table) {
            $table->dropColumn(['can_view_specialization_zooms', 'can_view_specialization_webinars']);
        });
    }
};
