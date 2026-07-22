<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('cms_subscribers', function (Blueprint $table) {
            $table->string('utm_source')->nullable()->after('message');
            $table->string('utm_medium')->nullable()->after('utm_source');
            $table->string('utm_campaign')->nullable()->after('utm_medium');
            $table->string('utm_term')->nullable()->after('utm_campaign');
            $table->string('utm_content')->nullable()->after('utm_term');
            $table->string('gclid')->nullable()->after('utm_content');
            $table->string('referer')->nullable()->after('gclid');
            $table->string('landing_url')->nullable()->after('referer');
            $table->string('traffic_source')->nullable()->after('landing_url');
        });
    }

    public function down()
    {
        Schema::table('cms_subscribers', function (Blueprint $table) {
            $table->dropColumn([
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_term',
                'utm_content', 'gclid', 'referer', 'landing_url', 'traffic_source'
            ]);
        });
    }
};
