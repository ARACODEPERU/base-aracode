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
        Schema::table('crm_messages', function (Blueprint $table) {
            $table->boolean('answer_ai')->default(false)->comment('saber si la respuesta fue de la IA');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_messages', function (Blueprint $table) {
            $table->dropColumn('answer_ai');
        });
    }
};
