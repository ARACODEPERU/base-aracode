<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('onli_carrito_abandonado', 'utm_source')) {
            Schema::table('onli_carrito_abandonado', function (Blueprint $table) {
                $table->string('utm_source')->nullable()->after('cart_total');
                $table->string('utm_medium')->nullable()->after('utm_source');
                $table->string('utm_campaign')->nullable()->after('utm_medium');
                $table->string('utm_term')->nullable()->after('utm_campaign');
                $table->string('utm_content')->nullable()->after('utm_term');
                $table->string('utm_id')->nullable()->after('utm_content');
                $table->string('fbclid')->nullable()->after('utm_id');
                $table->string('gclid')->nullable()->after('fbclid');
                $table->text('referer')->nullable()->after('gclid');
                $table->text('landing_url')->nullable()->after('referer');
                $table->string('traffic_source')->nullable()->after('landing_url');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('onli_carrito_abandonado', 'utm_source')) {
            Schema::table('onli_carrito_abandonado', function (Blueprint $table) {
                $table->dropColumn([
                    'utm_source', 'utm_medium', 'utm_campaign', 'utm_term',
                    'utm_content', 'utm_id', 'fbclid', 'gclid', 'referer',
                    'landing_url', 'traffic_source',
                ]);
            });
        }
    }
};
