<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sale_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('schedule_id')->nullable()->after('id');

            $table->foreign('schedule_id')
                ->references('id')
                ->on('sale_payment_schedules')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_documents', function (Blueprint $table) {
            // Primero eliminar la clave foránea
            $table->dropForeign(['schedule_id']);

            // Luego eliminar la columna
            $table->dropColumn('schedule_id');
        });
    }
};
