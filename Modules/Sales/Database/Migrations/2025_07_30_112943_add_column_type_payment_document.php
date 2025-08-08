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
            $table->string('forma_pago', 10)->default('Contado')->comment('forma de pago al contado o credito se envia a sunat');
            $table->boolean('status_pay')->default(true)->comment('estado true ya fue pagado estado false aun tiene pendiente de pago');
            $table->boolean('overdue_fee')->default(false)->comment('si la fecha de alguna cuota ya vencio el estado cambia a true');
            $table->boolean('single_payment')->default(false)->comment('si cancelo la deuta total se activa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_documents', function (Blueprint $table) {
            $table->dropColumn('forma_pago');
            $table->dropColumn('status_pay');
        });
    }
};
