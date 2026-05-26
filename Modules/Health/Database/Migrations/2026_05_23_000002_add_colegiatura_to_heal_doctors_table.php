<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('heal_doctors', function (Blueprint $table) {
            if (!Schema::hasColumn('heal_doctors', 'colegiatura')) {
                $table->string('colegiatura', 80)->nullable()->after('doctor_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('heal_doctors', function (Blueprint $table) {
            if (Schema::hasColumn('heal_doctors', 'colegiatura')) {
                $table->dropColumn('colegiatura');
            }
        });
    }
};
