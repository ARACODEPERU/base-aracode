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
        Schema::table('event_edition_match_sanctions', function (Blueprint $table) {
            $table->boolean('is_paid')->default(false);
            $table->dateTime('paid_at')->nullable();
            $table->unsignedBigInteger('note_sale_id')->nullable();
            $table->foreign('note_sale_id', 'sanctions_note_sale_id_fk')->references('id')->on('sales')->onDelete('SET NULL');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_edition_match_sanctions', function (Blueprint $table) {
            $table->dropForeign('sanctions_note_sale_id_fk');
            $table->dropColumn('note_sale_id');
            $table->dropColumn('paid_at');
            $table->dropColumn('is_paid');
        });
    }
};
