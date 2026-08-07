<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('cms_subscribers', function (Blueprint $table) {
            if (Schema::hasColumn('cms_subscribers', 'referer')) {
                $table->text('referer')->nullable()->change();
            }
            if (Schema::hasColumn('cms_subscribers', 'landing_url')) {
                $table->text('landing_url')->nullable()->change();
            }
        });
    }

    public function down()
    {
        // OJO: revertir a VARCHAR(255) falla si ya existen filas con referer/landing_url > 255 chars.
        Schema::table('cms_subscribers', function (Blueprint $table) {
            if (Schema::hasColumn('cms_subscribers', 'referer')) {
                $table->string('referer')->nullable()->change();
            }
            if (Schema::hasColumn('cms_subscribers', 'landing_url')) {
                $table->string('landing_url')->nullable()->change();
            }
        });
    }
};
