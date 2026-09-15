<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La fecha de nacimiento de las personas ya se usa en el sistema
     * (PersonController, DashboardController y Person::$fillable), pero no existia
     * una migracion que la creara, asi que se agrega de forma idempotente.
     */
    public function up(): void
    {
        if (Schema::hasColumn('people', 'birthdate')) {
            return;
        }

        Schema::table('people', function (Blueprint $table) {
            $table->date('birthdate')->nullable()->after('ubigeo')->comment('fecha de nacimiento');
        });
    }

    public function down(): void
    {
        // No se elimina: la columna la usa el resto del sistema.
    }
};
