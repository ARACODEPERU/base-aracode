<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Idempotente: solo agrega la columna si no existe.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('aca_exams', 'is_mock')) {
            Schema::table('aca_exams', function (Blueprint $table) {
                $table->boolean('is_mock')->default(false)->after('status')->comment('Indica si el examen es un simulacro (no afecta promedio)');
            });
        }
    }

    /**
     * Reverse the migrations.
     * Idempotente: solo elimina la columna si existe.
     */
    public function down(): void
    {
        if (Schema::hasColumn('aca_exams', 'is_mock')) {
            Schema::table('aca_exams', function (Blueprint $table) {
                $table->dropColumn('is_mock');
            });
        }
    }
};
