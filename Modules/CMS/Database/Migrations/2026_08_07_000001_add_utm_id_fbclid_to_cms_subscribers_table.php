<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('cms_subscribers', function (Blueprint $table) {
            $table->string('utm_id')->nullable()->after('utm_content');
            $table->string('fbclid')->nullable()->after('gclid');
        });
    }

    public function down()
    {
        Schema::table('cms_subscribers', function (Blueprint $table) {
            $table->dropColumn(['utm_id', 'fbclid']);
        });
    }
};
