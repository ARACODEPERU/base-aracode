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
        Schema::table('aca_courses', function (Blueprint $table) {
            $table->boolean('auto_certificate')->default(false)->after('certificate_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aca_courses', function (Blueprint $table) {
            $table->dropColumn('auto_certificate');
        });
    }
};
