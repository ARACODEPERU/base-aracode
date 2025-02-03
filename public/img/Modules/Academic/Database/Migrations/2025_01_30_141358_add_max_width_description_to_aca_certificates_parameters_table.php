<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('aca_certificates_parameters', function (Blueprint $table) {
            $table->integer('max_width_description')->nullable()->after('font_size_description');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('aca_certificates_parameters', function (Blueprint $table) {
            $table->dropColumn('max_width_description');
        });
    }
};
