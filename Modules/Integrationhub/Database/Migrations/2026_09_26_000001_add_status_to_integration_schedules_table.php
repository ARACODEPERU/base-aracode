<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integration_schedules', function (Blueprint $table) {
            $table->string('last_status', 20)
                ->nullable()
                ->after('last_executed_at')
                ->comment('Estado de la última ejecución: pending, running, success o failed');
            $table->text('last_error')
                ->nullable()
                ->after('last_status')
                ->comment('Detalle del último fallo (vacío si todo fue bien)');
        });
    }

    public function down(): void
    {
        Schema::table('integration_schedules', function (Blueprint $table) {
            $table->dropColumn(['last_status', 'last_error']);
        });
    }
};
