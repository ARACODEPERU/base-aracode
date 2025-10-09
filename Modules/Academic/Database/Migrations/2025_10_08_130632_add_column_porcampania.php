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
            $table->unsignedBigInteger('arrival_source_id')
                  ->nullable();
            $table->string('arrival_source_information', 300)->nullable()->comment('para guardar algun detalle');
            $table->foreign('arrival_source_id')
                  ->references('id')
                  ->on('aca_arrival_sources')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aca_cap_registrations', function (Blueprint $table) {
            $table->dropForeign(['arrival_source_id']);
            $table->dropColumn('arrival_source_id');
        });
    }
};
