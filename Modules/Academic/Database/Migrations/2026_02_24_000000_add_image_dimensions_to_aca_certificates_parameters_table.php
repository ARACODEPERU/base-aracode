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
            $table->integer('certificate_img_width')->nullable()->after('certificate_img');
            $table->integer('certificate_img_height')->nullable()->after('certificate_img_width');
            $table->integer('back_certificate_img_width')->nullable()->after('back_certificate_img');
            $table->integer('back_certificate_img_height')->nullable()->after('back_certificate_img_width');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aca_certificates_parameters', function (Blueprint $table) {
            $table->dropColumn('back_certificate_img_height');
            $table->dropColumn('back_certificate_img_width');
            $table->dropColumn('certificate_img_height');
            $table->dropColumn('certificate_img_width');
        });
    }
};
