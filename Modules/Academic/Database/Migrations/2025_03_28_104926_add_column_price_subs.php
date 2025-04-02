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
            $table->unsignedBigInteger('xdocument_id')->nullable();
            $table->decimal('amount_paid', 12, 2)->nullable()->comment('para saber cuanto pago por su suscripcion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aca_student_subscriptions', function (Blueprint $table) {
            $table->dropColumn('amount_paid');
            $table->dropColumn('xdocument_id');
        });
    }
};
