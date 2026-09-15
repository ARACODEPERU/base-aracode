<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->unsignedBigInteger('foreign_country_id')->nullable()->after('country_id')->comment('Pais para usuarios extranjeros');
            $table->string('foreign_state')->nullable()->after('foreign_country_id')->comment('Departamento/Estado para usuarios extranjeros');
            $table->string('foreign_city')->nullable()->after('foreign_state')->comment('Ciudad para usuarios extranjeros');
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn(['foreign_country_id', 'foreign_state', 'foreign_city']);
        });
    }
};
