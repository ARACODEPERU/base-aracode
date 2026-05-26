<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('heal_attentions', function (Blueprint $table) {
            if (!Schema::hasColumn('heal_attentions', 'cie10_id')) {
                $table->unsignedBigInteger('cie10_id')->nullable()->after('clinical_findings');
            }
        });
    }

    public function down(): void
    {
        Schema::table('heal_attentions', function (Blueprint $table) {
            if (Schema::hasColumn('heal_attentions', 'cie10_id')) {
                $table->dropColumn('cie10_id');
            }
        });
    }
};
