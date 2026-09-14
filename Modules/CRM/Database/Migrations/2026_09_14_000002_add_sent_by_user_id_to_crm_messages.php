<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registra quien respondio realmente cuando un administrador respondio
     * haciendose pasar por un asistente (null si lo envio su propio autor).
     */
    public function up(): void
    {
        Schema::table('crm_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('sent_by_user_id')->nullable()->after('person_id');
            $table->foreign('sent_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('crm_messages', function (Blueprint $table) {
            $table->dropForeign(['sent_by_user_id']);
            $table->dropColumn('sent_by_user_id');
        });
    }
};
