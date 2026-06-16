<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->string('country_code_phone', 8)->nullable()->after('country_code')
                  ->comment('Código internacional de teléfono (ej: 51, 591, 593)');
        });

        DB::table('countries')
            ->where('description', 'Perú')
            ->update(['country_code_phone' => '51']);

        DB::table('countries')
            ->where('description', 'Bolivia')
            ->update(['country_code_phone' => '591']);

        DB::table('countries')
            ->where('description', 'Ecuador')
            ->update(['country_code_phone' => '593']);

        DB::table('countries')
            ->where('description', 'Colombia')
            ->update(['country_code_phone' => '57']);

        DB::table('countries')
            ->where('description', 'Mexico')
            ->update(['country_code_phone' => '52']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn('country_code_phone');
        });
    }
};
