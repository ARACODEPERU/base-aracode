<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aca_course_landings', function (Blueprint $table) {
            $table->string('flow_id', 255)->nullable()->after('whatsapp_link')->comment('ID de ChatLvl o similar que inicia el flujo o envía plantilla de WhatsApp');
        });
    }

    public function down(): void
    {
        Schema::table('aca_course_landings', function (Blueprint $table) {
            $table->dropColumn('flow_id');
        });
    }
};
