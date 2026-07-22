<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aca_certificates_parameters', function (Blueprint $table) {
            $table->integer('max_width_names')->nullable()->default(600)->after('font_size_names');
            $table->integer('back_max_width_names')->nullable()->default(600)->after('back_font_size_names');
        });
    }

    public function down(): void
    {
        Schema::table('aca_certificates_parameters', function (Blueprint $table) {
            $table->dropColumn(['max_width_names', 'back_max_width_names']);
        });
    }
};
