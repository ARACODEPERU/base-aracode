<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('heal_cie10s', function (Blueprint $table) {
            if (!Schema::hasColumn('heal_cie10s', 'description')) {
                $table->string('description')->nullable()->after('cie10');
            }
        });
    }

    public function down(): void
    {
        Schema::table('heal_cie10s', function (Blueprint $table) {
            if (Schema::hasColumn('heal_cie10s', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
