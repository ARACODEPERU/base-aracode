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
        Schema::table('aca_courses', function (Blueprint $table) {
            $table->decimal('discount', 8, 2)->default(0)->comment('prorcentaje del descuento');
            $table->char('discount_applies', 2)->nullable()->comment('aquien aplica el descuento, 01=todos,02=Suscripción');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aca_courses', function (Blueprint $table) {
            $table->dropColumn('discount_applies');
            $table->dropColumn('discount');
        });
    }
};
