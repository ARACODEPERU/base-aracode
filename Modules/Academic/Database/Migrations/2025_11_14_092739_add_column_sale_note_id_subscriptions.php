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
        Schema::table('aca_student_subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('xsale_note_id')->nullable()->comment('para saber si tiene una nota de venta');
            $table->decimal('advancement', 12, 2)->default(0)->comment('para saber si el alumno a pago algun monto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aca_student_subscriptions', function (Blueprint $table) {
            $table->dropColumn('advancement');
            $table->dropColumn('xsale_note_id');
        });
    }
};
