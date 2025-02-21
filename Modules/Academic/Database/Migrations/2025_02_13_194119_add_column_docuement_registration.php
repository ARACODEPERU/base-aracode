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
        Schema::table('aca_cap_registrations', function (Blueprint $table) {
            $table->unsignedBigInteger('sale_note_id')->nullable()->comment('se genera cuando se hace una compra por internet');
            $table->unsignedBigInteger('document_id')->nullable()->comment('si se le a creado una boleta o factura el campo no estara en nulo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aca_cap_registrations', function (Blueprint $table) {
            $table->dropColumn('sale_note_id');
            $table->dropColumn('document_id');
        });
    }
};
