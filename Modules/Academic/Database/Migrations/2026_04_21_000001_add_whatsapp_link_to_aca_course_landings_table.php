<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aca_course_landings', function (Blueprint $table) {
            $table->string('whatsapp_link', 500)->nullable()->after('url_slug');
        });
    }

    public function down(): void
    {
        Schema::table('aca_course_landings', function (Blueprint $table) {
            $table->dropColumn('whatsapp_link');
        });
    }
};
