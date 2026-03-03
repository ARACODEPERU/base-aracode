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
            $table->string('back_content_type')->nullable()->after('back_content_show_module');
            $table->string('back_content_type_module')->nullable()->after('back_content_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aca_certificates_parameters', function (Blueprint $table) {
            $table->dropColumn('back_content_type_module');
            $table->dropColumn('back_content_type');
        });
    }
};
