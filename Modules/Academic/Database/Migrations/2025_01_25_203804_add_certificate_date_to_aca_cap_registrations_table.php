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
        Schema::table('aca_cap_registrations', function (Blueprint $table) {
            $table->date('certificate_date')->nullable();
        });
    }

    public function down()
    {
        Schema::table('aca_cap_registrations', function (Blueprint $table) {
            $table->dropColumn('certificate_date');
        });
    }
};
