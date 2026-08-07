<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('aca_course_landings', function (Blueprint $table) {
            $table->json('utm_config')->nullable()->after('faq_section');
        });
    }

    public function down()
    {
        Schema::table('aca_course_landings', function (Blueprint $table) {
            $table->dropColumn('utm_config');
        });
    }
};
