<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('aca_certificates_parameters', function (Blueprint $table) {
            $table->string('text_align_description')->nullable()->after('max_width_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aca_certificates_parameters', function (Blueprint $table) {
            $table->dropColumn('text_align_description');
        });
    }
};
